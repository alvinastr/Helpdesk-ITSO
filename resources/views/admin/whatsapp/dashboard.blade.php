@extends('layouts.app-production')

@section('title', 'WhatsApp Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">📊 WhatsApp Dashboard</h2>
            <p class="text-muted small mb-0">Overview statistik tiket WhatsApp</p>
        </div>
        
        <!-- Period Filter -->
        <div class="btn-group" role="group">
            <a href="{{ route('admin.whatsapp.dashboard', ['period' => 'today']) }}" 
               class="btn btn-sm {{ $period === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                Today
            </a>
            <a href="{{ route('admin.whatsapp.dashboard', ['period' => 'week']) }}" 
               class="btn btn-sm {{ $period === 'week' ? 'btn-primary' : 'btn-outline-primary' }}">
                This Week
            </a>
            <a href="{{ route('admin.whatsapp.dashboard', ['period' => 'month']) }}" 
               class="btn btn-sm {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">
                This Month
            </a>
        </div>
    </div>

    <!-- Statistics Cards Row 1 -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Total Tickets</p>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">📋</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Open</p>
                            <h3 class="mb-0 text-info">{{ $stats['open'] }}</h3>
                        </div>
                        <div class="text-info" style="font-size: 2rem;">📬</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">In Progress</p>
                            <h3 class="mb-0 text-warning">{{ $stats['in_progress'] }}</h3>
                        </div>
                        <div class="text-warning" style="font-size: 2rem;">🔄</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Resolved</p>
                            <h3 class="mb-0 text-success">{{ $stats['resolved'] }}</h3>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">✅</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 2 -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Closed</p>
                            <h3 class="mb-0 text-secondary">{{ $stats['closed'] }}</h3>
                        </div>
                        <div class="text-secondary" style="font-size: 2rem;">🔒</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Urgent Priority</p>
                            <h3 class="mb-0 text-danger">{{ $stats['urgent'] }}</h3>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">🚨</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">High Priority</p>
                            <h3 class="mb-0 text-warning">{{ $stats['high'] }}</h3>
                        </div>
                        <div class="text-warning" style="font-size: 2rem;">⚠️</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Avg Response Time</p>
                            <h3 class="mb-0">
                                @if($avgResponseTime > 0)
                                    @if($avgResponseTime < 60)
                                        {{ $avgResponseTime }}m
                                    @elseif($avgResponseTime < 1440)
                                        {{ round($avgResponseTime / 60, 1) }}h
                                    @else
                                        {{ round($avgResponseTime / 1440, 1) }}d
                                    @endif
                                @else
                                    -
                                @endif
                            </h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">⏱️</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="mb-3">📊 KPI Performance Metrics</h5>
        </div>
        
        <div class="col-md-3">
            <div class="card border-primary border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <p class="text-muted small mb-2">⚡ Avg First Response</p>
                    <h2 class="mb-1">
                        @if($kpiMetrics['avg_frt'] && $kpiMetrics['avg_frt'] > 0)
                            @if($kpiMetrics['avg_frt'] < 60)
                                {{ round($kpiMetrics['avg_frt']) }}<small>m</small>
                            @elseif($kpiMetrics['avg_frt'] < 1440)
                                {{ round($kpiMetrics['avg_frt'] / 60, 1) }}<small>h</small>
                            @else
                                {{ round($kpiMetrics['avg_frt'] / 1440, 1) }}<small>d</small>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </h2>
                    <small class="text-muted">First Response Time</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-success border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <p class="text-muted small mb-2">✅ Avg Resolution</p>
                    <h2 class="mb-1">
                        @if($kpiMetrics['avg_rt'] && $kpiMetrics['avg_rt'] > 0)
                            @if($kpiMetrics['avg_rt'] < 60)
                                {{ round($kpiMetrics['avg_rt']) }}<small>m</small>
                            @elseif($kpiMetrics['avg_rt'] < 1440)
                                {{ round($kpiMetrics['avg_rt'] / 60, 1) }}<small>h</small>
                            @else
                                {{ round($kpiMetrics['avg_rt'] / 1440, 1) }}<small>d</small>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </h2>
                    <small class="text-muted">Resolution Time</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-info border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <p class="text-muted small mb-2">🎯 SLA Compliance</p>
                    <h2 class="mb-1 {{ $kpiMetrics['sla_compliance'] >= 80 ? 'text-success' : 'text-danger' }}">
                        {{ $kpiMetrics['sla_compliance'] }}<small>%</small>
                    </h2>
                    <small class="text-muted">Within SLA Target</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-danger border-2 shadow-sm h-100">
                <div class="card-body text-center">
                    <p class="text-muted small mb-2">⚠️ SLA Breaches</p>
                    <h2 class="mb-1 text-danger">
                        {{ $kpiMetrics['sla_breached_count'] }}
                    </h2>
                    <small class="text-muted">Exceeded SLA</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="alert alert-info mb-0">
                <small>
                    <strong>ℹ️ KPI Info:</strong> 
                    FRT = First Response Time (admin pertama kali reply) | 
                    RT = Resolution Time (total waktu hingga resolved) | 
                    SLA Targets: Urgent=30m, High=2h, Normal=8h, Low=24h
                </small>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Category Distribution -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>📊 Category Distribution</strong>
                </div>
                <div class="card-body">
                    @if($categories->count() > 0)
                        <canvas id="categoryChart" height="200"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">No tickets in this period</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>📈 Status Distribution</strong>
                </div>
                <div class="card-body">
                    @if($stats['total'] > 0)
                        <canvas id="statusChart" height="200"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">No tickets in this period</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>📝 Recent Tickets</strong>
            <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-sm btn-outline-primary">
                View All Tickets
            </a>
        </div>
        <div class="card-body p-0">
            @if($recentTickets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ticket #</th>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.whatsapp.show', $ticket->id) }}" class="text-decoration-none">
                                            <strong>{{ $ticket->ticket_number }}</strong>
                                        </a>
                                    </td>
                                    <td>
                                        <div>{{ $ticket->sender_name ?? 'Unknown' }}</div>
                                        <small class="text-muted">{{ $ticket->sender_phone }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($ticket->category) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $priorityColors = [
                                                'urgent' => 'danger',
                                                'high' => 'warning',
                                                'normal' => 'info',
                                                'low' => 'secondary'
                                            ];
                                            $color = $priorityColors[$ticket->priority] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'open' => 'info',
                                                'in_progress' => 'warning',
                                                'pending' => 'secondary',
                                                'resolved' => 'success',
                                                'closed' => 'dark'
                                            ];
                                            $color = $statusColors[$ticket->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($ticket->assignedTo)
                                            <small>{{ $ticket->assignedTo->name }}</small>
                                        @else
                                            <small class="text-muted">Unassigned</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $ticket->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp.show', $ticket->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">No recent tickets</p>
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-sm btn-primary">
                        View All Tickets
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@if($categories->count() > 0 || $stats['total'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category Chart
    @if($categories->count() > 0)
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($categories->pluck('category')->map(fn($c) => ucfirst($c))->toArray()) !!},
                datasets: [{
                    data: {!! json_encode($categories->pluck('count')->toArray()) !!},
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    @endif

    // Status Chart
    @if($stats['total'] > 0)
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    label: 'Tickets',
                    data: [
                        {{ $stats['open'] }},
                        {{ $stats['in_progress'] }},
                        {{ $stats['resolved'] }},
                        {{ $stats['closed'] }}
                    ],
                    backgroundColor: [
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endif
@endsection
