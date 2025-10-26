<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $tickets = Ticket::whereIn('status', ['pending_keluhan', 'pending_review'])
            ->with(['threads', 'statusHistories'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.tickets.pending', compact('tickets'));
    }

    /**
     * Approve a ticket
     */
    public function approve(Request $request, $ticketId)
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
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
    public function reject(Request $request, $ticketId)
    {
        Log::info("Reject method called for ticket: $ticketId");
        
        $ticket = Ticket::findOrFail($ticketId);
        Log::info("Reject ticket found", ['ticket' => $ticket->toArray()]);
        
        $request->validate([
            'reason' => 'required|string|min:10|max:500'
        ]);

        try {
            Log::info("About to call rejectTicket service method");
            $result = $this->ticketService->rejectTicket($ticket, $request->reason, Auth::user());
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
    public function requestRevision(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
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
            'status' => 'required|string|in:open,in_progress,resolved,closed'
        ]);

        try {
            $this->ticketService->updateStatus($ticket, $request->status, 'Status updated by admin');
            
            return redirect()->back()->with('success', 'Status ticket berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengupdate status: ' . $e->getMessage());
        }
    }

    /**
     * Close a ticket with resolution
     */
    public function close(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        $request->validate([
            'resolution_notes' => 'required|string|min:10|max:1000'
        ]);

        try {
            $this->ticketService->closeTicket($ticket, $request->resolution_notes, Auth::user());
            
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
        
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $assignee = User::findOrFail($request->user_id);
        
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
            $stats = [
                'pending_review' => Ticket::where('status', 'pending_review')->count(),
                'open' => Ticket::where('status', 'open')->count(),
                'resolved' => Ticket::where('status', 'resolved')->count(),
                'closed_today' => Ticket::where('status', 'closed')
                    ->whereDate('updated_at', today())->count(),
            ];

            $recentTickets = Ticket::with(['threads'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return view('admin.dashboard', compact('stats', 'recentTickets'));
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}