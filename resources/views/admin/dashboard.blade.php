@extends('layouts.app-production')

@section('content')
<style>
/* Force light theme for dashboard */
.dashboard-container * {
    color-scheme: light !important;
}
.dashboard-container .card {
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #dee2e6 !important;
}
.dashboard-container .table {
    background-color: #ffffff !important;
    color: #212529 !important;
}
.dashboard-container .table thead th {
    background-color: #f8f9fa !important;
    color: #212529 !important;
}
.dashboard-container .table tbody tr {
    background-color: #ffffff !important;
    color: #212529 !important;
}
.dashboard-container .table tbody td {
    background-color: transparent !important;
    color: #212529 !important;
}
</style>

<div class="container-fluid dashboard-container">
    <h2 class="mb-4">{{ __('app.Dashboard') }} Admin</h2>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h3>{{ $stats['pending_review'] }}</h3>
                    <p>{{ __('app.Pending Review') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h3>{{ $stats['open'] }}</h3>
                    <p>{{ __('app.Open Tickets') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h3>{{ $stats['resolved'] }}</h3>
                    <p>{{ __('app.Resolved') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h3>{{ $stats['closed_today'] }}</h3>
                    <p>{{ __('app.Closed Today') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="card">
        <div class="card-header">
            <h5>{{ __('app.Recent Tickets') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID Tiket</th>
                            <th>Pengguna</th>
                            <th>Pelapor</th>
                            <th>{{ __('app.Subject') }}</th>
                            <th>{{ __('app.Status') }}</th>
                            <th>{{ __('app.Priority') }}</th>
                            <th>{{ __('app.Created') }}</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTickets as $ticket)
                        <tr>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->user_name }}</td>
                            <td>
                                @if($ticket->reporter_name)
                                    <strong>{{ $ticket->reporter_name }}</strong><br>
                                    @if($ticket->reporter_nip)
                                        <small class="text-muted">NIP: {{ $ticket->reporter_nip }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ strlen($ticket->subject) > 40 ? substr($ticket->subject, 0, 40) . '...' : $ticket->subject }}</td>
                            <td>
                                <span class="badge bg-{{ $ticket->status == 'open' ? 'primary' : 'secondary' }}">
                                    {{ $ticket->status }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $ticket->priority == 'high' ? 'danger' : 'warning' }}">
                                    {{ $ticket->priority }}
                                </span>
                            </td>
                            <td>{{ \App\Helpers\DateHelper::diffForHumansIndonesian($ticket->created_at) }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-primary">
                                    {{ __('app.View') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection