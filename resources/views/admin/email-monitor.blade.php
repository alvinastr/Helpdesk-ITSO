@extends('layouts.app')

@section('title', 'Email Auto-Fetch Monitor')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">📧 Email Auto-Fetch Monitor</h2>
            <p class="text-muted mb-0">Real-time monitoring untuk email auto-fetch system</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.email-monitor', ['period' => 'today']) }}" 
               class="btn btn-sm {{ $period == 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                Today
            </a>
            <a href="{{ route('admin.email-monitor', ['period' => 'week']) }}" 
               class="btn btn-sm {{ $period == 'week' ? 'btn-primary' : 'btn-outline-primary' }}">
                This Week
            </a>
            <a href="{{ route('admin.email-monitor', ['period' => 'month']) }}" 
               class="btn btn-sm {{ $period == 'month' ? 'btn-primary' : 'btn-outline-primary' }}">
                This Month
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-robot fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Auto-Created</p>
                            <h3 class="mb-0" id="stat-auto">{{ $stats['auto_created'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Manual Created</p>
                            <h3 class="mb-0" id="stat-manual">{{ $stats['manual_created'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="fas fa-ticket-alt fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Total Tickets</p>
                            <h3 class="mb-0" id="stat-total">{{ $stats['total'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-percentage fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Auto %</p>
                            <h3 class="mb-0" id="stat-percentage">{{ $stats['auto_percentage'] }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Fetch Button & Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">🔄 Email Fetch Status</h5>
                            <p class="text-muted mb-0 small">
                                <span id="last-fetch-time">Last fetch: Checking...</span> • 
                                Auto-fetch setiap 5 menit
                            </p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary" id="test-fetch-btn" onclick="testFetch()">
                                <i class="fas fa-sync-alt me-2"></i>Test Fetch Now
                            </button>
                        </div>
                    </div>
                    <div id="fetch-result" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">📊 Tickets Trend ({{ ucfirst($period) }})</h5>
                </div>
                <div class="card-body">
                    <canvas id="ticketsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">⚡ Response Time</h5>
                </div>
                <div class="card-body">
                    @if($responseStats['avg_response_time'] > 0)
                        <div class="text-center mb-3">
                            <h2 class="display-4 mb-0">{{ $responseStats['avg_response_time'] }}</h2>
                            <p class="text-muted">minutes (avg)</p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Fastest:</span>
                            <strong>{{ $responseStats['fastest'] }} min</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Slowest:</span>
                            <strong>{{ $responseStats['slowest'] }} min</strong>
                        </div>
                    @else
                        <p class="text-muted text-center">No response data yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Two Columns: Recent Tickets & Top Senders -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">📋 Recent Auto-Created Tickets</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>From</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="text-decoration-none">
                                            {{ $ticket->ticket_number }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($ticket->subject, 40) }}</td>
                                    <td>
                                        <small>{{ $ticket->reporter_email }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $ticket->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->status == 'open' ? 'primary' : 'success' }}">
                                            {{ ucfirst($ticket->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No auto-created tickets yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">👥 Top Email Senders</h5>
                </div>
                <div class="card-body">
                    @forelse($topSenders as $sender)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="flex-grow-1">
                            <p class="mb-0 small">{{ $sender->reporter_email }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary rounded-pill">{{ $sender->count }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center">No data yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js configuration
const ctx = document.getElementById('ticketsChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels']),
        datasets: [
            {
                label: 'Auto-Created',
                data: @json($chartData['auto']),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            },
            {
                label: 'Manual',
                data: @json($chartData['manual']),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
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

// Test fetch function
function testFetch() {
    const btn = document.getElementById('test-fetch-btn');
    const resultDiv = document.getElementById('fetch-result');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Fetching...';
    
    resultDiv.style.display = 'none';
    
    fetch('{{ route("admin.email-monitor.test-fetch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.className = 'alert alert-success mt-3';
            resultDiv.innerHTML = '<strong>✅ Success!</strong> ' + data.message;
            
            // Refresh stats
            refreshStats();
        } else {
            resultDiv.className = 'alert alert-danger mt-3';
            resultDiv.innerHTML = '<strong>❌ Error!</strong> ' + data.message;
        }
        resultDiv.style.display = 'block';
    })
    .catch(error => {
        resultDiv.className = 'alert alert-danger mt-3';
        resultDiv.innerHTML = '<strong>❌ Error!</strong> ' + error.message;
        resultDiv.style.display = 'block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Test Fetch Now';
    });
}

// Refresh statistics
function refreshStats() {
    fetch('{{ route("admin.email-monitor.live-stats") }}?period={{ $period }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('stat-auto').textContent = data.auto_created;
            document.getElementById('stat-manual').textContent = data.manual_created;
            document.getElementById('stat-total').textContent = data.total;
            document.getElementById('stat-percentage').textContent = data.auto_percentage + '%';
            
            if (data.last_fetch) {
                document.getElementById('last-fetch-time').textContent = 'Last fetch: ' + data.last_fetch;
            }
        });
}

// Auto-refresh stats every 30 seconds
setInterval(refreshStats, 30000);

// Initial load
refreshStats();
</script>
@endpush
@endsection
