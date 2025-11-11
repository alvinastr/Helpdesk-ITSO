<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTicket;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * WhatsApp Controller for Admin
 * 
 * @phpstan-type AuthUser \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User
 */
class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display a paginated list of WhatsApp tickets for admins
     */
    public function index(Request $request)
    {
        $query = WhatsAppTicket::with('assignedTo');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search by phone or ticket number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%");
            });
        }

        $perPage = 20;
        $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Statistics for dashboard
        $stats = [
            'total' => WhatsAppTicket::count(),
            'open' => WhatsAppTicket::where('status', 'open')->count(),
            'in_progress' => WhatsAppTicket::where('status', 'in_progress')->count(),
            'resolved' => WhatsAppTicket::where('status', 'resolved')->count(),
            'urgent' => WhatsAppTicket::where('priority', 'urgent')->count(),
            'today' => WhatsAppTicket::whereDate('created_at', today())->count(),
        ];

        return view('admin.whatsapp.index', compact('tickets', 'stats'));
    }

    /**
     * Show single WhatsApp ticket detail
     */
    public function show($id)
    {
        $ticket = WhatsAppTicket::with(['responses', 'assignedTo'])->findOrFail($id);

        return view('admin.whatsapp.show', compact('ticket'));
    }

    /**
     * Assign ticket to admin
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $ticket = WhatsAppTicket::findOrFail($id);
        $ticket->assignTo($request->user_id);

        // Track first assignment for KPI
        $ticket->recordFirstAssignment();

        Log::info("WhatsApp ticket assigned", [
            'ticket_id' => $ticket->ticket_number,
            'assigned_to' => $request->user_id,
            'assigned_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Ticket berhasil di-assign');
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,pending,resolved,closed',
            'note' => 'nullable|string',
        ]);

        $ticket = WhatsAppTicket::findOrFail($id);
        $oldStatus = $ticket->status;
        
        // Track work started for KPI (when status changes to in_progress for first time)
        if ($request->status === 'in_progress' && $oldStatus !== 'in_progress') {
            $ticket->recordWorkStarted();
        }
        
        if ($request->status === 'resolved') {
            $ticket->resolve($request->note);
        } elseif ($request->status === 'closed') {
            $ticket->close($request->note);
        } else {
            $ticket->update(['status' => $request->status]);
            if ($request->note) {
                $ticket->addResponse($request->note, 'status_change');
            }
        }

        // Check and update SLA status after any status change
        $ticket->checkSlaStatus();

        Log::info("WhatsApp ticket status updated", [
            'ticket_id' => $ticket->ticket_number,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Status berhasil diupdate');
    }

    /**
     * Add internal note or response
     */
    public function addResponse(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|in:internal_note,reply',
        ]);

        $ticket = WhatsAppTicket::findOrFail($id);
        $ticket->addResponse($request->message, $request->type, Auth::id());

        // If type is reply, send via WhatsApp
        if ($request->type === 'reply') {
            // Track first response for KPI
            $ticket->recordFirstResponse();
            
            $sent = $this->whatsappService->sendMessage(
                $ticket->sender_phone, 
                $request->message,
                $ticket->ticket_number  // Pass ticket number for bot tracking
            );
            
            if ($sent) {
                Log::info("WhatsApp reply sent", [
                    'ticket_id' => $ticket->ticket_number,
                    'to' => $ticket->sender_phone,
                ]);
            } else {
                Log::error("Failed to send WhatsApp reply", [
                    'ticket_id' => $ticket->ticket_number,
                    'to' => $ticket->sender_phone,
                ]);
            }
        }

        Log::info("Response added to WhatsApp ticket", [
            'ticket_id' => $ticket->ticket_number,
            'type' => $request->type,
            'by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Response berhasil ditambahkan');
    }
    
    /**
     * Send template reply
     */
    public function sendTemplate(Request $request, $id)
    {
        $request->validate([
            'template' => 'required|in:received,in_progress,resolved,need_info,closed'
        ]);
        
        $ticket = WhatsAppTicket::findOrFail($id);
        
        // Template messages
        $templates = [
            'received' => "Terima kasih telah menghubungi ITSO Helpdesk.\n\n📋 Tiket Anda: {$ticket->ticket_number}\n📁 Kategori: " . ucfirst($ticket->category) . "\n\nTim kami akan segera membantu Anda. Mohon menunggu update selanjutnya.",
            
            'in_progress' => "🔄 Update Tiket {$ticket->ticket_number}\n\nTiket Anda sedang dalam proses penanganan oleh tim IT kami.\n\nMohon menunggu, kami akan memberikan update segera.",
            
            'resolved' => "✅ Tiket {$ticket->ticket_number} telah selesai ditangani.\n\nTerima kasih atas kesabaran Anda. Jika masih ada kendala, silakan hubungi kami kembali.",
            
            'need_info' => "❓ Informasi Tambahan Diperlukan\n\nUntuk menangani tiket {$ticket->ticket_number}, kami memerlukan informasi tambahan dari Anda.\n\nMohon balas pesan ini dengan informasi yang diminta.",
            
            'closed' => "✅ Tiket {$ticket->ticket_number} telah ditutup.\n\nTerima kasih telah menggunakan layanan ITSO Helpdesk. Jika ada masalah lain, jangan ragu untuk menghubungi kami kembali."
        ];
        
        $message = $templates[$request->template];
        
        // Save response to database
        $ticket->addResponse($message, 'reply', Auth::id());
        
        // Send via WhatsApp
        $sent = $this->whatsappService->sendMessage(
            $ticket->sender_phone,
            $message,
            $ticket->ticket_number
        );
        
        if ($sent) {
            Log::info("WhatsApp template sent", [
                'ticket_id' => $ticket->ticket_number,
                'template' => $request->template,
                'to' => $ticket->sender_phone,
            ]);
            
            return redirect()->back()->with('success', 'Template message berhasil dikirim');
        } else {
            Log::error("Failed to send template", [
                'ticket_id' => $ticket->ticket_number,
                'template' => $request->template,
            ]);
            
            return redirect()->back()->with('error', 'Gagal mengirim template message');
        }
    }

    /**
     * Update actual report time for accurate KPI calculation
     */
    public function updateActualTime(Request $request, $id)
    {
        $request->validate([
            'actual_report_time' => 'required|date|before_or_equal:now',
        ]);

        $ticket = WhatsAppTicket::findOrFail($id);
        $ticket->update([
            'actual_report_time' => $request->actual_report_time
        ]);

        // Recalculate SLA status with new actual time
        $ticket->checkSlaStatus();

        Log::info("WhatsApp ticket actual report time updated", [
            'ticket_id' => $ticket->ticket_number,
            'actual_report_time' => $request->actual_report_time,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Actual report time berhasil diupdate. KPI akan dihitung ulang.');
    }

    /**
     * Dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'today'); // today, week, month
        $dateRange = $this->getDateRange($period);

        // Statistics
        $stats = [
            'total' => WhatsAppTicket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'open' => WhatsAppTicket::where('status', 'open')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'in_progress' => WhatsAppTicket::where('status', 'in_progress')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'resolved' => WhatsAppTicket::where('status', 'resolved')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'closed' => WhatsAppTicket::where('status', 'closed')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'urgent' => WhatsAppTicket::where('priority', 'urgent')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'high' => WhatsAppTicket::where('priority', 'high')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
        ];

        // Category breakdown
        $categories = WhatsAppTicket::selectRaw('category, COUNT(*) as count')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('category')
            ->get();

        // Response time stats
        $resolvedTickets = WhatsAppTicket::whereNotNull('resolved_at')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $avgResponseTime = 0;
        if ($resolvedTickets->count() > 0) {
            $totalMinutes = 0;
            foreach ($resolvedTickets as $ticket) {
                $created = Carbon::parse($ticket->created_at);
                $resolved = Carbon::parse($ticket->resolved_at);
                $totalMinutes += $created->diffInMinutes($resolved);
            }
            $avgResponseTime = round($totalMinutes / $resolvedTickets->count());
        }

        // KPI Metrics
        $kpiMetrics = [
            // Average First Response Time
            'avg_frt' => WhatsAppTicket::whereNotNull('first_response_at')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->get()
                ->avg(function($ticket) {
                    return $ticket->first_response_time;
                }),
            
            // Average Resolution Time
            'avg_rt' => WhatsAppTicket::whereNotNull('resolved_at')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->get()
                ->avg(function($ticket) {
                    return $ticket->resolution_time;
                }),
            
            // Average Handle Time
            'avg_ht' => WhatsAppTicket::whereNotNull('total_handle_time')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->avg('total_handle_time'),
            
            // SLA Compliance Rate
            'sla_compliance' => $this->calculateSlaCompliance($dateRange),
            
            // Tickets by SLA Status
            'sla_breached_count' => WhatsAppTicket::where('sla_breached', true)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            
            'sla_on_track_count' => WhatsAppTicket::where('sla_breached', false)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
        ];

        // Recent tickets
        $recentTickets = WhatsAppTicket::with('assignedTo')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.whatsapp.dashboard', compact('stats', 'categories', 'avgResponseTime', 'kpiMetrics', 'recentTickets', 'period'));
    }

    /**
     * Calculate SLA compliance rate
     */
    protected function calculateSlaCompliance(array $dateRange): float
    {
        $total = WhatsAppTicket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('resolved_at')
            ->count();

        if ($total === 0) {
            return 100;
        }

        $onTime = WhatsAppTicket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('resolved_at')
            ->where('sla_breached', false)
            ->count();

        return round(($onTime / $total) * 100, 1);
    }

    /**
     * Get date range based on period
     */
    protected function getDateRange($period): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
            default:
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
        }
    }
}
