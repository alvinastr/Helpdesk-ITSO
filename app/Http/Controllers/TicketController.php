<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use App\Http\Requests\TicketRequest;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Display listing of user's tickets
     */
    public function index(Request $request)
    {
        $query = Ticket::query();

        if (Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')
                        ->paginate(20);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show form for creating new ticket
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Store new ticket
     */
    public function store(TicketRequest $request)
    {
        try {
            $data = $request->validated();
            
            // Set user data from authenticated user (for system tracking)
            $data['user_id'] = Auth::id();
            $data['user_name'] = Auth::user()->name;
            $data['user_email'] = Auth::user()->email;
            $data['user_phone'] = Auth::user()->phone ?? null;
            
            // Default values for user-created tickets
            $data['channel'] = $data['channel'] ?? 'portal';
            $data['input_method'] = $data['input_method'] ?? 'manual';

            $ticket = $this->ticketService->createTicket($data);

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket {$ticket->ticket_number} berhasil dibuat!");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat ticket: ' . $e->getMessage());
        }
    }

    /**
     * Display ticket details
     */
    public function show(Ticket $ticket)
    {
        // Authorization check
        if (Auth::user()->role !== 'admin' && $ticket->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        $ticket->load(['threads' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'statusHistories', 'assignedUser', 'approvedBy']);

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Reply to ticket
     */
    public function reply(Request $request, Ticket $ticket)
    {
        // Authorization check
        if (Auth::user()->role !== 'admin' && $ticket->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'message' => 'required|min:5',
            'attachments.*' => 'nullable|file|max:5120' // 5MB
        ]);

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                $attachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path
                ];
            }
        }

        $this->ticketService->addThreadMessage($ticket, [
            'sender_type' => Auth::user()->role === 'admin' ? 'admin' : 'user',
            'sender_id' => Auth::id(),
            'sender_name' => Auth::user()->name,
            'message_type' => 'reply',
            'message' => $request->message,
            'attachments' => !empty($attachments) ? $attachments : null
        ]);

        // Auto-update status if needed
        if ($ticket->status === 'resolved' && Auth::user()->role !== 'admin') {
            $this->ticketService->updateStatus($ticket, 'in_progress', 'User replied after resolution');
        }

        return back()->with('success', 'Reply berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the ticket
     */
    public function edit(Ticket $ticket)
    {
        // Check ownership
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        // Only allow editing if ticket is still pending or open
        if (!in_array($ticket->status, ['pending_keluhan', 'open'])) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Ticket tidak dapat diedit pada status ini');
        }

        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Update the ticket
     */
    public function update(Request $request, Ticket $ticket)
    {
        // Check ownership
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        // Only allow editing if ticket is still pending or open
        if (!in_array($ticket->status, ['pending_keluhan', 'open'])) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Ticket tidak dapat diedit pada status ini');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical'
        ]);

        $ticket->update($validated);

        // Log the update in status history
        \App\Models\TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => 'Ticket updated by user',
            'is_internal' => false
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket berhasil diupdate');
    }

}
