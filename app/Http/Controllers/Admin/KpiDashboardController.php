<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KpiDashboardController extends Controller
{
    /**
     * Display KPI Dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $channel = $request->get('channel');
        $category = $request->get('category');
        
        // Get date range
        $dateRange = $this->getDateRange($period, $request);
        
        // Build query
        $query = Ticket::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        if ($channel) {
            $query->where('channel', $channel);
        }
        
        if ($category) {
            $query->where('category', $category);
        }
        
        // Calculate KPI metrics
        $totalTickets = $query->count();
        $closedTickets = (clone $query)->where('status', 'closed')->count();
        $openTickets = (clone $query)->where('status', 'open')->count();
        
        $avgFirstResponseTime = (clone $query)->whereNotNull('first_response_at')
            ->avg('response_time_minutes');
            
        $avgResolutionTime = (clone $query)->where('status', 'closed')
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');
            
        $avgSatisfactionScore = (clone $query)->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');
            
        $resolutionRate = $totalTickets > 0 ? round(($closedTickets / $totalTickets) * 100, 1) : 0;
        
        $breachedSlaCount = (clone $query)->where('sla_breached', true)->count();
        $slaComplianceRate = $totalTickets > 0 
            ? round((($totalTickets - $breachedSlaCount) / $totalTickets) * 100, 1) 
            : 0;
        
        // Top performing agents
        $topAgents = User::where('role', 'admin')
            ->withCount(['assignedTickets as closed_tickets_count' => function($q) use ($dateRange) {
                $q->where('status', 'closed')
                  ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }])
            ->orderByDesc('closed_tickets_count')
            ->take(5)
            ->get();
        
        // Agent workload
        $agentWorkload = User::where('role', 'admin')
            ->withCount(['assignedTickets as active_tickets' => function($q) {
                $q->whereIn('status', ['open', 'in_progress']);
            }])
            ->get();
        
        // Ticket volume trend (last 7 days)
        $ticketTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Ticket::whereDate('created_at', $date)->count();
            $ticketTrend[] = [
                'date' => $date->format('M d'),
                'count' => $count
            ];
        }
        
        // Response time trend (last 7 days)
        $responseTimeTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $avgTime = Ticket::whereDate('created_at', $date)
                ->whereNotNull('response_time_minutes')
                ->avg('response_time_minutes');
            $responseTimeTrend[] = [
                'date' => $date->format('M d'),
                'minutes' => round($avgTime ?? 0, 0)
            ];
        }
        
        // Tickets by category
        $ticketsByCategory = (clone $query)
            ->select('category', \DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();
        
        // Resolution time by category
        $resolutionTimeByCategory = Ticket::where('status', 'closed')
            ->whereNotNull('resolution_time_minutes')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select('category', \DB::raw('AVG(resolution_time_minutes) as avg_time'))
            ->groupBy('category')
            ->get();
        
        // Comparison with previous period
        $comparison = null;
        if ($request->get('compare') === 'previous') {
            $previousRange = $this->getPreviousPeriodRange($dateRange);
            $previousTotal = Ticket::whereBetween('created_at', [$previousRange['start'], $previousRange['end']])->count();
            $comparison = [
                'current' => $totalTickets,
                'previous' => $previousTotal,
                'change' => $previousTotal > 0 ? round((($totalTickets - $previousTotal) / $previousTotal) * 100, 1) : 0
            ];
        }
        
        // Percentage change
        $percentageChange = $comparison ? $comparison['change'] : null;
        
        return view('admin.kpi-dashboard', compact(
            'avgFirstResponseTime',
            'avgResolutionTime',
            'avgSatisfactionScore',
            'totalTickets',
            'openTickets',
            'closedTickets',
            'resolutionRate',
            'slaComplianceRate',
            'breachedSlaCount',
            'topAgents',
            'agentWorkload',
            'ticketTrend',
            'responseTimeTrend',
            'ticketsByCategory',
            'resolutionTimeByCategory',
            'comparison',
            'percentageChange',
            'period',
            'channel',
            'category'
        ));
    }
    
    /**
     * Get live stats via AJAX
     */
    public function liveStats()
    {
        return response()->json([
            'totalTickets' => Ticket::count(),
            'openTickets' => Ticket::where('status', 'open')->count(),
            'closedTickets' => Ticket::where('status', 'closed')->count(),
            'avgResponseTime' => round(Ticket::whereNotNull('response_time_minutes')->avg('response_time_minutes') ?? 0, 0),
            'avgResolutionTime' => round(Ticket::where('status', 'closed')->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes') ?? 0, 0),
        ]);
    }
    
    /**
     * Get date range based on period
     */
    protected function getDateRange($period, $request)
    {
        $now = Carbon::now();
        
        if ($request->has('start_date') && $request->has('end_date')) {
            return [
                'start' => Carbon::parse($request->start_date)->startOfDay(),
                'end' => Carbon::parse($request->end_date)->endOfDay(),
            ];
        }
        
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
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
        }
    }
    
    /**
     * Get previous period range for comparison
     */
    protected function getPreviousPeriodRange($currentRange)
    {
        $duration = $currentRange['start']->diffInDays($currentRange['end']);
        
        return [
            'start' => $currentRange['start']->copy()->subDays($duration + 1),
            'end' => $currentRange['start']->copy()->subDay(),
        ];
    }
}
