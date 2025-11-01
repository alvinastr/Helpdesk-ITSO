<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiCalculationService
{
    /**
     * Update KPI metrics untuk ticket
     * 
     * @param Ticket $ticket
     * @return void
     */
    public function updateTicketKpiMetrics(Ticket $ticket)
    {
        $updates = [];

        // Calculate response time
        if ($ticket->email_received_at && $ticket->first_response_at) {
            $updates['response_time_minutes'] = $ticket->email_received_at->diffInMinutes($ticket->first_response_at);
        }

        // Calculate resolution time
        if ($ticket->email_received_at && $ticket->resolved_at) {
            $updates['resolution_time_minutes'] = $ticket->email_received_at->diffInMinutes($ticket->resolved_at);
        }

        // Calculate ticket creation delay
        if ($ticket->email_received_at && $ticket->created_at) {
            $updates['ticket_creation_delay_minutes'] = $ticket->email_received_at->diffInMinutes($ticket->created_at);
        }

        if (!empty($updates)) {
            $ticket->update($updates);
        }
    }

    /**
     * Set first response time jika belum ada
     * 
     * @param Ticket $ticket
     * @param Carbon|null $responseTime
     * @return void
     */
    public function setFirstResponseTime(Ticket $ticket, ?Carbon $responseTime = null)
    {
        if (!$ticket->first_response_at) {
            $ticket->update([
                'first_response_at' => $responseTime ?? now()
            ]);
            
            $this->updateTicketKpiMetrics($ticket);
        }
    }

    /**
     * Set resolved time
     * 
     * @param Ticket $ticket
     * @param Carbon|null $resolvedTime
     * @return void
     */
    public function setResolvedTime(Ticket $ticket, ?Carbon $resolvedTime = null)
    {
        $ticket->update([
            'resolved_at' => $resolvedTime ?? now()
        ]);
        
        $this->updateTicketKpiMetrics($ticket);
    }

    /**
     * Get average response time
     * 
     * @param array $filters
     * @return float|null
     */
    public function getAverageResponseTime(array $filters = [])
    {
        $query = Ticket::whereNotNull('response_time_minutes');
        
        $this->applyFilters($query, $filters);
        
        return $query->avg('response_time_minutes');
    }

    /**
     * Get average resolution time
     * 
     * @param array $filters
     * @return float|null
     */
    public function getAverageResolutionTime(array $filters = [])
    {
        $query = Ticket::whereNotNull('resolution_time_minutes');
        
        $this->applyFilters($query, $filters);
        
        return $query->avg('resolution_time_minutes');
    }

    /**
     * Get average ticket creation delay
     * 
     * @param array $filters
     * @return float|null
     */
    public function getAverageTicketCreationDelay(array $filters = [])
    {
        $query = Ticket::whereNotNull('ticket_creation_delay_minutes');
        
        $this->applyFilters($query, $filters);
        
        return $query->avg('ticket_creation_delay_minutes');
    }

    /**
     * Get KPI summary
     * 
     * @param array $filters
     * @return array
     */
    public function getKpiSummary(array $filters = [])
    {
        $query = Ticket::query();
        $this->applyFilters($query, $filters);

        $totalTickets = (clone $query)->count();
        $ticketsWithResponse = (clone $query)->whereNotNull('first_response_at')->count();
        $ticketsResolved = (clone $query)->whereNotNull('resolved_at')->count();

        // Response time metrics
        $avgResponseTime = (clone $query)->whereNotNull('response_time_minutes')->avg('response_time_minutes');
        $minResponseTime = (clone $query)->whereNotNull('response_time_minutes')->min('response_time_minutes');
        $maxResponseTime = (clone $query)->whereNotNull('response_time_minutes')->max('response_time_minutes');
        
        // Resolution time metrics
        $avgResolutionTime = (clone $query)->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes');
        $minResolutionTime = (clone $query)->whereNotNull('resolution_time_minutes')->min('resolution_time_minutes');
        $maxResolutionTime = (clone $query)->whereNotNull('resolution_time_minutes')->max('resolution_time_minutes');

        // Ticket creation delay metrics
        $avgCreationDelay = (clone $query)->whereNotNull('ticket_creation_delay_minutes')->avg('ticket_creation_delay_minutes');

        // SLA compliance (example: response within 30 minutes, resolution within 2 days)
        $responseTarget = 30; // minutes
        $resolutionTarget = 2880; // minutes (48 hours)
        
        $ticketsWithinResponseTarget = (clone $query)
            ->whereNotNull('response_time_minutes')
            ->where('response_time_minutes', '<=', $responseTarget)
            ->count();
            
        $ticketsWithinResolutionTarget = (clone $query)
            ->whereNotNull('resolution_time_minutes')
            ->where('resolution_time_minutes', '<=', $resolutionTarget)
            ->count();

        return [
            'total_tickets' => $totalTickets,
            'tickets_with_response' => $ticketsWithResponse,
            'tickets_resolved' => $ticketsResolved,
            'response_rate' => $totalTickets > 0 ? round(($ticketsWithResponse / $totalTickets) * 100, 2) : 0,
            'resolution_rate' => $totalTickets > 0 ? round(($ticketsResolved / $totalTickets) * 100, 2) : 0,
            
            'avg_response_time_minutes' => round($avgResponseTime ?? 0, 2),
            'avg_response_time_formatted' => $this->formatMinutes($avgResponseTime),
            'min_response_time_minutes' => $minResponseTime,
            'max_response_time_minutes' => $maxResponseTime,
            
            'avg_resolution_time_minutes' => round($avgResolutionTime ?? 0, 2),
            'avg_resolution_time_formatted' => $this->formatMinutes($avgResolutionTime),
            'min_resolution_time_minutes' => $minResolutionTime,
            'max_resolution_time_minutes' => $maxResolutionTime,
            
            'avg_creation_delay_minutes' => round($avgCreationDelay ?? 0, 2),
            'avg_creation_delay_formatted' => $this->formatMinutes($avgCreationDelay),
            
            'sla_response_compliance' => $ticketsWithResponse > 0 
                ? round(($ticketsWithinResponseTarget / $ticketsWithResponse) * 100, 2) 
                : 0,
            'sla_resolution_compliance' => $ticketsResolved > 0 
                ? round(($ticketsWithinResolutionTarget / $ticketsResolved) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get KPI data grouped by period
     * 
     * @param string $period (daily, weekly, monthly)
     * @param array $filters
     * @return array
     */
    public function getKpiTrend(string $period = 'daily', array $filters = [])
    {
        $query = Ticket::query();
        $this->applyFilters($query, $filters);

        // Determine database driver
        $driver = DB::connection()->getDriverName();
        
        // Use appropriate date format function based on database driver
        if ($driver === 'sqlite') {
            $dateFormat = match($period) {
                'weekly' => '%Y-%W',
                'monthly' => '%Y-%m',
                default => '%Y-%m-%d',
            };
            $dateFormatSql = "strftime('{$dateFormat}', created_at)";
        } else {
            // MySQL/MariaDB
            $dateFormat = match($period) {
                'weekly' => '%Y-%u',
                'monthly' => '%Y-%m',
                default => '%Y-%m-%d',
            };
            $dateFormatSql = "DATE_FORMAT(created_at, '{$dateFormat}')";
        }

        $results = $query
            ->select([
                DB::raw("{$dateFormatSql} as period"),
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('COUNT(first_response_at) as tickets_with_response'),
                DB::raw('COUNT(resolved_at) as tickets_resolved'),
                DB::raw('AVG(response_time_minutes) as avg_response_time'),
                DB::raw('AVG(resolution_time_minutes) as avg_resolution_time'),
                DB::raw('AVG(ticket_creation_delay_minutes) as avg_creation_delay'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $results->map(function ($item) {
            return [
                'period' => $item->period,
                'total_tickets' => $item->total_tickets,
                'tickets_with_response' => $item->tickets_with_response,
                'tickets_resolved' => $item->tickets_resolved,
                'avg_response_time' => round($item->avg_response_time ?? 0, 2),
                'avg_response_time_formatted' => $this->formatMinutes($item->avg_response_time),
                'avg_resolution_time' => round($item->avg_resolution_time ?? 0, 2),
                'avg_resolution_time_formatted' => $this->formatMinutes($item->avg_resolution_time),
                'avg_creation_delay' => round($item->avg_creation_delay ?? 0, 2),
                'avg_creation_delay_formatted' => $this->formatMinutes($item->avg_creation_delay),
            ];
        })->toArray();
    }

    /**
     * Get KPI by category
     * 
     * @param array $filters
     * @return array
     */
    public function getKpiByCategory(array $filters = [])
    {
        $query = Ticket::query();
        $this->applyFilters($query, $filters);

        $results = $query
            ->select([
                'category',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('AVG(response_time_minutes) as avg_response_time'),
                DB::raw('AVG(resolution_time_minutes) as avg_resolution_time'),
            ])
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

        return $results->map(function ($item) {
            return [
                'category' => $item->category,
                'total_tickets' => $item->total_tickets,
                'avg_response_time' => round($item->avg_response_time ?? 0, 2),
                'avg_response_time_formatted' => $this->formatMinutes($item->avg_response_time),
                'avg_resolution_time' => round($item->avg_resolution_time ?? 0, 2),
                'avg_resolution_time_formatted' => $this->formatMinutes($item->avg_resolution_time),
            ];
        })->toArray();
    }

    /**
     * Get KPI by priority
     * 
     * @param array $filters
     * @return array
     */
    public function getKpiByPriority(array $filters = [])
    {
        $query = Ticket::query();
        $this->applyFilters($query, $filters);

        // Database-agnostic ordering for priority
        $driver = DB::connection()->getDriverName();
        $orderByRaw = $driver === 'sqlite'
            ? "CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 END"
            : "FIELD(priority, 'critical', 'high', 'medium', 'low')";

        $results = $query
            ->select([
                'priority',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('AVG(response_time_minutes) as avg_response_time'),
                DB::raw('AVG(resolution_time_minutes) as avg_resolution_time'),
            ])
            ->groupBy('priority')
            ->orderByRaw($orderByRaw)
            ->get();

        return $results->map(function ($item) {
            return [
                'priority' => $item->priority,
                'total_tickets' => $item->total_tickets,
                'avg_response_time' => round($item->avg_response_time ?? 0, 2),
                'avg_response_time_formatted' => $this->formatMinutes($item->avg_response_time),
                'avg_resolution_time' => round($item->avg_resolution_time ?? 0, 2),
                'avg_resolution_time_formatted' => $this->formatMinutes($item->avg_resolution_time),
            ];
        })->toArray();
    }

    /**
     * Apply filters to query
     * 
     * @param $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
    }

    /**
     * Format minutes to human readable string
     * 
     * @param float|null $minutes
     * @return string
     */
    private function formatMinutes(?float $minutes): string
    {
        if (!$minutes) return '-';
        
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        $days = floor($hours / 24);
        $hours = $hours % 24;
        
        if ($days > 0) {
            return sprintf('%d hari %d jam %d menit', $days, $hours, $mins);
        } elseif ($hours > 0) {
            return sprintf('%d jam %d menit', $hours, $mins);
        } else {
            return sprintf('%d menit', $mins);
        }
    }
}
