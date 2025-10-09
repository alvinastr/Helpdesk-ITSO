<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use App\Http\Requests\TicketRequest;

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

        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
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
            $data['user_name'] = auth()->user()->name;
            $data['user_email'] = auth()->user()->email;
            $data['channel'] = 'portal';

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
        if (auth()->user()->role !== 'admin' && $ticket->user_id !== auth()->id()) {
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
        if (auth()->user()->role !== 'admin' && $ticket->user_id !== auth()->id()) {
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
            'sender_type' => auth()->user()->role === 'admin' ? 'admin' : 'user',
            'sender_id' => auth()->id(),
            'sender_name' => auth()->user()->name,
            'message_type' => 'reply',
            'message' => $request->message,
            'attachments' => !empty($attachments) ? $attachments : null
        ]);

        // Auto-update status if needed
        if ($ticket->status === 'resolved' && auth()->user()->role !== 'admin') {
            $this->ticketService->updateStatus($ticket, 'in_progress', 'User replied after resolution');
        }

        return back()->with('success', 'Reply berhasil ditambahkan!');
    }

    /**
     * Submit feedback
     */
    public function feedback(Request $request, Ticket $ticket)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000'
        ]);

        if ($ticket->status !== 'closed') {
            return back()->with('error', 'Feedback hanya bisa diberikan untuk ticket yang sudah closed');
        }

        $this->ticketService->addFeedback(
            $ticket, 
            $request->rating, 
            $request->feedback
        );

        return back()->with('success', 'Terima kasih atas feedback Anda!');
    }
}
