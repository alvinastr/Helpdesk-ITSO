@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Pending Review Tickets</h2>

    @foreach($tickets as $ticket)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>{{ $ticket->subject }}</h5>
                    <p class="text-muted">{{ Str::limit($ticket->description, 200) }}</p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-secondary">{{ $ticket->ticket_number }}</span>
                        <span class="badge bg-warning">{{ $ticket->priority }}</span>
                        <span class="badge bg-info">{{ $ticket->category }}</span>
                    </div>
                    <p class="mt-2 mb-0">
                        <strong>User:</strong> {{ $ticket->user_name }} ({{ $ticket->user_email }})
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="btn-group-vertical w-100">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $ticket->id }}">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#revisionModal{{ $ticket->id }}">
                            <i class="fas fa-edit"></i> Request Revision
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $ticket->id }}">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
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

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal{{ $ticket->id }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.tickets.reject', $ticket) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Reject Ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Mengapa ticket ditolak?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Ticket</button>
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
@endsection