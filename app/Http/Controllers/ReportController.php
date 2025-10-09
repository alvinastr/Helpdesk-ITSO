<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Show reports page
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = [
            'total_tickets' => $this->getTotalTickets($startDate, $endDate),
            'by_status' => $this->getTicketsByStatus($startDate, $endDate),
            'by_category' => $this->getTicketsByCategory($startDate, $endDate),
            'by_channel' => $this->getTicketsByChannel($startDate, $endDate),
            'by_priority' => $this->getTicketsByPriority($startDate, $endDate),
            'avg_rating' => $this->getAverageRating($startDate, $endDate),
            'resolution_times' => $this->getResolutionTimes($startDate, $endDate)
        ];

        return view('reports.index', compact('data', 'startDate', 'endDate'));
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'assignedUser', 'approvedBy'])
            ->get();

        // Use Laravel Excel or PhpSpreadsheet
        // return Excel::download(new TicketsExport($tickets), 'tickets.xlsx');
        
        return response()->json(['message' => 'Export feature - integrate Laravel Excel']);
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $data = [
            'tickets' => Ticket::whereBetween('created_at', [$startDate, $endDate])->get(),
            'stats' => [
                'total' => Ticket::whereBetween('created_at', [$startDate, $endDate])->count(),
                'closed' => Ticket::where('status', 'closed')
                    ->whereBetween('created_at', [$startDate, $endDate])->count(),
            ]
        ];

        // Use DomPDF or similar
        // $pdf = PDF::loadView('reports.pdf', $data);
        // return $pdf->download('report.pdf');
        
        return response()->json(['message' => 'PDF export feature - integrate DomPDF']);
    }

    // Helper methods for statistics
    protected function getTotalTickets($start, $end)
    {
        return Ticket::whereBetween('created_at', [$start, $end])->count();
    }

    protected function getTicketsByStatus($start, $end)
    {
        return Ticket::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    protected function getTicketsByCategory($start, $end)
    {
        return Ticket::whereBetween('created_at', [$start, $end])
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');
    }

    protected function getTicketsByChannel($start, $end)
    {
        return Ticket::whereBetween('created_at', [$start, $end])
            ->selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel');
    }

    protected function getTicketsByPriority($start, $end)
    {
        return Ticket::whereBetween('created_at', [$start, $end])
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');
    }

    protected function getAverageRating($start, $end)
    {
        return Ticket::whereBetween('created_at', [$start, $end])
            ->whereNotNull('rating')
            ->avg('rating');
    }

    protected function getResolutionTimes($start, $end)
    {
        $tickets = Ticket::where('status', 'closed')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $times = [];
        foreach ($tickets as $ticket) {
            $hours = $ticket->created_at->diffInHours($ticket->closed_at);
            $times[] = $hours;
        }

        return [
            'avg' => !empty($times) ? round(array_sum($times) / count($times), 1) : 0,
            'min' => !empty($times) ? min($times) : 0,
            'max' => !empty($times) ? max($times) : 0
        ];
    }
}