@extends('layouts.app-production')

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
                            <strong>Dibuat:</strong> {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->created_at, 'd F Y H:i') }}<br>
                            @if($ticket->approved_at)
                                <strong>Approved:</strong> {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->approved_at, 'd F Y H:i') }}<br>
                            @endif
                            @if($ticket->closed_at)
                                <strong>Closed:</strong> {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->closed_at, 'd F Y H:i') }}<br>
                            @endif
                            @if($ticket->assignedUser)
                                <strong>Handler:</strong> {{ $ticket->assignedUser->name }}<br>
                            @endif
                        </div>
                    </div>

                    <!-- Data Pelapor (Reporter Information) - Only visible to Admin -->
                    @if(Auth::check() && Auth::user()->role === 'admin')
                        @if($ticket->reporter_name || $ticket->reporter_nip || $ticket->reporter_email || $ticket->reporter_phone)
                        <div class="alert alert-info mb-3 alert-persistent" id="reporter-info-section" style="display: block !important;">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-user-circle"></i> <strong>Data Pelapor</strong>
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    @if($ticket->reporter_name)
                                        <strong>Nama:</strong> {{ $ticket->reporter_name }}<br>
                                    @endif
                                    @if($ticket->reporter_nip)
                                        <strong>NIP:</strong> {{ $ticket->reporter_nip }}<br>
                                    @endif
                                    @if($ticket->reporter_department)
                                        <strong>Departemen:</strong> {{ $ticket->reporter_department }}<br>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    @if($ticket->reporter_position)
                                        <strong>Jabatan:</strong> {{ $ticket->reporter_position }}<br>
                                    @endif
                                    @if($ticket->reporter_email)
                                        <strong>Email:</strong> 
                                        <a href="mailto:{{ $ticket->reporter_email }}">{{ $ticket->reporter_email }}</a><br>
                                    @endif
                                    @if($ticket->reporter_phone)
                                        <strong>Telepon:</strong> 
                                        <a href="tel:{{ $ticket->reporter_phone }}">{{ $ticket->reporter_phone }}</a><br>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning mb-3 alert-persistent">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Data Pelapor:</strong> Tidak ada data pelapor untuk ticket ini.
                        </div>
                        @endif
                    @endif

                    @if($ticket->status == 'rejected')
                        <div class="alert alert-danger alert-persistent">
                            <strong>Ticket Ditolak:</strong><br>
                            {{ $ticket->rejection_reason }}
                        </div>
                    @endif

                    @if($ticket->status == 'closed' && $ticket->resolution_notes)
                        <div class="alert alert-success alert-persistent">
                            <strong>Resolution:</strong><br>
                            {{ $ticket->resolution_notes }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- KPI Metrics Card (Only show if email_received_at exists) -->
            @if($ticket->email_received_at)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>KPI Metrics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Email Received -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-envelope fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Email Diterima</small>
                                    <h6 class="mb-0">{{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->email_received_at, 'd M Y, H:i') }}</h6>
                                </div>
                            </div>
                        </div>

                        <!-- Response Time -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 text-success rounded p-3">
                                        <i class="fas fa-reply fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Response Time</small>
                                    @if($ticket->first_response_at)
                                        <h6 class="mb-1">
                                            <span class="badge {{ $ticket->isResponseTimeWithinTarget() ? 'bg-success' : 'bg-danger' }}">
                                                {{ $ticket->getResponseTimeFormatted() }}
                                            </span>
                                        </h6>
                                        <small class="{{ $ticket->isResponseTimeWithinTarget() ? 'text-success' : 'text-danger' }}">
                                            <i class="fas {{ $ticket->isResponseTimeWithinTarget() ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                            {{ $ticket->isResponseTimeWithinTarget() ? 'Memenuhi SLA' : 'Melebihi SLA' }}
                                        </small>
                                    @else
                                        <h6 class="mb-0 text-muted">Belum ada respon</h6>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Resolution Time -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-check-double fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Resolution Time</small>
                                    @if($ticket->resolved_at)
                                        <h6 class="mb-1">
                                            <span class="badge {{ $ticket->isResolutionTimeWithinTarget() ? 'bg-success' : 'bg-danger' }}">
                                                {{ $ticket->getResolutionTimeFormatted() }}
                                            </span>
                                        </h6>
                                        <small class="{{ $ticket->isResolutionTimeWithinTarget() ? 'text-success' : 'text-danger' }}">
                                            <i class="fas {{ $ticket->isResolutionTimeWithinTarget() ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                            {{ $ticket->isResolutionTimeWithinTarget() ? 'Memenuhi SLA' : 'Melebihi SLA' }}
                                        </small>
                                    @else
                                        <h6 class="mb-0 text-muted">Belum diresolve</h6>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Creation Delay -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded p-3">
                                        <i class="fas fa-hourglass-half fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Delay Pembuatan Ticket</small>
                                    @if($ticket->ticket_creation_delay_minutes)
                                        <h6 class="mb-0">
                                            <span class="badge {{ $ticket->ticket_creation_delay_minutes <= 60 ? 'bg-success' : 'bg-warning' }}">
                                                {{ $ticket->getTicketCreationDelayFormatted() }}
                                            </span>
                                        </h6>
                                    @else
                                        <h6 class="mb-0 text-muted">-</h6>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Visualization -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3"><i class="fas fa-clock me-2"></i>Timeline</h6>
                        <div class="kpi-timeline">
                            <!-- Email Received -->
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <strong class="d-block" style="color: #212529 !important;">Email Diterima</strong>
                                    <small class="text-muted">{{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->email_received_at, 'd M Y, H:i') }}</small>
                                </div>
                            </div>

                            <!-- First Response -->
                            @if($ticket->first_response_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <strong class="d-block" style="color: #212529 !important;">Respon Pertama</strong>
                                    <small class="text-muted">
                                        {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->first_response_at, 'd M Y, H:i') }}
                                        <span class="badge ms-2" style="background-color: #d1e7dd !important; color: #000000 !important; font-weight: 700 !important; font-size: 0.85rem !important; padding: 0.35em 0.65em !important; border: 1px solid #198754;">+{{ $ticket->getResponseTimeFormatted() }}</span>
                                    </small>
                                </div>
                            </div>
                            @endif

                            <!-- Resolved -->
                            @if($ticket->resolved_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <strong class="d-block" style="color: #212529 !important;">Diresolve</strong>
                                    <small class="text-muted">
                                        {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->resolved_at, 'd M Y, H:i') }}
                                        <span class="badge ms-2" style="background-color: #cfe2ff !important; color: #000000 !important; font-weight: 700 !important; font-size: 0.85rem !important; padding: 0.35em 0.65em !important; border: 1px solid #0d6efd;">+{{ $ticket->getResolutionTimeFormatted() }}</span>
                                    </small>
                                </div>
                            </div>
                            @endif

                            <!-- Ticket Created -->
                            <div class="timeline-item">
                                <div class="timeline-marker bg-secondary"></div>
                                <div class="timeline-content">
                                    <strong class="d-block" style="color: #212529 !important;">Ticket Dibuat di Sistem</strong>
                                    <small class="text-muted">
                                        {{ \App\Helpers\DateHelper::formatDateIndonesian($ticket->created_at, 'd M Y, H:i') }}
                                        @if($ticket->ticket_creation_delay_minutes)
                                            <span class="badge ms-2" style="background-color: #fff3cd !important; color: #000000 !important; font-weight: 700 !important; font-size: 0.85rem !important; padding: 0.35em 0.65em !important; border: 1px solid #ffc107;">Delay: {{ $ticket->getTicketCreationDelayFormatted() }}</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Email Content Card (jika ada email thread) --}}
            @if($ticket->email_thread && count($ticket->email_thread) > 0)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope-open-text me-2"></i>Email Thread (Rekap Data)
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($ticket->email_thread as $index => $email)
                    <div class="email-message mb-4 {{ $loop->last ? '' : 'border-bottom pb-4' }}">
                        {{-- Email Header --}}
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                @if($email['type'] === 'user_complaint')
                                    <span class="badge bg-primary mb-2">
                                        <i class="fas fa-user me-1"></i>Email dari User #{{ $email['index'] ?? $loop->iteration }}
                                    </span>
                                @elseif($email['type'] === 'admin_response')
                                    <span class="badge bg-success mb-2">
                                        <i class="fas fa-reply me-1"></i>Response Admin #{{ $email['index'] ?? $loop->iteration }}
                                    </span>
                                @elseif($email['type'] === 'user_reply')
                                    <span class="badge bg-primary mb-2">
                                        <i class="fas fa-reply me-1"></i>Reply dari User #{{ $email['index'] ?? $loop->iteration }}
                                    </span>
                                @elseif($email['type'] === 'admin_reply')
                                    <span class="badge bg-success mb-2">
                                        <i class="fas fa-reply-all me-1"></i>Reply Admin #{{ $email['index'] ?? $loop->iteration }}
                                    </span>
                                @elseif($email['type'] === 'resolution')
                                    <span class="badge bg-info mb-2">
                                        <i class="fas fa-check-double me-1"></i>Resolution #{{ $email['index'] ?? $loop->iteration }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary mb-2">
                                        <i class="fas fa-envelope me-1"></i>Email #{{ $email['index'] ?? $loop->iteration }}
                                    </span>
                                @endif
                                <div class="text-muted small">
                                    <strong>From:</strong> {{ $email['from'] ?? 'N/A' }} 
                                    @if(!empty($email['from_name']))
                                        ({{ $email['from_name'] }})
                                    @elseif(!empty($email['sender_name']))
                                        ({{ $email['sender_name'] }})
                                    @endif
                                    <br>
                                    <strong>To:</strong> {{ $email['to'] ?? 'N/A' }}<br>
                                    @if(!empty($email['cc']))
                                        <strong>CC:</strong> {{ $email['cc'] }}<br>
                                    @endif
                                    <strong>Time:</strong> {{ \Carbon\Carbon::parse($email['timestamp'])->format('d M Y, H:i') }}
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#email-{{ $index }}"
                                    aria-expanded="false">
                                <i class="fas fa-chevron-down"></i> Show/Hide
                            </button>
                        </div>
                        
                        {{-- Email Subject --}}
                        <div class="mb-2">
                            <strong>Subject:</strong> <span class="text-primary">{{ $email['subject'] ?? 'N/A' }}</span>
                        </div>
                        
                        {{-- Email Body --}}
                        <div class="collapse {{ $index === 0 ? 'show' : '' }}" id="email-{{ $index }}">
                            <div class="card card-body bg-light">
                                <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $email['body'] ?? 'No content' }}</pre>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

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
                            <small class="text-muted">{{ \App\Helpers\DateHelper::formatDateIndonesian($thread->created_at, 'd F Y H:i') }}</small>
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
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Admin Actions -->
            @if(Auth::check() && Auth::user()->role === 'admin')
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield"></i> Admin Actions</h5>
                </div>
                <div class="card-body">
                    @if(!in_array($ticket->status, ['closed', 'rejected']))
                        <!-- Update Status -->
                        <div class="mb-3">
                            <form action="{{ route('admin.tickets.update-status', $ticket) }}" method="POST">
                                @csrf
                                <label class="form-label"><strong>Update Status:</strong></label>
                                <select name="status" class="form-select mb-2" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                </select>
                                <textarea name="notes" class="form-control mb-2" placeholder="Catatan (opsional)" rows="2"></textarea>
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="fas fa-sync"></i> Update Status
                                </button>
                            </form>
                        </div>
                        <hr>
                    @endif

                    @if($ticket->status == 'resolved' || $ticket->status == 'in_progress')
                        <!-- Close Ticket -->
                        <div class="mb-3">
                            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#closeTicketModal">
                                <i class="fas fa-check-circle"></i> Close Ticket
                            </button>
                        </div>
                        <hr>
                    @endif

                    @if(!in_array($ticket->status, ['closed', 'rejected']))
                        <!-- Add Note -->
                        <div class="mb-3">
                            <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                <i class="fas fa-sticky-note"></i> Add Internal Note
                            </button>
                        </div>

                        <!-- Assign Ticket -->
                        <div class="mb-3">
                            <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#assignTicketModal">
                                <i class="fas fa-user-plus"></i> Assign to Admin
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            @endif

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

