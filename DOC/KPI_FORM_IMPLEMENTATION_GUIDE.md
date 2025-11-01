# Panduan Implementasi Form Email Received Time

## Update Form Pembuatan Ticket Admin

Untuk menerapkan KPI system dengan benar, perlu menambahkan field `email_received_at` pada form pembuatan ticket oleh admin.

## Contoh Implementasi

### 1. Blade Template (views/admin/tickets/create.blade.php)

Tambahkan field ini di form:

```blade
<div class="row">
    <!-- Channel -->
    <div class="col-md-6 mb-3">
        <label for="channel" class="form-label">Channel <span class="text-danger">*</span></label>
        <select class="form-control @error('channel') is-invalid @enderror" 
                id="channel" 
                name="channel" 
                required>
            <option value="">Pilih Channel</option>
            <option value="email" {{ old('channel') === 'email' ? 'selected' : '' }}>Email</option>
            <option value="whatsapp" {{ old('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            <option value="call" {{ old('channel') === 'call' ? 'selected' : '' }}>Telepon</option>
            <option value="portal" {{ old('channel') === 'portal' ? 'selected' : '' }}>Portal</option>
        </select>
        @error('channel')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Email Received Time (BARU) -->
    <div class="col-md-6 mb-3" id="email-received-time-wrapper" style="display: none;">
        <label for="email_received_at" class="form-label">
            Waktu Email Diterima <span class="text-danger">*</span>
            <i class="fas fa-info-circle text-info" 
               data-bs-toggle="tooltip" 
               title="Isi dengan tanggal dan waktu email keluhan pertama kali diterima. Data ini penting untuk KPI tracking."></i>
        </label>
        <input type="datetime-local" 
               class="form-control @error('email_received_at') is-invalid @enderror" 
               id="email_received_at" 
               name="email_received_at" 
               value="{{ old('email_received_at') }}">
        <small class="form-text text-muted">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            Penting untuk perhitungan KPI (Response Time, Resolution Time)
        </small>
        @error('email_received_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- Script untuk show/hide berdasarkan channel -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const channelSelect = document.getElementById('channel');
    const emailTimeWrapper = document.getElementById('email-received-time-wrapper');
    const emailTimeInput = document.getElementById('email_received_at');

    function toggleEmailTimeField() {
        if (channelSelect.value === 'email') {
            emailTimeWrapper.style.display = 'block';
            emailTimeInput.required = true;
        } else {
            emailTimeWrapper.style.display = 'none';
            emailTimeInput.required = false;
            emailTimeInput.value = '';
        }
    }

    // Initial check
    toggleEmailTimeField();

    // Listen to changes
    channelSelect.addEventListener('change', toggleEmailTimeField);

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
```

### 2. Validation (AdminTicketController.php)

Tambahkan validation rules:

```php
public function store(Request $request)
{
    $rules = [
        'reporter_nip' => 'required|string|max:50',
        'reporter_name' => 'required|string|max:255',
        'reporter_email' => 'nullable|email',
        'reporter_phone' => 'required|string|max:20',
        'reporter_department' => 'required|string|max:100',
        'channel' => 'required|in:email,whatsapp,call,portal',
        'subject' => 'required|string|max:255',
        'description' => 'required|string',
        'category' => 'nullable|string',
        'priority' => 'required|in:low,medium,high,critical',
        
        // BARU: Validation untuk email_received_at
        'email_received_at' => 'nullable|date|before_or_equal:now',
    ];

    // Make email_received_at required if channel is email
    if ($request->input('channel') === 'email') {
        $rules['email_received_at'] = 'required|date|before_or_equal:now';
    }

    $validated = $request->validate($rules, [
        'email_received_at.required' => 'Waktu email diterima wajib diisi untuk channel email',
        'email_received_at.date' => 'Format waktu tidak valid',
        'email_received_at.before_or_equal' => 'Waktu email tidak boleh di masa depan',
    ]);

    // Create ticket with KPI tracking
    $ticket = $this->ticketService->createTicketByAdmin([
        'reporter_nip' => $validated['reporter_nip'],
        'reporter_name' => $validated['reporter_name'],
        'reporter_email' => $validated['reporter_email'],
        'reporter_phone' => $validated['reporter_phone'],
        'reporter_department' => $validated['reporter_department'],
        'channel' => $validated['channel'],
        'input_method' => 'manual',
        'subject' => $validated['subject'],
        'description' => $validated['description'],
        'category' => $validated['category'],
        'priority' => $validated['priority'],
        'created_by_admin' => Auth::id(),
        
        // BARU: Pass email_received_at untuk KPI
        'email_received_at' => $validated['email_received_at'] ?? null,
    ]);

    return redirect()
        ->route('admin.tickets.show', $ticket->id)
        ->with('success', 'Ticket berhasil dibuat dengan KPI tracking');
}
```

### 3. Display KPI Info di Detail Ticket

Tambahkan section KPI di view detail ticket:

