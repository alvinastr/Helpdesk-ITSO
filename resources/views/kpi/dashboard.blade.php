@extends('layouts.app')

@section('title', 'KPI Dashboard')

@push('styles')
<style>
    .kpi-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-left: 4px solid transparent;
    }
    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }
    .kpi-card.primary { border-left-color: #0d6efd; }
    .kpi-card.success { border-left-color: #198754; }
    .kpi-card.warning { border-left-color: #ffc107; }
    .kpi-card.info { border-left-color: #0dcaf0; }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    
    .filter-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .filter-card .form-label {
        color: white;
        font-weight: 500;
    }
    
    .filter-card .form-control,
    .filter-card .form-select {
        border: 2px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.1);
        color: white;
    }
    
    .filter-card .form-control::placeholder {
        color: rgba(255,255,255,0.7);
    }
    
    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        background: rgba(255,255,255,0.2);
        border-color: white;
        color: white;
        box-shadow: 0 0 0 0.25rem rgba(255,255,255,0.25);
    }
    
    .filter-card .form-select option {
        background: #6c757d;
        color: white;
    }
    
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 1rem;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 1.2rem;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-white px-3 py-2 rounded shadow-sm">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-chart-line me-1"></i>KPI Dashboard</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold">KPI Dashboard</h1>
                <p class="text-muted mb-0">Key Performance Indicators for Helpdesk System</p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="fas fa-print me-2"></i>Print
                </button>
                <button onclick="exportToCSV()" class="btn btn-outline-success">
                    <i class="fas fa-file-csv me-2"></i>Export CSV
                </button>
            </div>
        </div>

    <!-- Filter Section -->
    <div class="card mb-4 border-0 shadow filter-card">
        <div class="card-body">
            <h5 class="mb-3">
                <i class="fas fa-filter me-2"></i>Filter Data KPI
            </h5>
            <form method="GET" action="{{ route('kpi.dashboard') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                               class="form-control" placeholder="Pilih tanggal">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-check me-1"></i>Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                               class="form-control" placeholder="Pilih tanggal">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-folder me-1"></i>Kategori
                        </label>
                        <select name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            <option value="Technical" {{ request('category') === 'Technical' ? 'selected' : '' }}>Technical</option>
                            <option value="Billing" {{ request('category') === 'Billing' ? 'selected' : '' }}>Billing</option>
                            <option value="General" {{ request('category') === 'General' ? 'selected' : '' }}>General</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-exclamation-circle me-1"></i>Prioritas
                        </label>
                        <select name="priority" class="form-select">
                            <option value="">Semua Prioritas</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <a href="{{ route('kpi.dashboard') }}" class="btn btn-light" title="Reset Filter">
                            <i class="fas fa-redo"></i>
                        </a>
                        <button type="submit" class="btn btn-light flex-fill">
                            <i class="fas fa-search me-2"></i>Terapkan
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
            <div class="card kpi-card primary h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2 small fw-semibold text-uppercase">
                                <i class="fas fa-ticket-alt me-1"></i>Total Tiket
                            </p>
                            <h2 class="mb-0 fw-bold">{{ number_format($summary['total_tickets']) }}</h2>
                            <small class="text-muted">Semua status</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10">
                            <i class="fas fa-ticket-alt text-primary fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Rate -->
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card success h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2 small fw-semibold text-uppercase">
                                <i class="fas fa-reply me-1"></i>Response Rate
                            </p>
                            <h2 class="mb-0 fw-bold">{{ number_format($summary['response_rate'], 1) }}%</h2>
                            <small class="text-muted">{{ number_format($summary['tickets_with_response']) }} dari {{ number_format($summary['total_tickets']) }} tiket</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10">
                            <i class="fas fa-reply-all text-success fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Response Time -->
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card warning h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="w-100">
                            <p class="text-muted mb-2 small fw-semibold text-uppercase">
                                <i class="fas fa-clock me-1"></i>Avg Response Time
                            </p>
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
                        <div class="stat-icon bg-warning bg-opacity-10">
                            <i class="fas fa-clock text-warning fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Resolution Time -->
        <div class="col-md-6 col-lg-3">
            <div class="card kpi-card info h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="w-100">
                            <p class="text-muted mb-2 small fw-semibold text-uppercase">
                                <i class="fas fa-check-double me-1"></i>Avg Resolution Time
                            </p>
                            <h4 class="mb-2 fw-bold">{{ $summary['avg_resolution_time_formatted'] }}</h4>
                            @if($summary['sla_resolution_compliance'] >= 80)
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-check-circle me-1"></i>SLA: {{ number_format($summary['sla_resolution_compliance'], 1) }}%
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i>SLA: {{ number_format($summary['sla_resolution_compliance'], 1) }}%
                                </span>
                            @endif
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10">
                            <i class="fas fa-check-double text-info fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="fas fa-info-circle fs-4 me-3 mt-1"></i>
            <div>
                <h6 class="alert-heading mb-2">Informasi SLA Target</h6>
                <ul class="mb-0 small">
                    <li><strong>Response Time:</strong> Target ≤ 30 menit (hijau jika compliance ≥ 80%)</li>
                    <li><strong>Resolution Time:</strong> Target ≤ 48 jam (hijau jika compliance ≥ 80%)</li>
                    <li>Data ditampilkan berdasarkan filter yang Anda terapkan</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Trend Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Trend KPI</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="kpiTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLA Compliance -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>SLA Compliance</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="slaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Tables -->
    <div class="row g-4 mb-4">
        <!-- By Priority -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>KPI Berdasarkan Prioritas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-flag me-2"></i>Prioritas</th>
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
                                    <td class="text-end fw-semibold">{{ number_format($stat['total_tickets']) }}</td>
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
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>KPI Berdasarkan Kategori</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-tag me-2"></i>Kategori</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Avg Response</th>
                                    <th class="text-end">Avg Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byCategory as $stat)
                                <tr>
                                    <td>
                                        @php
                                            $categoryClass = [
                                                'Technical' => 'primary',
                                                'Billing' => 'warning',
                                                'General' => 'info'
                                            ][$stat['category']] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $categoryClass }}">
                                            {{ $stat['category'] }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($stat['total_tickets']) }}</td>
                                    <td class="text-end">{{ $stat['avg_response_time_formatted'] }}</td>
                                    <td class="text-end">{{ $stat['avg_resolution_time_formatted'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fs-2 mb-2 d-block"></i>
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
        <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Tiket dengan Masalah SLA</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><i class="fas fa-hashtag me-2"></i>Ticket ID</th>
                            <th><i class="fas fa-envelope me-2"></i>Subject</th>
                            <th><i class="fas fa-flag me-2"></i>Prioritas</th>
                            <th><i class="fas fa-clock me-2"></i>Response Time</th>
                            <th><i class="fas fa-check-circle me-2"></i>Resolution Time</th>
                            <th><i class="fas fa-info-circle me-2"></i>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slowTickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="text-decoration-none fw-semibold">
                                    <i class="fas fa-ticket-alt me-1"></i>#{{ $ticket->id }}
                                </a>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;" title="{{ $ticket->subject }}">
                                    {{ $ticket->subject }}
                                </div>
                            </td>
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
                                        <span class="badge bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            {{ $ticket->getResponseTimeFormatted() }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">
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
                                        <span class="badge bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            {{ $ticket->getResolutionTimeFormatted() }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            {{ $ticket->getResolutionTimeFormatted() }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        Belum resolved
                                    </span>
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
                            <td colspan="6" class="text-center py-5">
                                <div class="text-success">
                                    <i class="fas fa-check-circle fs-1 mb-3 d-block"></i>
                                    <h5 class="text-muted">Semua tiket memenuhi SLA! 🎉</h5>
                                    <p class="text-muted small">Tidak ada tiket yang memiliki masalah dengan Response Time atau Resolution Time</p>
                                </div>
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
    // Chart.js Global Configuration
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 13;
    
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
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Jumlah Tiket',
                        font: {
                            weight: 'bold',
                            size: 12
                        }
                    },
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Waktu (menit)',
                        font: {
                            weight: 'bold',
                            size: 12
                        }
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });

    // SLA Chart
    const slaCtx = document.getElementById('slaChart').getContext('2d');
    new Chart(slaCtx, {
        type: 'doughnut',
        data: {
            labels: ['Response SLA ✓', 'Response SLA ✗', 'Resolution SLA ✓', 'Resolution SLA ✗'],
            datasets: [{
                data: [
                    {{ $summary['sla_response_compliance'] }},
                    {{ 100 - $summary['sla_response_compliance'] }},
                    {{ $summary['sla_resolution_compliance'] }},
                    {{ 100 - $summary['sla_resolution_compliance'] }}
                ],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(251, 146, 60, 0.8)'
                ],
                borderColor: [
                    'rgba(34, 197, 94, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(251, 146, 60, 1)'
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
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.toFixed(1) + '%';
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
