@extends('layouts.app-production')

@section('title', 'WhatsApp Bot Monitoring')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2">
                <i class="fab fa-whatsapp text-success"></i>
                WhatsApp Bot Monitoring
            </h1>
            <p class="text-muted">Real-time monitoring untuk WhatsApp Bot ITSO</p>
        </div>
    </div>

    {{-- Bot Status Cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-{{ $botStatus['online'] ? 'success' : 'danger' }} border-5">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-muted mb-2">Bot Status</h6>
                            <h3 class="mb-2">
                                <span class="badge bg-{{ $botStatus['online'] ? 'success' : 'danger' }}">
                                    {{ $botStatus['online'] ? '🟢 Online' : '🔴 Offline' }}
                                </span>
                            </h3>
                            @if($botStatus['online'])
                                <small class="text-muted">
                                    Connected as: <strong>{{ $botStatus['whatsapp_name'] }}</strong><br>
                                    State: <span class="badge bg-{{ $botStatus['whatsapp_state'] === 'connected' ? 'success' : 'warning' }}">{{ $botStatus['whatsapp_state'] }}</span><br>
                                    @if($botStatus['whatsapp_number'])
                                        Number: <span class="text-muted">+{{ $botStatus['whatsapp_number'] }}</span><br>
                                    @endif
                                    @if($botStatus['uptime'])
                                        Uptime: <span class="text-muted">{{ $botStatus['uptime'] }}</span><br>
                                    @endif
                                    <span class="text-muted">🔌 Port: localhost:3000</span>
                                </small>
                            @else
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $botStatus['error'] ?? 'Not responding' }}<br>
                                    <span class="text-muted">Expected at: localhost:3000</span><br>
                                    <span class="text-muted">💡 Tip: Jalankan bot service terlebih dahulu</span>
                                </small>
                            @endif
                        </div>
                        <div class="text-end">
                            <i class="fas fa-robot fa-3x text-{{ $botStatus['online'] ? 'success' : 'secondary' }} opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-start border-info border-5">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Queue Status</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-success mb-1">{{ $queueStats['processed'] ?? 0 }}</h4>
                            <small class="text-muted">Processed</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning mb-1">{{ $queueStats['pending'] ?? 0 }}</h4>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger mb-1">{{ $queueStats['failed'] ?? 0 }}</h4>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-start border-primary border-5">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Tickets Overview</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary mb-1">{{ $ticketsToday }}</h4>
                            <small class="text-muted">Today</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-info mb-1">{{ $ticketsWeek }}</h4>
                            <small class="text-muted">This Week</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-secondary mb-1">{{ $ticketsMonth }}</h4>
                            <small class="text-muted">This Month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Charts --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">📊 Tickets by Category (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">⚡ Tickets by Priority (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="priorityChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Tickets Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📝 Recent Tickets</h5>
                    <div>
                        <span class="badge bg-secondary me-2" id="lastUpdate">Last update: Just now</span>
                        <button class="btn btn-sm btn-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Sender</th>
                                    <th>Message</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTickets as $ticket)
                                <tr>
                                    <td><strong>{{ $ticket->ticket_number }}</strong></td>
                                    <td>
                                        <strong>{{ $ticket->sender_name ?? 'Unknown' }}</strong><br>
                                        <small class="text-muted">{{ $ticket->sender_phone }}</small>
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($ticket->original_message ?? '', 50) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($ticket->category) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->status == 'resolved' ? 'success' : ($ticket->status == 'in_progress' ? 'primary' : 'secondary') }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $ticket->created_at->format('d M Y, H:i') }}</small><br>
                                        <small class="text-muted">{{ $ticket->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No tickets found
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
</div>

@push('styles')
<style>
.border-start {
    border-left-width: 5px !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = {!! json_encode($ticketsByCategory) !!};
    
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(item => item.category.charAt(0).toUpperCase() + item.category.slice(1)),
            datasets: [{
                data: categoryData.map(item => item.count),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
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

    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    const priorityData = {!! json_encode($ticketsByPriority) !!};
    
    new Chart(priorityCtx, {
        type: 'bar',
        data: {
            labels: priorityData.map(item => item.priority.charAt(0).toUpperCase() + item.priority.slice(1)),
            datasets: [{
                label: 'Number of Tickets',
                data: priorityData.map(item => item.count),
                backgroundColor: ['#e74a3b', '#f6c23e', '#858796'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Auto-refresh every 30 seconds
    let lastUpdate = new Date();
    setInterval(updateLastUpdateTime, 1000);
    setInterval(refreshData, 30000);

    function updateLastUpdateTime() {
        const seconds = Math.floor((new Date() - lastUpdate) / 1000);
        const minutes = Math.floor(seconds / 60);
        
        let timeText;
        if (minutes === 0) {
            timeText = seconds === 0 ? 'Just now' : `${seconds}s ago`;
        } else {
            timeText = `${minutes}m ago`;
        }
        
        document.getElementById('lastUpdate').textContent = `Last update: ${timeText}`;
    }

    function refreshData() {
        const icon = document.getElementById('refreshIcon');
        icon.classList.add('fa-spin');
        
        fetch('{{ route("whatsapp.api.status") }}')
            .then(response => response.json())
            .then(data => {
                console.log('Data refreshed:', data);
                lastUpdate = new Date();
                updateLastUpdateTime();
                
                // For full refresh with new data, reload the page
                setTimeout(() => {
                    location.reload();
                }, 500);
            })
            .catch(error => {
                console.error('Error refreshing data:', error);
                icon.classList.remove('fa-spin');
            });
    }
</script>
@endpush
@endsection
