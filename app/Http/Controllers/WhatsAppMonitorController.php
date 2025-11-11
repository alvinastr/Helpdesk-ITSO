<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppTicket;
use Carbon\Carbon;

class WhatsAppMonitorController extends Controller
{
    private $botApiUrl;
    
    public function __construct()
    {
        $this->botApiUrl = config('services.whatsapp.bot_url', 'http://localhost:3000');
    }
    
    /**
     * Display WhatsApp Bot Dashboard
     */
    public function index()
    {
        // Get bot status
        $botStatus = $this->getBotStatus();
        
        // Get queue statistics
        $queueStats = $this->getQueueStats();
        
        // Get today's tickets statistics
        $ticketsToday = WhatsAppTicket::whereDate('created_at', Carbon::today())->count();
        $ticketsWeek = WhatsAppTicket::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();
        $ticketsMonth = WhatsAppTicket::whereMonth('created_at', Carbon::now()->month)->count();
        
        // Get tickets by category
        $ticketsByCategory = WhatsAppTicket::selectRaw('category, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('category')
            ->get();
            
        // Get tickets by priority
        $ticketsByPriority = WhatsAppTicket::selectRaw('priority, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('priority')
            ->get();
        
        // Get recent tickets
        $recentTickets = WhatsAppTicket::with('responses')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('whatsapp.monitor', compact(
            'botStatus',
            'queueStats',
            'ticketsToday',
            'ticketsWeek',
            'ticketsMonth',
            'ticketsByCategory',
            'ticketsByPriority',
            'recentTickets'
        ));
    }
    
    /**
     * Get Bot Status via API
     */
    private function getBotStatus()
    {
        try {
            $response = Http::timeout(3)->get("{$this->botApiUrl}/health");
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Calculate uptime if available
                $uptime = null;
                if (isset($data['uptime'])) {
                    $seconds = (int) $data['uptime'];
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $uptime = sprintf('%dh %dm', $hours, $minutes);
                }
                
                return [
                    'online' => true,
                    'whatsapp_name' => $data['whatsapp']['info']['pushname'] ?? 'Unknown',
                    'whatsapp_state' => $data['whatsapp']['state'] ?? 'disconnected',
                    'whatsapp_number' => $data['whatsapp']['info']['wid']['user'] ?? null,
                    'timestamp' => $data['timestamp'] ?? null,
                    'uptime' => $uptime,
                    'queue' => $data['queue'] ?? null
                ];
            }
        } catch (\Exception $e) {
            // Bot offline or unreachable
            // Log error for debugging
            Log::info('WhatsApp Bot connection failed', [
                'url' => $this->botApiUrl,
                'error' => $e->getMessage()
            ]);
        }
        
        return [
            'online' => false,
            'whatsapp_name' => null,
            'whatsapp_state' => 'disconnected',
            'whatsapp_number' => null,
            'error' => 'Bot tidak dapat dijangkau',
            'uptime' => null
        ];
    }
    
    /**
     * Get Queue Statistics via API
     */
    private function getQueueStats()
    {
        try {
            $response = Http::timeout(3)->get("{$this->botApiUrl}/queue/stats");
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Bot offline
        }
        
        return [
            'processed' => 0,
            'failed' => 0,
            'pending' => 0
        ];
    }
    
    /**
     * Get Bot Status API (for AJAX refresh)
     */
    public function apiStatus()
    {
        $status = $this->getBotStatus();
        $queueStats = $this->getQueueStats();
        
        return response()->json([
            'bot' => $status,
            'queue' => $queueStats,
            'tickets_today' => WhatsAppTicket::whereDate('created_at', Carbon::today())->count()
        ]);
    }
    
    /**
     * Get Recent Activity Logs
     */
    public function logs(Request $request)
    {
        $limit = $request->input('limit', 50);
        
        $logs = WhatsAppTicket::with('responses')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'timestamp' => $ticket->created_at->format('Y-m-d H:i:s'),
                    'ticket_number' => $ticket->ticket_number,
                    'sender' => $ticket->sender_phone,
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'message' => substr($ticket->original_message ?? '', 0, 100) . '...'
                ];
            });
        
        return response()->json($logs);
    }
    
    /**
     * Get Statistics for Charts
     */
    public function statistics(Request $request)
    {
        $days = $request->input('days', 7);
        
        // Tickets per day
        $ticketsPerDay = WhatsAppTicket::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Category distribution
        $categoryStats = WhatsAppTicket::selectRaw('category, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('category')
            ->get();
        
        // Priority distribution
        $priorityStats = WhatsAppTicket::selectRaw('priority, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('priority')
            ->get();
        
        // Status distribution
        $statusStats = WhatsAppTicket::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        
        return response()->json([
            'tickets_per_day' => $ticketsPerDay,
            'by_category' => $categoryStats,
            'by_priority' => $priorityStats,
            'by_status' => $statusStats
        ]);
    }
}
