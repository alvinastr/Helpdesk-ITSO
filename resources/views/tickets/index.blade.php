@extends('layouts.app-production')

@section('content')
<style>
    /* Ticket card styles */
    .ticket-card {
        transition: all 0.2s ease;
        border: 1px solid #e9ecef;
        margin-bottom: 1rem;
    }
    
    .ticket-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        border-color: #0d6efd;
    }
    
    /* Pagination custom styles - Clean and Simple */
    .pagination-wrapper .pagination {
        margin: 0 !important;
        gap: 0.25rem !important;
    }
    
    .pagination-wrapper .pagination .page-item {
        margin: 0 !important;
    }
    
    .pagination-wrapper .pagination .page-link {
        min-width: 38px !important;
        max-width: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 0.375rem !important;
        border: 1px solid #dee2e6 !important;
        color: #0d6efd !important;
        background-color: white !important;
        font-weight: 500 !important;
        font-size: 0.875rem !important;
        transition: all 0.2s ease !important;
        padding: 0 !important;
        line-height: 1 !important;
    }
    
    /* Force SVG icons to be small and centered */
    .pagination-wrapper .pagination .page-link svg {
        width: 10px !important;
        height: 10px !important;
        max-width: 10px !important;
        max-height: 10px !important;
        display: inline-block !important;
        vertical-align: middle !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .pagination-wrapper .pagination .page-link:hover {
        background-color: #f8f9fa !important;
        border-color: #0d6efd !important;
        color: #0d6efd !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(13, 110, 253, 0.15);
    }
    
    .pagination-wrapper .pagination .page-item.active .page-link {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: white !important;
        font-weight: 600 !important;
    }
    
    .pagination-wrapper .pagination .page-item.disabled .page-link {
        opacity: 0.4 !important;
        cursor: not-allowed !important;
        background-color: white !important;
        color: #6c757d !important;
    }
    
    .pagination-wrapper .pagination .page-item.disabled .page-link:hover {
        transform: none !important;
        box-shadow: none !important;
        background-color: white !important;
    }
    
    /* Additional safety for pagination wrapper */
    .pagination-wrapper {
        margin-top: 2rem;
        padding: 1rem 0;
    }
</style>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">{{ __('app.My Tickets') }}</h2>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>{{ __('app.Create Ticket') }} (Admin)
                    </a>
                @else
                    <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>{{ __('app.Create Ticket') }}
                    </a>
                @endif
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
        <div class="card ticket-card">
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
        <div class="pagination-wrapper">
            <div class="d-flex justify-content-center">
                {{ $tickets->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    @else
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Belum ada tiket. 
            <a href="{{ route('tickets.create') }}" class="alert-link">{{ __('app.Create Ticket') }}</a>
        </div>
    @endif
</div>
@endsection