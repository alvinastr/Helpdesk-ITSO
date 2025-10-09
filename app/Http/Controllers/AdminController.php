<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;

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
            'pending_review' => Ticket::where('status', 'pending_review')->count(),
            'open' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed_today' => Ticket::where('status', 'closed')
                ->whereDate('closed_at', today())->count(),
            'avg_resolution_time' => $this->calculateAvgResolutionTime(),
            'satisfaction_score' => Ticket::whereNotNull('rating')
                ->avg('rating')
        ];

        $recentTickets = Ticket::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTickets'));
    }

    /**
     * List pending tickets for review
     */
    public function pendingReview()
    {
        $tickets = Ticket::where('status', 'pending_review')
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
            'reason' => 'required|string|min:10'
        ]);

        $this->ticketService->rejectTicket($ticket, $request->reason);

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
            'resolution_notes' => 'required|string|min:20'
        ]);

        $this->ticketService->closeTicket($ticket, $request->resolution_notes);

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
     * Calculate average resolution time
     */
    protected function calculateAvgResolutionTime()
    {
        $tickets = Ticket::where('status', 'closed')
            ->whereNotNull('closed_at')
            ->get();

        if ($tickets->isEmpty()) return 0;

        $totalHours = 0;
        foreach ($tickets as $ticket) {
            $totalHours += $ticket->created_at->diffInHours($ticket->closed_at);
        }

        return round($totalHours / $tickets->count(), 1);
    }
}