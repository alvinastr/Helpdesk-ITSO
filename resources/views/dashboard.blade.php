@extends('layouts.app-production')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        @if(auth()->user()->role === 'admin')
                            - Administrator
                        @endif
                    </h5>
                    @if(auth()->user()->role !== 'admin')
                        <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Ticket
                        </a>
                    @endif
                </div>

                <div class="card-body">
                    @if(auth()->user()->role === 'admin')
                        <!-- Admin Dashboard -->
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">{{ $stats['pending_review'] ?? 0 }}</h4>
                                                <p class="mb-0">Pending Review</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">{{ $stats['open'] ?? 0 }}</h4>
                                                <p class="mb-0">Open Tickets</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-folder-open fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">{{ $stats['resolved'] ?? 0 }}</h4>
                                                <p class="mb-0">Resolved</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-secondary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">{{ $stats['closed_today'] ?? 0 }}</h4>
                                                <p class="mb-0">Closed Today</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-archive fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Quick Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('admin.pending-review') }}" class="btn btn-outline-warning">
                                                <i class="fas fa-clock me-1"></i>Review Pending Tickets
                                            </a>
                                            <a href="{{ route('admin.reports') }}" class="btn btn-outline-info">
                                                <i class="fas fa-chart-bar me-1"></i>View Reports
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">System Stats</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1">
                                            <strong>Avg Resolution Time:</strong> 
                                            {{ number_format($stats['avg_resolution_time'] ?? 0, 1) }} hours
                                        </p>
                                        <p class="mb-0">
                                            <strong>Satisfaction Score:</strong> 
                                            {{ number_format($stats['satisfaction_score'] ?? 0, 1) }}/5
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($recentTickets) && $recentTickets->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Recent Tickets</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Ticket #</th>
                                                        <th>User</th>
                                                        <th>Subject</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentTickets as $ticket)
                                                    <tr>
                                                        <td>{{ $ticket->ticket_number }}</td>
                                                        <td>{{ $ticket->user_name }}</td>
                                                        <td>{{ Str::limit($ticket->subject, 40) }}</td>
                                                        <td>
                                                            <span class="badge ticket-status status-{{ $ticket->status }}">
                                                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    @else
                        <!-- User Dashboard -->
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-primary">{{ $stats['total_tickets'] ?? 0 }}</h3>
                                        <p class="mb-0">Total Tickets</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-warning">{{ $stats['open_tickets'] ?? 0 }}</h3>
                                        <p class="mb-0">Open Tickets</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-success">{{ $stats['closed_tickets'] ?? 0 }}</h3>
                                        <p class="mb-0">Resolved</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-info">{{ $stats['pending_review'] ?? 0 }}</h3>
                                        <p class="mb-0">Pending Review</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($recent_tickets) && $recent_tickets->count() > 0)
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">My Recent Tickets</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Ticket #</th>
                                                        <th>Subject</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recent_tickets as $ticket)
                                                    <tr>
                                                        <td>{{ $ticket->ticket_number }}</td>
                                                        <td>{{ Str::limit($ticket->subject, 50) }}</td>
                                                        <td>
                                                            <span class="badge ticket-status status-{{ $ticket->status }}">
                                                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                                        <td>
                                                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center">
                                            <a href="{{ route('tickets.index') }}" class="btn btn-outline-primary">
                                                View All Tickets
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                        <h5>No Tickets Yet</h5>
                                        <p class="text-muted">You haven't created any support tickets yet.</p>
                                        <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Create Your First Ticket
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection