@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Admin Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h3>{{ $stats['pending_review'] }}</h3>
                    <p>Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h3>{{ $stats['open'] }}</h3>
                    <p>Open Tickets</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h3>{{ $stats['resolved'] }}</h3>
                    <p>Resolved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h3>{{ $stats['closed_today'] }}</h3>
                    <p>Closed Today</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Avg Resolution Time</h5>
                    <h3>{{ $stats['avg_resolution_time'] }} hours</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Customer Satisfaction</h5>
                    <h3>{{ number_format($stats['satisfaction_score'], 1) }} / 5.0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="card">
        <div class="card-header">
            <h5>Recent Tickets</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTickets as $ticket)
                        <tr>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->user_name }}</td>
                            <td>{{ Str::limit($ticket->subject, 40) }}</td>
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
                            <td>{{ $ticket->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-primary">
                                    View
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