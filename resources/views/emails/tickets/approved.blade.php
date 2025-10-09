@component('mail::message')
    # Ticket Approved ✅

    Halo **{{ $ticket->user_name }}**,

    Kabar baik! Ticket Anda telah disetujui dan sedang ditangani.

    **Detail Ticket:**
    - Ticket ID: {{ $ticket->ticket_number }}
    - Subject: {{ $ticket->subject }}
    - Status: OPEN
    - Category: {{ $ticket->category }}
    - Priority: {{ strtoupper($ticket->priority) }}
    @if ($ticket->assignedUser)
        - Handler: {{ $ticket->assignedUser->name }}
    @endif

    Estimasi penyelesaian: 2-3 hari kerja (tergantung kompleksitas)

    Anda akan mendapat update saat ada perkembangan.

    @component('mail::button', ['url' => route('tickets.show', $ticket)])
        View Ticket
    @endcomponent

    Terima kasih,<br>
    {{ config('app.name') }} Support Team
@endcomponent alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-group mb-3">
        <label for="subject">Subjek <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject"
            value="{{ old('subject') }}" placeholder="Ringkasan masalah Anda" required>
        @error('subject')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group mb-3">
        <label for="description">Deskripsi Keluhan <span class="text-danger">*</span></label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
            rows="5" placeholder="Jelaskan masalah Anda secara detail..." required>{{ old('description') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Minimal 10 karakter</small>
    </div>

    <div class="form-group mb-3">
        <label for="user_phone">No. WhatsApp (Opsional)</label>
        <input type="text" class="form-control @error('user_phone') is-invalid @enderror" id="user_phone"
            name="user_phone" value="{{ old('user_phone') }}" placeholder="08xx-xxxx-xxxx">
        @error('user_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Untuk notifikasi via WhatsApp</small>
    </div>

    <div class="form-group mb-3">
        <label for="attachments">Lampiran (Opsional)</label>
        <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" id="attachments"
            name="attachments[]" multiple>
        @error('attachments.*')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Max 5MB per file. Format: jpg, png, pdf, doc</small>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Submit Ticket
        </button>
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>
</form>
</div>
</div>
</div>
</div>
</div>
@endsection
