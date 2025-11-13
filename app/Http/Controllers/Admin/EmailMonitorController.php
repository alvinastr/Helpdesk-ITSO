<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailMonitorController extends Controller
{
    /**
     * Display email fetch monitoring dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'today'); // today, week, month

        // Get date range based on period
        $dateRange = $this->getDateRange($period);

        // Statistics
        $stats = [
            'auto_created' => $this->getAutoCreatedCount($dateRange),
            'manual_created' => $this->getManualCreatedCount($dateRange),
            'total' => $this->getTotalTickets($dateRange),
            'auto_percentage' => 0,
        ];

        if ($stats['total'] > 0) {
            $stats['auto_percentage'] = round(($stats['auto_created'] / $stats['total']) * 100, 1);
        }

        // Chart data - Tickets per day
        $chartData = $this->getTicketsPerDay($dateRange);

        // Recent auto-created tickets
        $recentTickets = $this->getRecentAutoTickets(10);

        // Email sources (top senders)
        $topSenders = $this->getTopEmailSenders($dateRange, 10);

        // Response time stats
        $responseStats = $this->getResponseTimeStats($dateRange);

        return view('admin.email-monitor', compact(
            'stats',
            'chartData',
            'recentTickets',
            'topSenders',
            'responseStats',
            'period'
        ));
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

    /**
     * Get count of auto-created tickets
     */
    protected function getAutoCreatedCount($dateRange): int
    {
        return Ticket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('input_method', 'email_auto')
            ->count();
    }

    /**
     * Get count of manual created tickets
     */
    protected function getManualCreatedCount($dateRange): int
    {
        return Ticket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('input_method', '!=', 'email_auto')
            ->count();
    }

    /**
     * Get total tickets
     */
    protected function getTotalTickets($dateRange): int
    {
        return Ticket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();
    }

    /**
     * Get tickets per day for chart
     */
    protected function getTicketsPerDay($dateRange): array
    {
        $tickets = Ticket::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN input_method = "email_auto" THEN 1 ELSE 0 END) as auto'),
                DB::raw('SUM(CASE WHEN input_method != "email_auto" THEN 1 ELSE 0 END) as manual')
            )
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $tickets->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('d M');
            })->toArray(),
            'auto' => $tickets->pluck('auto')->toArray(),
            'manual' => $tickets->pluck('manual')->toArray(),
        ];
    }

    /**
     * Get recent auto-created tickets
     */
    protected function getRecentAutoTickets($limit = 10)
    {
        return Ticket::where('input_method', 'email_auto')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top email senders
     */
    protected function getTopEmailSenders($dateRange, $limit = 10)
    {
        return Ticket::select('reporter_email', DB::raw('COUNT(*) as count'))
            ->where('input_method', 'email_auto')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('reporter_email')
            ->groupBy('reporter_email')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get response time statistics
     */
    protected function getResponseTimeStats($dateRange): array
    {
        $tickets = Ticket::where('input_method', 'email_auto')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('email_received_at')
            ->whereNotNull('first_response_at')
            ->get();

        if ($tickets->isEmpty()) {
            return [
                'avg_response_time' => 0,
                'fastest' => 0,
                'slowest' => 0,
            ];
        }

        $responseTimes = [];
        foreach ($tickets as $ticket) {
            $emailReceived = Carbon::parse($ticket->email_received_at);
            $firstResponse = Carbon::parse($ticket->first_response_at);
            $responseTimes[] = $emailReceived->diffInMinutes($firstResponse);
        }

        return [
            'avg_response_time' => round(array_sum($responseTimes) / count($responseTimes), 1),
            'fastest' => min($responseTimes),
            'slowest' => max($responseTimes),
        ];
    }

    /**
     * Test email fetch manually (for admin)
     */
    public function testFetch(Request $request)
    {
        try {
            // Run fetch command
            \Illuminate\Support\Facades\Artisan::call('emails:fetch');
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Email fetch completed',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fetch failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get live statistics (for AJAX refresh)
     */
    public function liveStats(Request $request)
    {
        $period = $request->get('period', 'today');
        $dateRange = $this->getDateRange($period);

        // Count total tickets created via email auto-fetch
        $totalFetched = Ticket::where('input_method', 'email_auto_fetch')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Count successful email-fetched tickets (approved or auto-approved)
        $successful = Ticket::where('input_method', 'email_auto_fetch')
            ->whereIn('validation_status', ['approved', 'auto_approved'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Count failed email-fetched tickets (rejected or needs revision)
        $failed = Ticket::where('input_method', 'email_auto_fetch')
            ->whereIn('validation_status', ['rejected', 'needs_revision'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Get last fetch time
        $lastFetchTime = $this->getLastFetchTime();

        return response()->json([
            'total_fetched' => $totalFetched,
            'successful' => $successful,
            'failed' => $failed,
            'last_fetch_time' => $lastFetchTime,
        ]);
    }

    /**
     * Get last fetch time from latest auto-created ticket
     */
    protected function getLastFetchTime(): ?string
    {
        $lastTicket = Ticket::where('input_method', 'email_auto')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastTicket) {
            return $lastTicket->created_at->diffForHumans();
        }

        return null;
    }
}