```blade
@if($ticket->email_received_at)
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>KPI Metrics</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Email Received -->
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-envelope text-primary fa-2x mr-3"></i>
                    <div>
                        <small class="text-muted">Email Diterima</small>
                        <p class="mb-0 font-weight-bold">
                            {{ $ticket->email_received_at->format('d M Y, H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Response Time -->
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-reply text-success fa-2x mr-3"></i>
                    <div>
                        <small class="text-muted">Response Time</small>
                        <p class="mb-0 font-weight-bold">
                            @if($ticket->first_response_at)
                                <span class="badge badge-{{ $ticket->isResponseTimeWithinTarget() ? 'success' : 'danger' }}">
                                    {{ $ticket->getResponseTimeFormatted() }}
                                </span>
                                @if($ticket->isResponseTimeWithinTarget())
                                    <small class="text-success"><i class="fas fa-check-circle"></i> Memenuhi SLA</small>
                                @else
                                    <small class="text-danger"><i class="fas fa-exclamation-circle"></i> Melebihi SLA</small>
                                @endif
                            @else
                                <span class="text-muted">Belum ada respon</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resolution Time -->
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-double text-info fa-2x mr-3"></i>
                    <div>
                        <small class="text-muted">Resolution Time</small>
                        <p class="mb-0 font-weight-bold">
                            @if($ticket->resolved_at)
                                <span class="badge badge-{{ $ticket->isResolutionTimeWithinTarget() ? 'success' : 'danger' }}">
                                    {{ $ticket->getResolutionTimeFormatted() }}
                                </span>
                                @if($ticket->isResolutionTimeWithinTarget())
                                    <small class="text-success"><i class="fas fa-check-circle"></i> Memenuhi SLA</small>
                                @else
                                    <small class="text-danger"><i class="fas fa-exclamation-circle"></i> Melebihi SLA</small>
                                @endif
                            @else
                                <span class="text-muted">Belum diresolve</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ticket Creation Delay -->
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hourglass-half text-warning fa-2x mr-3"></i>
                    <div>
                        <small class="text-muted">Delay Pembuatan Ticket</small>
                        <p class="mb-0 font-weight-bold">
                            @if($ticket->ticket_creation_delay_minutes)
                                <span class="badge badge-{{ $ticket->ticket_creation_delay_minutes <= 60 ? 'success' : 'warning' }}">
                                    {{ $ticket->getTicketCreationDelayFormatted() }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Visualization -->
        <div class="mt-4">
            <h6 class="font-weight-bold mb-3">Timeline</h6>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary"></div>
                    <div class="timeline-content">
                        <strong>Email Diterima</strong>
                        <p class="text-muted mb-0">{{ $ticket->email_received_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>

                @if($ticket->first_response_at)
                <div class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                        <strong>Respon Pertama</strong>
                        <p class="text-muted mb-0">
                            {{ $ticket->first_response_at->format('d M Y, H:i') }}
                            <span class="badge badge-info ml-2">+{{ $ticket->getResponseTimeFormatted() }}</span>
                        </p>
                    </div>
                </div>
                @endif

                @if($ticket->resolved_at)
                <div class="timeline-item">
                    <div class="timeline-marker bg-info"></div>
                    <div class="timeline-content">
                        <strong>Diresolve</strong>
                        <p class="text-muted mb-0">
                            {{ $ticket->resolved_at->format('d M Y, H:i') }}
                            <span class="badge badge-info ml-2">+{{ $ticket->getResolutionTimeFormatted() }}</span>
                        </p>
                    </div>
                </div>
                @endif

                <div class="timeline-item">
                    <div class="timeline-marker bg-secondary"></div>
                    <div class="timeline-content">
                        <strong>Ticket Dibuat</strong>
                        <p class="text-muted mb-0">
                            {{ $ticket->created_at->format('d M Y, H:i') }}
                            @if($ticket->ticket_creation_delay_minutes)
                                <span class="badge badge-warning ml-2">Delay: {{ $ticket->getTicketCreationDelayFormatted() }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -26px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #e0e0e0;
}

.timeline-content {
    padding-left: 10px;
}
</style>
```

## Tips Penggunaan

1. **Untuk Ticket dari Email**
   - Selalu pilih channel "Email"
   - Field "Waktu Email Diterima" akan muncul otomatis
   - Isi dengan timestamp dari email header

2. **Untuk Channel Lain**
   - Field "Waktu Email Diterima" tidak ditampilkan
   - KPI tracking akan menggunakan created_at sebagai baseline

3. **Format DateTime-Local**
   - Format input: `YYYY-MM-DDThh:mm`
   - Contoh: `2025-10-21T13:00`

4. **Validasi**
   - Waktu tidak boleh di masa depan
   - Wajib diisi jika channel = email
   - Optional untuk channel lain

## Quick Copy: Datetime from Email Header

Jika menggunakan Gmail/Outlook, copy timestamp dengan cara:
1. Buka email → Show Original/View Source
2. Cari header "Date:"
3. Convert ke format datetime-local di browser

Atau gunakan JavaScript helper:

```javascript
// Helper untuk quick-fill email received time
function quickFillEmailTime(emailDateString) {
    // Parse email date (RFC 2822 format)
    const date = new Date(emailDateString);
    
    // Convert to datetime-local format
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    const datetimeLocal = `${year}-${month}-${day}T${hours}:${minutes}`;
    
    document.getElementById('email_received_at').value = datetimeLocal;
}

// Contoh penggunaan:
// quickFillEmailTime('Mon, 21 Oct 2025 13:00:00 +0700');
```

---

**Last Updated**: 29 Oktober 2025
