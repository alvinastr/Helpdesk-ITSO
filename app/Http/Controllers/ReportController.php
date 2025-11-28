<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;

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
        $status = $request->get('status');
        $category = $request->get('category');
        $priority = $request->get('priority');
        $channel = $request->get('channel');

        // Build query for tickets with proper datetime handling
        $query = Ticket::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        
        if ($status) {
            $query->where('status', $status);
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }
        if ($channel) {
            $query->where('channel', $channel);
        }

        $tickets = $query->with(['user', 'assignedUser'])->get();
        $totalTickets = $tickets->count();

        // Get statistics
        $ticketsByStatus = $this->getTicketsByStatus($startDate, $endDate);
        $ticketsByCategory = $this->getTicketsByCategory($startDate, $endDate);
        $ticketsByChannel = $this->getTicketsByChannel($startDate, $endDate);
        $ticketsByPriority = $this->getTicketsByPriority($startDate, $endDate);

        return view('admin.reports.index', compact(
            'tickets',
            'totalTickets',
            'ticketsByStatus',
            'ticketsByCategory',
            'ticketsByChannel',
            'ticketsByPriority',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status');
        $category = $request->get('category');

        $query = Ticket::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->with(['user', 'assignedUser']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($category) {
            $query->where('category', $category);
        }

        $tickets = $query->get();

        $filename = 'tickets_report_' . now()->format('Y-m-d_His') . '.xlsx';
        $filePath = storage_path('app/' . $filename);

        $writer = SimpleExcelWriter::create($filePath);

        foreach ($tickets as $ticket) {
            // Calculate resolution time
            $resolutionTime = null;
            $resolvedDate = null;
            if (in_array($ticket->status, ['resolved', 'closed']) && $ticket->updated_at) {
                $resolutionTime = $ticket->created_at->diffInHours($ticket->updated_at);
                $resolvedDate = $ticket->updated_at->format('Y-m-d H:i:s');
            }
            
            $writer->addRow([
                'No. Tiket' => $ticket->ticket_number,
                'Subjek' => $ticket->subject,
                'Pelapor' => $ticket->reporter_name ?: $ticket->email_from ?: $ticket->user_name,
                'Email' => $ticket->email_from ?: $ticket->reporter_email ?: '-',
                'Status' => ucfirst(str_replace('_', ' ', $ticket->status)),
                'Kategori' => $ticket->category ?: '-',
                'Prioritas' => ucfirst($ticket->priority ?: 'normal'),
                'Channel' => ucfirst($ticket->input_method ?: $ticket->channel ?: '-'),
                'Ditangani Oleh' => $ticket->assignedUser?->name ?? 'Belum ditugaskan',
                'Dibuat' => $ticket->created_at->format('Y-m-d H:i:s'),
                'Diselesaikan' => $resolvedDate ?: '-',
                'Waktu Penyelesaian (Jam)' => $resolutionTime ? number_format($resolutionTime, 1) : '-',
            ]);
        }

        $writer->close();

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status');
        $category = $request->get('category');

        $query = Ticket::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->with(['user', 'assignedUser']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($category) {
            $query->where('category', $category);
        }

        $tickets = $query->get();

        $data = [
            'tickets' => $tickets,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalTickets' => $tickets->count(),
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', $data);
        
        $filename = 'tickets_report_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // Helper methods for statistics
    protected function getTotalTickets($start, $end)
    {
        return Ticket::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->count();
    }

    protected function getTicketsByStatus($start, $end)
    {
        return Ticket::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    protected function getTicketsByCategory($start, $end)
    {
        return Ticket::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');
    }

    protected function getTicketsByChannel($start, $end)
    {
        return Ticket::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel');
    }

    protected function getTicketsByPriority($start, $end)
    {
        return Ticket::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');
    }
}