@extends('layouts.app-production')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Pending Review Tickets</h2>

    @foreach($tickets as $ticket)
    <div class="card mb-3 shadow-sm" style="min-height: 320px;">
        <div class="card-body d-flex flex-column">
            <div class="row flex-grow-1">
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0">{{ $ticket->subject }}</h5>
                        <span class="badge bg-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'secondary') }}">
                            {{ strtoupper($ticket->priority) }}
                        </span>
                    </div>
                    
                    <p class="text-muted mb-2">{{ Str::limit($ticket->description, 200) }}</p>
                    
                    <!-- Badges Section -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-secondary">
                            <i class="fas fa-ticket-alt"></i> {{ $ticket->ticket_number }}
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-folder"></i> {{ $ticket->category }}
                        </span>
                        <span class="badge bg-dark">
                            <i class="fas fa-{{ $ticket->channel === 'email' ? 'envelope' : ($ticket->channel === 'whatsapp' ? 'whatsapp' : 'desktop') }}"></i> 
                            {{ ucfirst($ticket->channel) }}
                        </span>
                    </div>

                    <!-- Reporter Info (Minimal) -->
                    <div class="alert alert-light border mb-2 py-2">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Pelapor:</small>
                                <strong>{{ $ticket->reporter_name ?? $ticket->user_name }}</strong>
                                @if($ticket->reporter_nip)
                                    <span class="badge bg-secondary ms-1">{{ $ticket->reporter_nip }}</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Waktu Tunggu:</small>
                                <strong class="text-warning">
                                    <i class="fas fa-clock"></i> {{ $ticket->created_at->diffForHumans() }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <p class="mb-0 text-muted small">
                        <i class="fas fa-user"></i> <strong>User:</strong> {{ $ticket->user_name }} ({{ $ticket->user_email }})
                    </p>
                </div>
                
                <div class="col-md-4">
                    <!-- View Details - Made Prominent -->
                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-outline-primary btn-lg w-100 mb-2">
                        <i class="fas fa-eye"></i> Lihat Detail Lengkap
                    </a>
                    
                    <hr>
                    
                    <!-- Action Buttons -->
                    <div class="btn-group-vertical w-100 gap-1">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $ticket->id }}">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#revisionModal{{ $ticket->id }}">
                            <i class="fas fa-edit"></i> Request Revision
                        </button>
                        <form action="{{ route('admin.tickets.reject', $ticket) }}" method="POST" class="reject-form-inline" data-ticket-number="{{ $ticket->ticket_number }}">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal{{ $ticket->id }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.tickets.approve', $ticket) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Assign to Handler (optional)</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">-- Select Handler --</option>
                                <!-- Populate with users who have handler role -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div class="modal fade" id="revisionModal{{ $ticket->id }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.tickets.revision', $ticket) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Request Revision</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Message to User <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" required placeholder="Apa yang perlu diperbaiki/dilengkapi?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <div class="d-flex justify-content-center">
        {{ $tickets->links() }}
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reject form submissions with confirmation
    document.querySelectorAll('.reject-form-inline').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const ticketNumber = this.dataset.ticketNumber;
            const reason = prompt('Alasan penolakan ticket ' + ticketNumber + '?\n\n(Kosongkan jika tidak perlu, akan otomatis: "Ticket ditolak oleh admin")');
            
            // User pressed Cancel
            if (reason === null) {
                return false;
            }
            
            // User pressed OK (with or without text)
            // Add reason as hidden input if provided
            if (reason.trim() !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reason';
                input.value = reason;
                this.appendChild(input);
            }
            
            // Confirm the rejection
            if (confirm('Yakin ingin menolak ticket ' + ticketNumber + '?')) {
                this.submit();
            }
        });
    });
    
    // Handle modal aria-hidden for approve/revision modals
    document.querySelectorAll('[id^="approveModal"], [id^="revisionModal"]').forEach(function(modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            this.removeAttribute('aria-hidden');
        });
    });
});
</script>
@endpush
@endsection