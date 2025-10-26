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
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>{{ $ticket->ticket_number }} - {{ $ticket->subject }}</h5>
                                <p><strong>{{ __('app.Status') }}:</strong> @translateStatus($ticket->status)</p>
                                <p><strong>{{ __('app.Created') }}:</strong> {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->created_at) }}</p>
                                <p><strong>User:</strong> {{ $ticket->user_name }} ({{ $ticket->user_email }})</p>
                                
                                @if($ticket->status === 'pending_keluhan')
                                    <span class="badge bg-warning">{{ __('app.pending_keluhan') }}</span>
                                @endif
                                
                                @if($ticket->status === 'pending_review')
                                    <span class="badge bg-info">{{ __('app.pending_review') }}</span>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <div class="actions d-flex gap-2">
                                    <form action="/admin/tickets/{{ $ticket->id }}/approve" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">{{ __('app.Approve') }}</button>
                                    </form>
                                    
                                    <form action="/admin/tickets/{{ $ticket->id }}/reject" method="POST" class="d-inline">
                                        @csrf
                                        <div class="input-group">
                                            <input type="text" name="reason" class="form-control" placeholder="Alasan penolakan" required>
                                            <button type="submit" class="btn btn-danger">{{ __('app.Reject') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
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