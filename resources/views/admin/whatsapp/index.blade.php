@extends('layouts.app-production')

@section('title', 'WhatsApp Tickets')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">💬 WhatsApp Tickets</h2>
            <p class="text-muted mb-0">Daftar ticket yang masuk via WhatsApp</p>
        </div>
        <div>
            <a href="{{ route('admin.whatsapp.dashboard') }}" class="btn btn-primary">
                <i class="fas fa-chart-bar"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-1">Total</h5>
                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-1">Open</h5>
                    <h2 class="mb-0 text-primary">{{ $stats['open'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-1">In Progress</h5>
                    <h2 class="mb-0 text-warning">{{ $stats['in_progress'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-1">Resolved</h5>
                    <h2 class="mb-0 text-success">{{ $stats['resolved'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-1">Urgent</h5>
                    <h2 class="mb-0 text-danger">{{ $stats['urgent'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-1">Today</h5>
                    <h2 class="mb-0 text-info">{{ $stats['today'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.whatsapp.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search ticket/phone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="">All Priority</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All Category</option>
                        <option value="network" {{ request('category') == 'network' ? 'selected' : '' }}>Network</option>
                        <option value="hardware" {{ request('category') == 'hardware' ? 'selected' : '' }}>Hardware</option>
                        <option value="software" {{ request('category') == 'software' ? 'selected' : '' }}>Software</option>
                        <option value="account" {{ request('category') == 'account' ? 'selected' : '' }}>Account</option>
                        <option value="email" {{ request('category') == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="security" {{ request('category') == 'security' ? 'selected' : '' }}>Security</option>
                        <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket #</th>
                            <th>Sender</th>
                            <th>Subject / Preview</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Received</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $t)
                        <tr>
                            <td>{{ $loop->iteration + (($tickets->currentPage()-1) * $tickets->perPage()) }}</td>
                            <td>
                                <a href="{{ route('admin.whatsapp.show', $t->id) }}" class="text-decoration-none fw-bold">
                                    {{ $t->ticket_number }}
                                </a>
                            </td>
                            <td>
                                <div class="small">
                                    <strong>{{ $t->sender_name ?? 'Unknown' }}</strong><br>
                                    <span class="text-muted">{{ $t->sender_phone }}</span>
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($t->original_message ?? ($t->raw_data['body'] ?? ''), 50) }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($t->category) }}</span>
                            </td>
                            <td>
                                @if($t->priority === 'urgent')
                                    <span class="badge bg-danger">🔴 Urgent</span>
                                @elseif($t->priority === 'high')
                                    <span class="badge bg-warning">🟡 High</span>
                                @else
                                    <span class="badge bg-success">🟢 Normal</span>
                                @endif
                            </td>
                            <td>
                                @if($t->status === 'open')
                                    <span class="badge bg-primary">Open</span>
                                @elseif($t->status === 'in_progress')
                                    <span class="badge bg-warning">In Progress</span>
                                @elseif($t->status === 'resolved')
                                    <span class="badge bg-success">Resolved</span>
                                @elseif($t->status === 'closed')
                                    <span class="badge bg-secondary">Closed</span>
                                @else
                                    <span class="badge bg-info">{{ ucfirst($t->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($t->assignedTo)
                                    <span class="badge bg-secondary">{{ $t->assignedTo->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="small">{{ $t->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.whatsapp.show', $t->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No WhatsApp tickets yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
