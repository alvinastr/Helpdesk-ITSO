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
                    <h3>{{ $stats['pending_keluhan'] }}</h3>
                    <p>{{ __('app.Pending Keluhan') }}</p>
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

    <!-- Email Auto-Fetch Statistics -->
    @if(isset($emailStats) && $emailStats['last_fetch'])
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope-open-text me-2"></i>
                        Email Auto-Fetch Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Last Fetch Info -->
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Last Fetch</h6>
                                <p class="h5">{{ $emailStats['last_fetch']->fetch_started_at->diffForHumans() }}</p>
                                <small class="text-muted">
                                    {{ $emailStats['last_fetch']->fetch_started_at->format('d/m/Y H:i') }}
                                </small>
                                <br>
                                @if($emailStats['last_fetch']->status === 'completed')
                                    <span class="badge bg-success mt-2">Success</span>
                                @elseif($emailStats['last_fetch']->status === 'failed')
                                    <span class="badge bg-danger mt-2">Failed</span>
                                @else
                                    <span class="badge bg-warning mt-2">Running</span>
                                @endif
                            </div>
                        </div>

                        <!-- Last Fetch Results -->
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Last Fetch Results</h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>✅ Success:</span>
                                    <strong class="text-success">{{ $emailStats['last_fetch']->successful }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>⏭️ Duplicates:</span>
                                    <strong class="text-warning">{{ $emailStats['last_fetch']->duplicates }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>❌ Failed:</span>
                                    <strong class="text-danger">{{ $emailStats['last_fetch']->failed }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Statistics -->
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Today's Stats</h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Total Fetched:</span>
                                    <strong>{{ $emailStats['today']->total_fetched ?? 0 }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Tickets Created:</span>
                                    <strong class="text-success">{{ $emailStats['today']->total_success ?? 0 }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Fetch Runs:</span>
                                    <strong>{{ $emailStats['today']->fetch_count ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Success Rate -->
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <h6 class="text-muted">Success Rate</h6>
                                <div class="display-4 my-3">
                                    <span class="@if($emailStats['last_fetch_success_rate'] >= 80) text-success @elseif($emailStats['last_fetch_success_rate'] >= 50) text-warning @else text-danger @endif">
                                        {{ $emailStats['last_fetch_success_rate'] }}%
                                    </span>
                                </div>
                                <small class="text-muted">Last fetch performance</small>
                            </div>
                        </div>
                    </div>

                    @if($emailStats['last_fetch']->error_message)
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>Error:</strong> {{ $emailStats['last_fetch']->error_message }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

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
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-primary">
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