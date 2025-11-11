@extends('layouts.app-production')

@section('title', 'WhatsApp Ticket')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">💬 WhatsApp Ticket - {{ $ticket->ticket_number }}</h2>
            <p class="text-muted mb-0">Detail ticket masuk via WhatsApp</p>
        </div>
        <div>
            <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-secondary">Back to list</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                        <h5>From: {{ $ticket->sender_name ?? $ticket->sender_phone }}</h5>
                        <p class="text-muted">Received: {{ $ticket->created_at->toDayDateTimeString() }}</p>
                        <hr>
                        <p>{{ $ticket->original_message ?? ($ticket->raw_data['body'] ?? '-') }}</p>
                    </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <strong>💬 Responses & Notes</strong>
                </div>
                <div class="card-body">
                    @if($ticket->responses && $ticket->responses->count())
                        <div class="mb-4">
                            @foreach($ticket->responses as $resp)
                                <div class="mb-3 p-3 border rounded {{ $resp->type === 'reply' ? 'bg-light' : 'bg-white' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <small class="text-muted">
                                                {{ $resp->created_at->format('d M Y, H:i') }}
                                                <span class="text-muted">• {{ $resp->created_at->diffForHumans() }}</span>
                                            </small>
                                        </div>
                                        @if($resp->type === 'reply')
                                            <span class="badge bg-success">Sent via WhatsApp</span>
                                        @elseif($resp->type === 'status_change')
                                            <span class="badge bg-info">Status Change</span>
                                        @else
                                            <span class="badge bg-secondary">Internal Note</span>
                                        @endif
                                    </div>
                                    <div class="mb-1">{{ $resp->message }}</div>
                                    <small class="text-muted">By: {{ $resp->admin->name ?? 'System' }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-4">
                            <small>📝 No responses yet. Add a response below.</small>
                        </div>
                    @endif
                    
                    <!-- Add Response Form -->
                    <form action="{{ route('admin.whatsapp.response', $ticket) }}" method="POST" id="responseForm">
                        @csrf
                        <div class="mb-3">
                            <label for="message" class="form-label small">Add Response or Note</label>
                            <textarea name="message" id="message" rows="3" class="form-control" placeholder="Type your message here..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Response Type</label>
                            <div class="form-check">
                                <input type="radio" name="type" value="internal_note" class="form-check-input" id="typeNote" checked>
                                <label class="form-check-label small" for="typeNote">
                                    📝 Internal Note (hanya admin yang bisa lihat)
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="type" value="reply" class="form-check-input" id="typeWhatsApp">
                                <label class="form-check-label small" for="typeWhatsApp">
                                    💬 Send via WhatsApp (akan dikirim ke customer)
                                </label>
                            </div>
                        </div>
                        
                        <!-- Template Quick Buttons -->
                        <div class="mb-3">
                            <label class="form-label small">📋 Quick Templates</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="useTemplate('received')">
                                    ✅ Ticket Received
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="useTemplate('in_progress')">
                                    🔄 In Progress
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="useTemplate('resolved')">
                                    ✔️ Resolved
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="useTemplate('need_info')">
                                    ❓ Need More Info
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="useTemplate('closed')">
                                    🔒 Closed
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Add Response
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Ticket Info Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>📋 Ticket Information</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Ticket Number</small>
                        <strong>{{ $ticket->ticket_number }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge 
                            @if($ticket->status === 'new') bg-primary
                            @elseif($ticket->status === 'in_progress') bg-warning
                            @elseif($ticket->status === 'resolved') bg-success
                            @elseif($ticket->status === 'closed') bg-secondary
                            @endif">
                            {{ ucfirst($ticket->status ?? 'new') }}
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Priority</small>
                        <span class="badge 
                            @if($ticket->priority === 'urgent') bg-danger
                            @elseif($ticket->priority === 'high') bg-warning
                            @elseif($ticket->priority === 'normal') bg-info
                            @else bg-secondary
                            @endif">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Category</small>
                        <strong>{{ ucfirst($ticket->category) }}</strong>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">👤 From</small>
                        <strong>{{ $ticket->sender_name ?? 'Unknown' }}</strong><br>
                        <small class="text-muted">📱 {{ $ticket->sender_phone }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">👨‍💼 Assigned To</small>
                        <strong>{{ $ticket->assignedTo->name ?? '- Not assigned -' }}</strong>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-0">
                        <small class="text-muted d-block">🕐 Created</small>
                        <strong>{{ $ticket->created_at->format('d M Y, H:i') }}</strong><br>
                        <small class="text-muted">{{ $ticket->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
            
            <!-- KPI Tracking Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <strong>⏱️ KPI & Performance Metrics</strong>
                </div>
                <div class="card-body">
                    <!-- Actual Report Time Editor -->
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="mb-2">
                            <small class="text-muted d-block">📅 Created At (Bot)</small>
                            <strong>{{ $ticket->created_at->format('d M Y H:i:s') }}</strong>
                        </div>
                        <form action="{{ route('admin.whatsapp.update-actual-time', $ticket->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <label class="form-label small"><strong>🕐 Actual Report Time</strong></label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="datetime-local" name="actual_report_time" class="form-control" 
                                       value="{{ ($ticket->actual_report_time ?? $ticket->created_at)->format('Y-m-d\TH:i') }}"
                                       max="{{ now()->format('Y-m-d\TH:i') }}">
                                <button type="submit" class="btn btn-sm btn-primary" title="Update jika user report sebelum chat bot">
                                    💾
                                </button>
                            </div>
                            <small class="text-muted">Edit jika user report via grup/telpon sebelum chat bot</small>
                        </form>
                    </div>
                    
                    <!-- KPI Metrics Display -->
                    <div class="row text-center g-2 mb-3">
                        <!-- First Response Time -->
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block mb-1">⚡ First Response</small>
                                @if($ticket->first_response_at)
                                    <h5 class="mb-0">{{ $ticket->formatted_frt }}</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">{{ $ticket->first_response_at->format('d M H:i') }}</small>
                                @else
                                    <h5 class="text-warning mb-0">Pending</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">Belum ada respon</small>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Resolution Time -->
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block mb-1">✅ Resolution</small>
                                @if($ticket->resolved_at)
                                    <h5 class="mb-0">{{ $ticket->formatted_rt }}</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">{{ $ticket->resolved_at->format('d M H:i') }}</small>
                                @else
                                    @php
                                        $elapsed = ($ticket->actual_report_time ?? $ticket->created_at)->diffInMinutes(now());
                                    @endphp
                                    <h5 class="text-info mb-0">{{ round($elapsed / 60, 1) }}h</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">Dalam progress...</small>
                                @endif
                            </div>
                        </div>
                        
                        <!-- SLA Status -->
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block mb-1">🎯 SLA Status</small>
                                @if($ticket->is_sla_breached)
                                    <h5 class="text-danger mb-0">BREACH</h5>
                                    @if($ticket->sla_breach_at)
                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $ticket->sla_breach_at->format('d M H:i') }}</small>
                                    @endif
                                @else
                                    <h5 class="text-success mb-0">ON TRACK</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">Target: {{ $ticket->sla_target }}m</small>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Handle Time -->
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block mb-1">🔧 Handle Time</small>
                                @if($ticket->handle_time)
                                    <h5 class="mb-0">{{ round($ticket->handle_time / 60, 1) }}h</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">{{ $ticket->handle_time }}m total</small>
                                @else
                                    <h5 class="text-muted mb-0">-</h5>
                                    <small class="text-muted" style="font-size: 0.7rem;">Belum ada data</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timestamps Details -->
                    <div class="small">
                        <div class="mb-1">
                            <strong>📌 First Assigned:</strong> 
                            {{ $ticket->first_assigned_at ? $ticket->first_assigned_at->format('d M Y H:i') : '-' }}
                        </div>
                        <div>
                            <strong>🔧 Work Started:</strong> 
                            {{ $ticket->work_started_at ? $ticket->work_started_at->format('d M Y H:i') : '-' }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>⚡ Actions</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.whatsapp.status', $ticket) }}" method="POST" class="mb-2">
                        @csrf
                        <label class="small text-muted">Update Status</label>
                        <select name="status" class="form-select form-select-sm mb-2">
                            <option value="new" {{ $ticket->status === 'new' ? 'selected' : '' }}>New</option>
                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Update Status</button>
                    </form>
                    
                    <hr>
                    
                    <form action="{{ route('admin.whatsapp.assign', $ticket) }}" method="POST">
                        @csrf
                        <label class="small text-muted">Assign To Admin</label>
                        <select name="admin_id" class="form-select form-select-sm mb-2">
                            <option value="">- Select Admin -</option>
                            @foreach(\App\Models\User::where('role', 'admin')->get() as $admin)
                                <option value="{{ $admin->id }}" {{ $ticket->assigned_to == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-success w-100">Assign</button>
                    </form>
                </div>
            </div>
            
            <!-- Debug Info (Collapsible) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#rawData">
                        <small>🔍 Debug Info (Click to expand)</small>
                    </a>
                </div>
                <div id="rawData" class="collapse">
                    <div class="card-body">
                        <small class="text-muted d-block mb-2">Raw WhatsApp Payload:</small>
                        <pre style="max-height:200px;overflow:auto;font-size:10px;background:#f8f9fa;padding:8px;border-radius:4px;">{{ json_encode($ticket->raw_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Template messages
const templates = {
    received: "Terima kasih telah menghubungi ITSO Helpdesk.\n\n📋 Tiket Anda: {{ $ticket->ticket_number }}\n📁 Kategori: {{ ucfirst($ticket->category) }}\n\nTim kami akan segera membantu Anda. Mohon menunggu update selanjutnya.",
    
    in_progress: "🔄 Update Tiket {{ $ticket->ticket_number }}\n\nTiket Anda sedang dalam proses penanganan oleh tim IT kami.\n\nMohon menunggu, kami akan memberikan update segera.",
    
    resolved: "✅ Tiket {{ $ticket->ticket_number }} telah selesai ditangani.\n\nTerima kasih atas kesabaran Anda. Jika masih ada kendala, silakan hubungi kami kembali.",
    
    need_info: "❓ Informasi Tambahan Diperlukan\n\nUntuk menangani tiket {{ $ticket->ticket_number }}, kami memerlukan informasi tambahan dari Anda.\n\nMohon balas pesan ini dengan informasi yang diminta.",
    
    closed: "✅ Tiket {{ $ticket->ticket_number }} telah ditutup.\n\nTerima kasih telah menggunakan layanan ITSO Helpdesk. Jika ada masalah lain, jangan ragu untuk menghubungi kami kembali."
};

// Use template function
function useTemplate(templateName) {
    const message = templates[templateName];
    if (message) {
        document.getElementById('message').value = message;
        document.getElementById('typeWhatsApp').checked = true;
        
        // Scroll to message textarea
        document.getElementById('message').scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('message').focus();
    }
}

// Send template directly (bypass form)
function sendTemplateDirect(templateName) {
    if (!confirm('Kirim template message ini ke customer?')) return;
    
    fetch('{{ route("admin.whatsapp.template", $ticket) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ template: templateName })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Template berhasil dikirim!');
            location.reload();
        } else {
            alert('❌ Gagal mengirim template: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ Error: ' + error.message);
    });
}
</script>
@endpush
@endsection
