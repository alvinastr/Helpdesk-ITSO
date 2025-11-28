<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->middleware(['auth', 'role:admin']);
        $this->ticketService = $ticketService;
    }

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'pending_keluhan' => Ticket::where('status', 'pending_keluhan')->count(),
            'open' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed_today' => Ticket::where('status', 'closed')
                ->whereDate('closed_at', today())->count()
        ];

        $recentTickets = Ticket::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.dashboard', compact('stats', 'recentTickets'));
    }

    /**
     * List pending tickets for review
     */
    public function pendingReview()
    {
        $tickets = Ticket::where('status', 'pending_keluhan')
            ->with('user')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('admin.pending-review', compact('tickets'));
    }

    /**
     * Approve ticket
     */
    public function approve(Request $request, Ticket $ticket)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $this->ticketService->approveTicket(
            $ticket, 
            $request->assigned_to
        );

        return back()->with('success', "Ticket {$ticket->ticket_number} approved!");
    }

    /**
     * Reject ticket
     */
    public function reject(Request $request, Ticket $ticket)
    {
        $request->validate([
            'reason' => 'nullable|string|min:10' // Changed to nullable
        ]);

        // Auto-generate reason if not provided
        $reason = $request->reason ?: 'Ticket ditolak oleh admin';

        $this->ticketService->rejectTicket($ticket, $reason);

        return back()->with('success', "Ticket {$ticket->ticket_number} rejected!");
    }

    /**
     * Request revision
     */
    public function requestRevision(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string|min:10'
        ]);

        $this->ticketService->requestRevision($ticket, $request->message);

        return back()->with('success', 'Revision request sent!');
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved',
            'notes' => 'nullable|string'
        ]);

        $this->ticketService->updateProgress(
            $ticket, 
            $request->status, 
            $request->notes ?? "Status updated to {$request->status}"
        );

        return back()->with('success', 'Status updated!');
    }

    /**
     * Close ticket
     */
    public function close(Request $request, Ticket $ticket)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|min:10' // Changed to nullable with lower min
        ]);

        // Auto-generate resolution notes if not provided
        $resolutionNotes = $request->resolution_notes ?: 'Masalah telah diselesaikan';

        $this->ticketService->closeTicket($ticket, $resolutionNotes);

        return back()->with('success', "Ticket {$ticket->ticket_number} closed!");
    }

    /**
     * Assign ticket to handler
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $ticket->update(['assigned_to' => $request->user_id]);

        $this->ticketService->addThreadMessage($ticket, [
            'sender_type' => 'system',
            'sender_name' => 'System',
            'message_type' => 'note',
            'message' => "Ticket assigned to " . User::find($request->user_id)->name
        ]);

        return back()->with('success', 'Ticket assigned!');
    }

    /**
     * Show form for admin to create ticket
     */
    public function createTicket()
    {
        return view('admin.create-ticket');
    }

    /**
     * Store ticket created by admin
     */
    public function storeTicket(\App\Http\Requests\AdminTicketRequest $request)
    {
        try {
            Log::info('=== Admin Create Ticket Start ===');
            Log::info('Request data:', $request->all());
            
            $data = $request->validated();
            Log::info('Validated data:', $data);
            
            // Add admin info
            $data['created_by_admin'] = Auth::id();
            
            // If input method is manual, set user data same as reporter
            if ($data['input_method'] === 'manual') {
                $data['user_name'] = $data['reporter_name'];
                $data['user_email'] = $data['reporter_email'];
                $data['user_phone'] = $data['reporter_phone'];
                $data['user_id'] = null; // External user
            }

            $ticket = $this->ticketService->createTicketByAdmin($data);
            Log::info('Ticket created successfully:', ['ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number]);

            return redirect()
                ->route('admin.tickets.show', $ticket)
                ->with('success', "Ticket {$ticket->ticket_number} berhasil dibuat!");
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Validasi gagal: ' . implode(', ', array_keys($e->errors())));
        } catch (\Exception $e) {
            Log::error('Error creating ticket:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat ticket: ' . $e->getMessage());
        }
    }

    /**
     * Show ticket details for admin
     */
    public function showTicket(Ticket $ticket)
    {
        $ticket->load([
            'threads' => function($query) {
                $query->orderBy('created_at', 'asc');
            }, 
            'statusHistories.changedBy', 
            'assignedUser', 
            'approvedBy',
            'createdByAdmin'
        ]);

        return view('tickets.show', compact('ticket'));
    }
}