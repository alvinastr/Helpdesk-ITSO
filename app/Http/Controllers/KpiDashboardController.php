<?php

namespace App\Http\Controllers;

use App\Services\KpiCalculationService;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KpiDashboardController extends Controller
{
    protected $kpiService;

    public function __construct(KpiCalculationService $kpiService)
    {
        $this->kpiService = $kpiService;
    }

    /**
     * Display KPI Dashboard
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $filters = $this->getFilters($request);

        // Get KPI Summary
        $summary = $this->kpiService->getKpiSummary($filters);

        // Get KPI Trends
        $period = $request->input('period', 'daily');
        $trends = $this->kpiService->getKpiTrend($period, $filters);

        // Get KPI by Category
        $byCategory = $this->kpiService->getKpiByCategory($filters);

        // Get KPI by Priority
        $byPriority = $this->kpiService->getKpiByPriority($filters);

        // Get tickets with SLA issues (slow response or resolution)
        $slowTickets = Ticket::with(['user', 'assignedUser'])
            ->where(function ($q) {
                // Response time exceeded target (>30 minutes)
                $q->where(function ($query) {
                    $query->whereNotNull('response_time_minutes')
                          ->where('response_time_minutes', '>', 30);
                })
                // OR Resolution time exceeded target (>2880 minutes = 48 hours)
                ->orWhere(function ($query) {
                    $query->whereNotNull('resolution_time_minutes')
                          ->where('resolution_time_minutes', '>', 2880);
                });
            })
            ->when($filters['date_from'] ?? null, function ($q, $date) {
                return $q->where('created_at', '>=', $date);
            })
            ->when($filters['date_to'] ?? null, function ($q, $date) {
                return $q->where('created_at', '<=', $date);
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('kpi.dashboard', compact(
            'summary',
            'trends',
            'byCategory',
            'byPriority',
            'slowTickets',
            'filters',
            'period'
        ));
    }

    /**
     * Export KPI Report
     */
    public function export(Request $request)
    {
        $filters = $this->getFilters($request);
        $format = $request->input('format', 'csv');

        // Get comprehensive KPI data
        $summary = $this->kpiService->getKpiSummary($filters);
        $trends = $this->kpiService->getKpiTrend('daily', $filters);
        $byCategory = $this->kpiService->getKpiByCategory($filters);
        $byPriority = $this->kpiService->getKpiByPriority($filters);

        if ($format === 'json') {
            return response()->json([
                'summary' => $summary,
                'trends' => $trends,
                'by_category' => $byCategory,
                'by_priority' => $byPriority,
            ]);
        }

        // CSV Export
        $filename = 'kpi-report-' . now()->format('Y-m-d') . '.csv';
        
        return response()->streamDownload(function () use ($summary, $trends, $byCategory, $byPriority) {
            $handle = fopen('php://output', 'w');
            
            // Summary section
            fputcsv($handle, ['KPI SUMMARY REPORT']);
            fputcsv($handle, ['Generated at', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Tickets', $summary['total_tickets']]);
            fputcsv($handle, ['Tickets with Response', $summary['tickets_with_response']]);
            fputcsv($handle, ['Tickets Resolved', $summary['tickets_resolved']]);
            fputcsv($handle, ['Response Rate (%)', $summary['response_rate']]);
            fputcsv($handle, ['Resolution Rate (%)', $summary['resolution_rate']]);
            fputcsv($handle, ['Avg Response Time', $summary['avg_response_time_formatted']]);
            fputcsv($handle, ['Avg Resolution Time', $summary['avg_resolution_time_formatted']]);
            fputcsv($handle, ['Avg Ticket Creation Delay', $summary['avg_creation_delay_formatted']]);
            fputcsv($handle, ['SLA Response Compliance (%)', $summary['sla_response_compliance']]);
            fputcsv($handle, ['SLA Resolution Compliance (%)', $summary['sla_resolution_compliance']]);
            fputcsv($handle, []);
            
            // Trends section
            fputcsv($handle, ['DAILY TRENDS']);
            fputcsv($handle, ['Period', 'Total Tickets', 'Avg Response Time', 'Avg Resolution Time']);
            foreach ($trends as $trend) {
                fputcsv($handle, [
                    $trend['period'],
                    $trend['total_tickets'],
                    $trend['avg_response_time_formatted'],
                    $trend['avg_resolution_time_formatted'],
                ]);
            }
            fputcsv($handle, []);
            
            // By Category section
            fputcsv($handle, ['BY CATEGORY']);
            fputcsv($handle, ['Category', 'Total Tickets', 'Avg Response Time', 'Avg Resolution Time']);
            foreach ($byCategory as $cat) {
                fputcsv($handle, [
                    $cat['category'],
                    $cat['total_tickets'],
                    $cat['avg_response_time_formatted'],
                    $cat['avg_resolution_time_formatted'],
                ]);
            }
            fputcsv($handle, []);
            
            // By Priority section
            fputcsv($handle, ['BY PRIORITY']);
            fputcsv($handle, ['Priority', 'Total Tickets', 'Avg Response Time', 'Avg Resolution Time']);
            foreach ($byPriority as $pri) {
                fputcsv($handle, [
                    $pri['priority'],
                    $pri['total_tickets'],
                    $pri['avg_response_time_formatted'],
                    $pri['avg_resolution_time_formatted'],
                ]);
            }
            
            fclose($handle);
        }, $filename);
    }

    /**
     * API endpoint for KPI data
     */
    public function apiSummary(Request $request)
    {
        $filters = $this->getFilters($request);
        $summary = $this->kpiService->getKpiSummary($filters);

        return response()->json($summary);
    }

    /**
     * API endpoint for KPI trends
     */
    public function apiTrends(Request $request)
    {
        $filters = $this->getFilters($request);
        $period = $request->input('period', 'daily');
        $trends = $this->kpiService->getKpiTrend($period, $filters);

        return response()->json($trends);
    }

    /**
     * Get filters from request
     */
    private function getFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('date_from')) {
            $filters['date_from'] = Carbon::parse($request->input('date_from'))->startOfDay();
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = Carbon::parse($request->input('date_to'))->endOfDay();
        }

        if ($request->has('category')) {
            $filters['category'] = $request->input('category');
        }

        if ($request->has('priority')) {
            $filters['priority'] = $request->input('priority');
        }

        if ($request->has('status')) {
            $filters['status'] = $request->input('status');
        }

        if ($request->has('assigned_to')) {
            $filters['assigned_to'] = $request->input('assigned_to');
        }

        return $filters;
    }
}
