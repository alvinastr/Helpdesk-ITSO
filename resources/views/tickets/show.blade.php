@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <!-- Ticket Header -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ $ticket->subject }}</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
                            <strong>Status:</strong> 
                            <span class="badge 
                                @if($ticket->status == 'closed') bg-success
                                @elseif($ticket->status == 'rejected') bg-danger
                                @elseif($ticket->status == 'open' || $ticket->status == 'in_progress') bg-primary
                                @else bg-secondary
                                @endif">
                                {{ strtoupper(str_replace('_', ' ', $ticket->status)) }}
                            </span><br>
                            <strong>Kategori:</strong> {{ $ticket->category ?? '-' }}<br>
                            <strong>Priority:</strong> 
                            <span class="badge bg-warning text-dark">{{ strtoupper($ticket->priority) }}</span>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Dibuat:</strong> {{ $ticket->created_at->format('d M Y H:i') }}<br>
                            @if($ticket->approved_at)
                                <strong>Approved:</strong> {{ $ticket->approved_at->format('d M Y H:i') }}<br>
                            @endif
                            @if($ticket->closed_at)
                                <strong>Closed:</strong> {{ $ticket->closed_at->format('d M Y H:i') }}<br>
                            @endif
                            @if($ticket->assignedUser)
                                <strong>Handler:</strong> {{ $ticket->assignedUser->name }}<br>
                            @endif
                        </div>
                    </div>

                    @if($ticket->status == 'rejected')
                        <div class="alert alert-danger">
                            <strong>Ticket Ditolak:</strong><br>
                            {{ $ticket->rejection_reason }}
                        </div>
                    @endif

                    @if($ticket->status == 'closed' && $ticket->resolution_notes)
                        <div class="alert alert-success">
                            <strong>Resolution:</strong><br>
                            {{ $ticket->resolution_notes }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Thread Conversation -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Conversation Thread</h5>
                </div>
                <div class="card-body">
                    @foreach($ticket->threads as $thread)
                    <div class="mb-3 p-3 border rounded 
                        @if($thread->sender_type == 'admin') bg-light 
                        @elseif($thread->sender_type == 'system') bg-info bg-opacity-10
                        @endif">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>
                                @if($thread->sender_type == 'system')
                                    <i class="fas fa-robot text-info"></i>
                                @elseif($thread->sender_type == 'admin')
                                    <i class="fas fa-user-shield text-primary"></i>
                                @else
                                    <i class="fas fa-user text-secondary"></i>
                                @endif
                                {{ $thread->sender_name }}
                                <span class="badge bg-secondary">{{ ucfirst($thread->message_type) }}</span>
                            </strong>
                            <small class="text-muted">{{ $thread->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <div>{{ $thread->message }}</div>
                        
                        @if($thread->attachments)
                            <div class="mt-2">
                                <strong>Attachments:</strong>
                                @foreach($thread->attachments as $attachment)
                                    <a href="{{ Storage::url($attachment['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-paperclip"></i> {{ $attachment['filename'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Reply Form -->
            @if(!in_array($ticket->status, ['closed', 'rejected']))
            <div class="card">
                <div class="card-header">
                    <h5>Add Reply</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                      name="message" 
                                      rows="4" 
                                      placeholder="Tulis reply Anda..."
                                      required></textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Attachments (optional)</label>
                            <input type="file" class="form-control" name="attachments[]" multiple>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-reply"></i> Send Reply
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Feedback Form -->
            @if($ticket->status == 'closed' && !$ticket->rating)
            <div class="card mt-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Beri Rating & Feedback</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('tickets.feedback', $ticket) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Rating <span class="text-danger">*</span></label>
                            <div class="rating">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}" required>
                                    <label for="star{{ $i }}">⭐</label>
                                @endfor
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label>Feedback (optional)</label>
                            <textarea class="form-control" name="feedback" rows="3" placeholder="Bagaimana pengalaman Anda?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
            @endif

            @if($ticket->rating)
            <div class="alert alert-info mt-3">
                <strong>Your Rating:</strong> 
                @for($i = 1; $i <= $ticket->rating; $i++)
                    ⭐
                @endfor
                ({{ $ticket->rating }}/5)
                @if($ticket->feedback)
                    <br><strong>Feedback:</strong> {{ $ticket->feedback }}
                @endif
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Status History</h5>
                </div>
                <div class="card-body">
                    @foreach($ticket->statusHistories as $history)
                    <div class="mb-2">
                        <small class="text-muted">{{ $history->created_at->format('d M Y H:i') }}</small><br>
                        <span class="badge bg-secondary">{{ $history->old_status }}</span> 
                        <i class="fas fa-arrow-right"></i> 
                        <span class="badge bg-primary">{{ $history->new_status }}</span>
                        @if($history->notes)
                            <br><small>{{ $history->notes }}</small>
                        @endif
                    </div>
                    <hr>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
