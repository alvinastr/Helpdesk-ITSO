@extends('layouts.app')

@section('title', 'KPI Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">KPI Dashboard</h1>
            <p class="text-muted">Analisis Key Performance Indicators Helpdesk</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('kpi.export', array_merge(['format' => 'csv'], $filters)) }}" 
               class="btn btn-outline-secondary">
                <i class="fas fa-download me-2"></i>Export CSV
            </a>
            <a href="{{ route('kpi.export', array_merge(['format' => 'json'], $filters)) }}" 
               class="btn btn-outline-secondary">
                <i class="fas fa-download me-2"></i>Export JSON
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('kpi.dashboard') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                               class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                               class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            <option value="Technical" {{ request('category') === 'Technical' ? 'selected' : '' }}>Technical</option>
                            <option value="Billing" {{ request('category') === 'Billing' ? 'selected' : '' }}>Billing</option>
                            <option value="General" {{ request('category') === 'General' ? 'selected' : '' }}>General</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Prioritas</label>
                        <select name="priority" class="form-select">
                            <option value="">Semua Prioritas</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <a href="{{ route('kpi.dashboard') }}" class="btn btn-secondary">Reset</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Tickets -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2 small">Total Tiket</p>
                            <h2 class="mb-0 fw-bold">{{ $summary['total_tickets'] }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-ticket-alt text-primary fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Rate -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2 small">Response Rate</p>
                            <h2 class="mb-0 fw-bold">{{ $summary['response_rate'] }}%</h2>
                            <small class="text-muted">{{ $summary['tickets_with_response'] }} tiket</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-reply text-success fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Response Time -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-2 small">Rata-rata Response Time</p>
                            <h4 class="mb-2 fw-bold">{{ $summary['avg_response_time_formatted'] }}</h4>
                            @if($summary['sla_response_compliance'] >= 80)
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-check-circle me-1"></i>SLA: {{ $summary['sla_response_compliance'] }}%
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i>SLA: {{ $summary['sla_response_compliance'] }}%
                                </span>
                            @endif
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-clock text-warning fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Resolution Time -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-2 small">Rata-rata Resolution Time</p>
                            <h4 class="mb-2 fw-bold">{{ $summary['avg_resolution_time_formatted'] }}</h4>
                            @if($summary['sla_resolution_compliance'] >= 80)
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-check-circle me-1"></i>SLA: {{ $summary['sla_resolution_compliance'] }}%
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i>SLA: {{ $summary['sla_resolution_compliance'] }}%
                                </span>
                            @endif
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-check-double text-info fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Trend Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Trend KPI</h5>
                </div>
                <div class="card-body">
                    <canvas id="kpiTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- SLA Compliance -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">SLA Compliance</h5>
                </div>
                <div class="card-body">
                    <canvas id="slaChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Tables -->
    <div class="row g-4 mb-4">
        <!-- By Priority -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">KPI Berdasarkan Prioritas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Prioritas</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Avg Response</th>
                                    <th class="text-end">Avg Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byPriority as $stat)
                                <tr>
                                    <td>
                                        @php
                                            $priorityClass = [
                                                'critical' => 'danger',
                                                'high' => 'warning',
                                                'medium' => 'info',
                                                'low' => 'secondary'
                                            ][$stat['priority']] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $priorityClass }}">
                                            {{ ucfirst($stat['priority']) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $stat['total_tickets'] }}</td>
                                    <td class="text-end">{{ $stat['avg_response_time_formatted'] }}</td>
                                    <td class="text-end">{{ $stat['avg_resolution_time_formatted'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Tidak ada data
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- By Category -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">KPI Berdasarkan Kategori</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Avg Response</th>
                                    <th class="text-end">Avg Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byCategory as $stat)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $stat['category'] }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $stat['total_tickets'] }}</td>
                                    <td class="text-end">{{ $stat['avg_response_time_formatted'] }}</td>
                                    <td class="text-end">{{ $stat['avg_resolution_time_formatted'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Tidak ada data
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tickets with KPI Issues -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Tiket dengan Masalah SLA</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Subject</th>
                            <th>Prioritas</th>
                            <th>Response Time</th>
                            <th>Resolution Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slowTickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="text-decoration-none">
                                    #{{ $ticket->id }}
                                </a>
                            </td>
                            <td>{{ Str::limit($ticket->subject, 40) }}</td>
                            <td>
                                @php
                                    $priorityClass = [
                                        'critical' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        'low' => 'secondary'
                                    ][$ticket->priority] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $priorityClass }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->response_time_minutes)
                                    @if($ticket->isResponseTimeWithinTarget())
                                        <span class="badge bg-success">
                                            {{ $ticket->getResponseTimeFormatted() }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            {{ $ticket->getResponseTimeFormatted() }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->resolution_time_minutes)
                                    @if($ticket->isResolutionTimeWithinTarget())
                                        <span class="badge bg-success">
                                            {{ $ticket->getResolutionTimeFormatted() }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            {{ $ticket->getResolutionTimeFormatted() }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">Belum resolved</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = [
                                        'open' => 'primary',
                                        'in_progress' => 'info',
                                        'pending' => 'warning',
                                        'resolved' => 'success',
                                        'closed' => 'secondary'
                                    ][$ticket->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                Tidak ada tiket dengan masalah SLA
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Trend Chart
    const trendData = @json($trends);
    const trendCtx = document.getElementById('kpiTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.period),
            datasets: [
                {
                    label: 'Total Tiket',
                    data: trendData.map(d => d.total_tickets),
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    yAxisID: 'y',
                },
                {
                    label: 'Avg Response Time (min)',
                    data: trendData.map(d => d.avg_response_time),
                    borderColor: 'rgb(255, 206, 86)',
                    backgroundColor: 'rgba(255, 206, 86, 0.1)',
                    yAxisID: 'y1',
                },
                {
                    label: 'Avg Resolution Time (min)',
                    data: trendData.map(d => d.avg_resolution_time),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Jumlah Tiket'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Waktu (menit)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // SLA Chart
    const slaCtx = document.getElementById('slaChart').getContext('2d');
    new Chart(slaCtx, {
        type: 'doughnut',
        data: {
            labels: ['Response SLA Met', 'Response SLA Missed', 'Resolution SLA Met', 'Resolution SLA Missed'],
            datasets: [{
                data: [
                    {{ $summary['sla_response_compliance'] }},
                    {{ 100 - $summary['sla_response_compliance'] }},
                    {{ $summary['sla_resolution_compliance'] }},
                    {{ 100 - $summary['sla_resolution_compliance'] }}
                ],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
</script>
@endpush
@endsection
