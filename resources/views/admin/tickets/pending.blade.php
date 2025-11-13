@extends('layouts.app-production')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>{{ __('app.Pending Review') }} - Admin</h2>
        </div>
    </div>
    
    <div class="pending-tickets">
        @if($tickets->count() > 0)
            @foreach($tickets as $ticket)
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Header with Priority Badge -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0">{{ $ticket->subject }}</h5>
                                    <span class="badge bg-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'secondary') }}">
                                        {{ strtoupper($ticket->priority ?? 'MEDIUM') }}
                                    </span>
                                </div>
                                
                                <!-- Badges Section -->
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-ticket-alt"></i> {{ $ticket->ticket_number }}
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="fas fa-folder"></i> {{ $ticket->category ?? 'General' }}
                                    </span>
                                    <span class="badge bg-dark">
                                        <i class="fas fa-{{ $ticket->channel === 'email' ? 'envelope' : ($ticket->channel === 'whatsapp' ? 'whatsapp' : 'desktop') }}"></i> 
                                        {{ ucfirst($ticket->channel ?? 'portal') }}
                                    </span>
                                    @if($ticket->status === 'pending_keluhan')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock"></i> {{ __('app.pending_keluhan') }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Reporter Info (Minimal) - PERSISTENT -->
                                <div class="alert alert-light border mb-2 py-2 alert-persistent">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-user-circle"></i> Pelapor:
                                            </small>
                                            <strong>{{ $ticket->reporter_name ?? $ticket->user_name }}</strong>
                                            @if($ticket->reporter_nip)
                                                <span class="badge bg-secondary ms-1">{{ $ticket->reporter_nip }}</span>
                                            @endif
                                            @if($ticket->reporter_department)
                                                <br><small class="text-muted">{{ $ticket->reporter_department }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-clock"></i> Waktu Tunggu:
                                            </small>
                                            <strong class="text-warning">
                                                {{ $ticket->created_at->diffForHumans() }}
                                            </strong>
                                            <br><small class="text-muted">{{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->created_at) }}</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <p class="mb-0 text-muted small">
                                    <i class="fas fa-user"></i> <strong>User:</strong> {{ $ticket->user_name }} ({{ $ticket->user_email }})
                                </p>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- View Details Button - Prominent -->
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-outline-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-eye"></i> Lihat Detail Lengkap
                                </a>
                                
                                <!-- Action Buttons -->
                                <div class="d-flex flex-column gap-2">
                                    <form action="{{ route('admin.tickets.approve', $ticket) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-check"></i> {{ __('app.Approve') }}
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $ticket->id }}">
                                        <i class="fas fa-times"></i> {{ __('app.Reject') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal{{ $ticket->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.tickets.reject', $ticket) }}" method="POST">
                                @csrf
                                <div class="modal-header bg-white border-bottom">
                                    <h5 class="modal-title text-dark">
                                        <i class="fas fa-times-circle text-danger"></i> Tolak Tiket
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body bg-white">
                                    <div class="mb-3">
                                        <label class="form-label text-dark">Tiket: <strong>{{ $ticket->ticket_number }}</strong></label>
                                        <p class="text-muted small">{{ $ticket->subject }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label for="reason{{ $ticket->id }}" class="form-label text-dark">
                                            Alasan Penolakan <span class="text-muted">(Optional)</span>
                                        </label>
                                        <textarea 
                                            id="reason{{ $ticket->id }}"
                                            name="reason" 
                                            class="form-control" 
                                            rows="4" 
                                            placeholder="Jelaskan alasan penolakan tiket ini... (Kosongkan jika tidak perlu, akan diisi otomatis: 'Ticket ditolak oleh admin')"
                                        ></textarea>
                                        <small class="text-muted d-block mt-1">Optional - Jika dikosongkan, akan otomatis: "Ticket ditolak oleh admin"</small>
                                        <small class="text-muted d-block">Alasan ini akan dikirim ke user via email</small>
                                    </div>
                                </div>
                                <div class="modal-footer bg-white border-top">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Batal
                                    </button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-check"></i> Ya, Tolak Tiket
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> Tidak ada tiket yang menunggu review.
            </div>
        @endif
    </div>
    
    {{ $tickets->links() }}
</div>
@endsection