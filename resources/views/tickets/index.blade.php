@extends('layouts.app-production')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __('app.My Tickets') }}</h2>
                <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('app.Create Ticket') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('tickets.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="Cari ticket..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="pending_review" {{ request('status') == 'pending_review' ? 'selected' : '' }}>{{ __('app.pending_review') }}</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>{{ __('app.open') }}</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>{{ __('app.in_progress') }}</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>{{ __('app.resolved') }}</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>{{ __('app.closed') }}</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('app.rejected') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets List -->
    @if($tickets->count() > 0)
        @foreach($tickets as $ticket)
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5>
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-decoration-none">
                                {{ $ticket->subject }}
                            </a>
                        </h5>
                        <p class="text-muted mb-2">
                            <small>{{ Str::limit($ticket->description, 150) }}</small>
                        </p>
                        <div class="d-flex gap-2">
                            <span class="badge bg-secondary">{{ $ticket->ticket_number }}</span>
                            <span class="badge bg-info">{{ ucfirst($ticket->channel) }}</span>
                            <span class="badge bg-warning text-dark">@translatePriority($ticket->priority)</span>
                            @if($ticket->category)
                                <span class="badge bg-primary">{{ $ticket->category }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge 
                            @if($ticket->status == 'closed') bg-success
                            @elseif($ticket->status == 'rejected') bg-danger
                            @elseif($ticket->status == 'open' || $ticket->status == 'in_progress') bg-primary
                            @else bg-secondary
                            @endif
                            mb-2">
                            @translateStatus($ticket->status)
                        </span>
                        <br>
                        <small class="text-muted">{{ \App\Helpers\DateHelper::diffForHumansIndonesian($ticket->created_at) }}</small>
                        <br>
                        @if($ticket->threads()->count() > 1)
                            <small class="text-info">
                                <i class="fas fa-comments"></i> {{ $ticket->threads()->count() }} balasan
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $tickets->links() }}
        </div>
    @else
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Belum ada tiket. 
            <a href="{{ route('tickets.create') }}" class="alert-link">{{ __('app.Create Ticket') }}</a>
        </div>
    @endif
</div>
@endsection