<!-- Close Ticket Modal -->
<div class="modal fade" id="closeTicketModal" tabindex="-1" aria-labelledby="closeTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.close', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="closeTicketModalLabel">
                        <i class="fas fa-check-circle"></i> Close Ticket
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Pastikan masalah sudah terselesaikan sebelum menutup ticket.
                    </div>
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label"><strong>Resolution Notes</strong> <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="4" 
                                  placeholder="Jelaskan bagaimana masalah diselesaikan... (Kosongkan jika tidak perlu, akan diisi otomatis: 'Masalah telah diselesaikan')" 
                                  minlength="10" maxlength="1000"></textarea>
                        <small class="text-muted">Optional - Minimal 10 karakter jika diisi. Jika dikosongkan, akan otomatis: "Masalah telah diselesaikan"</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Close Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.add-note', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="addNoteModalLabel">
                        <i class="fas fa-sticky-note"></i> Add Internal Note
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="message" class="form-label"><strong>Note *</strong></label>
                        <textarea class="form-control" id="message" name="message" rows="4" 
                                  placeholder="Tulis catatan internal..." 
                                  required minlength="5" maxlength="1000"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="is_internal" checked>
                        <label class="form-check-label" for="is_internal">
                            Internal note (tidak terlihat oleh user)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Simpan Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Ticket Modal -->
<div class="modal fade" id="assignTicketModal" tabindex="-1" aria-labelledby="assignTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="assignTicketModalLabel">
                        <i class="fas fa-user-plus"></i> Assign Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label"><strong>Pilih Admin *</strong></label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">-- Pilih Admin --</option>
                            @php
                                $admins = \App\Models\User::where('role', 'admin')->get();
                            @endphp
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ $ticket->assigned_to == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }} ({{ $admin->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-check"></i> Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.kpi-timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 1rem;
}

.kpi-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -26px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    padding-left: 10px;
}

.timeline-content strong {
    color: #495057;
    font-weight: 600;
}

.timeline-content small {
    font-size: 0.875rem;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .kpi-timeline::before {
        background: #495057;
    }
    
    .timeline-marker {
        border-color: #212529;
        box-shadow: 0 0 0 2px #495057;
    }
    
    .timeline-content strong {
        color: #f8f9fa;
    }
}
</style>
@endpush
@endsection
