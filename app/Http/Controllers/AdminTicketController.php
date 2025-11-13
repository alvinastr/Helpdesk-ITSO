<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminTicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->middleware('auth');
        $this->middleware('admin'); // Ensure only admins can access
        $this->ticketService = $ticketService;
    }

    /**
     * Show pending tickets for admin review
     */
    public function pendingTickets()
    {
        $tickets = Ticket::where('status', 'pending_keluhan')
            ->with(['threads', 'statusHistories'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.tickets.pending', compact('tickets'));
    }

    /**
     * Approve a ticket
     */
    public function approve(Request $request, Ticket $ticket)
    {
        try {
            Log::info('Approve method called for ticket: ' . $ticket->id . ' status: ' . $ticket->status);
            
            $result = $this->ticketService->approveTicket($ticket, Auth::user());
            Log::info('Approve result: ' . $result->status);
            
            return redirect()->back()->with('success', 'Ticket berhasil disetujui');
        } catch (\Exception $e) {
            Log::error('Approve error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyetujui ticket: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending ticket
     */
    public function reject(Request $request, Ticket $ticket)
    {
        Log::info("Reject method called for ticket: {$ticket->id}");
        Log::info("Reject ticket found", ['ticket' => $ticket->toArray()]);
        
        // Accept both 'reason' and 'rejection_reason' for compatibility
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        try {
            Log::info("About to call rejectTicket service method");
            // Use default reason if empty - check both possible field names
            $reason = $request->rejection_reason ?: $request->reason ?: 'Ticket ditolak oleh admin';
            $result = $this->ticketService->rejectTicket($ticket, $reason, Auth::user());
            Log::info("RejectTicket completed", ['result_status' => $result->status]);
            
            return redirect()->back()->with('success', 'Ticket berhasil ditolak');
        } catch (\Exception $e) {
            Log::error("Reject error: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Gagal menolak ticket: ' . $e->getMessage());
        }
    }

    /**
     * Request revision for a ticket
     */
    public function requestRevision(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string|min:10|max:1000'
        ]);

        try {
            $this->ticketService->requestRevision($ticket, $request->message, Auth::user());
            
            return redirect()->back()->with('success', 'Permintaan revisi berhasil dikirim');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengirim permintaan revisi: ' . $e->getMessage());
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $notes = $request->notes ?? 'Status updated by admin';
            $this->ticketService->updateStatus($ticket, $request->status, $notes);
            
            return redirect()->back()->with('success', 'Status ticket berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengupdate status: ' . $e->getMessage());
        }
    }

    /**
     * Close a ticket with resolution
     */
    public function close(Request $request, Ticket $ticket)
    {
        // Resolution notes is now optional - will be auto-filled if empty
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        try {
            // Use default resolution notes if empty
            $resolutionNotes = $request->resolution_notes ?: 'Masalah telah diselesaikan';
            $this->ticketService->closeTicket($ticket, $resolutionNotes, Auth::user());
            
            return redirect()->back()->with('success', 'Ticket berhasil ditutup');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menutup ticket: ' . $e->getMessage());
        }
    }

    /**
     * Assign ticket to another admin
     */
    public function assign(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        // Accept both 'user_id' and 'assigned_to' for compatibility
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        // Get assignee ID from either field
        $assigneeId = $request->assigned_to ?: $request->user_id;
        
        if (!$assigneeId) {
            return redirect()->back()->with('error', 'User ID wajib diisi');
        }

        $assignee = User::findOrFail($assigneeId);
        
        if ($assignee->role !== 'admin') {
            return redirect()->back()->with('error', 'Hanya bisa assign ke admin lain');
        }

        try {
            $this->ticketService->assignTicket($ticket, $assignee, Auth::user());
            
            return redirect()->back()->with('success', 'Ticket berhasil di-assign ke ' . $assignee->name);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal assign ticket: ' . $e->getMessage());
        }
    }

    /**
     * Add internal note to ticket
     */
    public function addNote(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        $request->validate([
            'message' => 'required|string|min:5|max:1000',
            'is_internal' => 'boolean'
        ]);

        try {
            $this->ticketService->addThreadMessage($ticket, [
                'sender_type' => 'admin',
                'sender_name' => Auth::user()->name,
                'sender_id' => Auth::id(),
                'message_type' => $request->is_internal ? 'note' : 'reply',
                'message' => $request->message
            ]);
            
            return redirect()->back()->with('success', 'Catatan berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan catatan: ' . $e->getMessage());
        }
    }

    /**
     * Show admin dashboard with statistics
     */
    public function dashboard()
    {
        try {
            // Total tickets count
            $totalTickets = Ticket::count();
            
            // Open tickets count (not closed)
            $openTickets = Ticket::where('status', '!=', 'closed')->count();
            
            // Closed tickets count
            $closedTickets = Ticket::where('status', 'closed')->count();

            // Statistics array
            $stats = [
                'pending_keluhan' => Ticket::where('status', 'pending_keluhan')->count(),
                'open' => Ticket::where('status', 'open')->count(),
                'resolved' => Ticket::where('status', 'resolved')->count(),
                'closed_today' => Ticket::where('status', 'closed')
                    ->whereDate('updated_at', today())->count(),
            ];

            // Recent tickets
            $recentTickets = Ticket::with(['threads'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Tickets by category
            $ticketsByCategory = Ticket::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category');

            return view('admin.dashboard', compact(
                'stats', 
                'recentTickets', 
                'totalTickets', 
                'openTickets', 
                'closedTickets',
                'ticketsByCategory'
            ));
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}