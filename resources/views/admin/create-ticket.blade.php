@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-semibold">
                        <i class="fas fa-plus-circle me-2"></i>Buat Ticket Baru (Admin)
                    </h4>
                </div>

                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Terdapat kesalahan:</h6>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.tickets.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf

                        {{-- Data Pelapor --}}
                        <div class="mb-4">
                            <h5 class="text-primary border-bottom border-primary border-2 pb-2 mb-4 fw-semibold">
                                <i class="fas fa-user me-2"></i>Data Pelapor
                            </h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="reporter_nip" class="form-label fw-semibold mb-2">
                                    NIP Pelapor <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('reporter_nip') is-invalid @enderror" 
                                       id="reporter_nip" 
                                       name="reporter_nip" 
                                       value="{{ old('reporter_nip') }}" 
                                       required
                                       placeholder="Masukkan NIP pelapor">
                                @error('reporter_nip')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="reporter_name" class="form-label fw-semibold mb-2">
                                    Nama Pelapor <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('reporter_name') is-invalid @enderror" 
                                       id="reporter_name" 
                                       name="reporter_name" 
                                       value="{{ old('reporter_name') }}" 
                                       required
                                       placeholder="Masukkan nama pelapor">
                                @error('reporter_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="reporter_email" class="form-label fw-semibold mb-2">
                                    Email Pelapor
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg @error('reporter_email') is-invalid @enderror" 
                                       id="reporter_email" 
                                       name="reporter_email" 
                                       value="{{ old('reporter_email') }}"
                                       placeholder="contoh@email.com">
                                @error('reporter_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="reporter_phone" class="form-label fw-semibold mb-2">
                                    Telepon Pelapor
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('reporter_phone') is-invalid @enderror" 
                                       id="reporter_phone" 
                                       name="reporter_phone" 
                                       value="{{ old('reporter_phone') }}"
                                       placeholder="08xxxxxxxxxx">
                                @error('reporter_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-info-circle me-1"></i>Isi salah satu: Email atau Telepon
                                </small>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="reporter_department" class="form-label fw-semibold mb-2">
                                Departemen <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('reporter_department') is-invalid @enderror" 
                                   id="reporter_department" 
                                   name="reporter_department" 
                                   value="{{ old('reporter_department') }}" 
                                   required
                                   placeholder="Masukkan departemen pelapor">
                            @error('reporter_department')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Data Keluhan --}}
                        <div class="mb-4">
                            <h5 class="text-primary border-bottom border-primary border-2 pb-2 mb-4 fw-semibold">
                                <i class="fas fa-clipboard-list me-2"></i>Data Keluhan
                            </h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="input_method" class="form-label fw-semibold mb-2">
                                    Metode Input <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg @error('input_method') is-invalid @enderror" 
                                        id="input_method" 
                                        name="input_method" 
                                        required>
                                    <option value="">Pilih Metode Input</option>
                                    <option value="manual" {{ old('input_method') == 'manual' ? 'selected' : '' }}>
                                        ✍️ Manual (Input Langsung)
                                    </option>
                                    <option value="whatsapp" {{ old('input_method') == 'whatsapp' ? 'selected' : '' }}>
                                        💬 WhatsApp
                                    </option>
                                    <option value="email" {{ old('input_method') == 'email' ? 'selected' : '' }}>
                                        📧 Email
                                    </option>
                                </select>
                                @error('input_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="channel" class="form-label fw-semibold mb-2">
                                    Channel <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg @error('channel') is-invalid @enderror" 
                                        id="channel" 
                                        name="channel" 
                                        required>
                                    <option value="">📱 Pilih Channel</option>
                                    <option value="portal" {{ old('channel') == 'portal' ? 'selected' : '' }}>🌐 Portal</option>
                                    <option value="email" {{ old('channel') == 'email' ? 'selected' : '' }}>📧 Email</option>
                                    <option value="whatsapp" {{ old('channel') == 'whatsapp' ? 'selected' : '' }}>� WhatsApp</option>
                                    <option value="call" {{ old('channel') == 'call' ? 'selected' : '' }}>☎️ Telepon</option>
                                </select>
                                @error('channel')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-semibold mb-2">
                                    Prioritas
                                </label>
                                <select class="form-select form-select-lg @error('priority') is-invalid @enderror" 
                                        id="priority" 
                                        name="priority">
                                    <option value="">🎯 Pilih Prioritas</option>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>🟢 Low</option>
                                    <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>🟠 High</option>
                                    <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6" id="email-received-time-wrapper" style="display: none;">
                                <label for="email_received_at" class="form-label fw-semibold mb-2">
                                    Waktu Email Diterima <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-info ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Isi dengan tanggal dan waktu email keluhan pertama kali diterima. Data ini penting untuk KPI tracking (Response Time & Resolution Time)."></i>
                                </label>
                                <input type="datetime-local" 
                                       class="form-control form-control-lg @error('email_received_at') is-invalid @enderror" 
                                       id="email_received_at" 
                                       name="email_received_at" 
                                       value="{{ old('email_received_at') }}">
                                <small class="form-text text-muted d-block mt-2">
                                    <i class="fas fa-chart-line text-primary me-1"></i>
                                    Penting untuk perhitungan KPI: Response Time & Resolution Time
                                </small>
                                @error('email_received_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="subject" class="form-label fw-semibold mb-2">
                                Subjek <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('subject') is-invalid @enderror" 
                                   id="subject" 
                                   name="subject" 
                                   value="{{ old('subject') }}" 
                                   required
                                   placeholder="Ringkasan singkat masalah yang dilaporkan">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold mb-2">
                                Deskripsi Keluhan <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control form-control-lg @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="5" 
                                      required
                                      placeholder="Jelaskan masalah secara detail, termasuk langkah-langkah yang sudah dilakukan dan hasil yang diharapkan...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4" id="original_message_row" style="display: none;">
                            <label for="original_message" class="form-label fw-semibold mb-2">
                                <i class="fas fa-quote-left me-1"></i>Pesan Asli (dari WA/Email)
                            </label>
                            <textarea class="form-control @error('original_message') is-invalid @enderror" 
                                      id="original_message" 
                                      name="original_message" 
                                      rows="4"
                                      placeholder="Salin dan tempel pesan asli dari WhatsApp atau Email di sini...">{{ old('original_message') }}</textarea>
                            @error('original_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-5">
                            <label for="attachments" class="form-label fw-semibold mb-2">
                                <i class="fas fa-paperclip me-1"></i>Lampiran
                            </label>
                            <input type="file" 
                                   class="form-control form-control-lg @error('attachments.*') is-invalid @enderror" 
                                   id="attachments" 
                                   name="attachments[]" 
                                   multiple
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt">
                            <div class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Maksimal 5MB per file. Format yang didukung: JPG, PNG, PDF, DOC, DOCX, TXT
                            </div>
                            @error('attachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Buat Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputMethodSelect = document.getElementById('input_method');
    const channelSelect = document.getElementById('channel');
    const originalMessageRow = document.getElementById('original_message_row');
    const emailInput = document.getElementById('reporter_email');
    const phoneInput = document.getElementById('reporter_phone');
    const emailTimeWrapper = document.getElementById('email-received-time-wrapper');
    const emailTimeInput = document.getElementById('email_received_at');

    // Show/hide original message based on input method
    function toggleOriginalMessage() {
        if (inputMethodSelect.value === 'whatsapp' || inputMethodSelect.value === 'email') {
            originalMessageRow.style.display = 'block';
        } else {
            originalMessageRow.style.display = 'none';
        }
    }

    // Show/hide email received time based on channel
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

    // Auto sync channel with input method
    function syncChannel() {
        if (inputMethodSelect.value === 'whatsapp') {
            channelSelect.value = 'whatsapp';
        } else if (inputMethodSelect.value === 'email') {
            channelSelect.value = 'email';
        } else if (inputMethodSelect.value === 'manual') {
            channelSelect.value = 'portal';
        }
        toggleEmailTimeField();
    }

    // Contact validation helper
    function validateContact() {
        const hasEmail = emailInput.value.trim() !== '';
        const hasPhone = phoneInput.value.trim() !== '';
        
        if (!hasEmail && !hasPhone) {
            emailInput.setCustomValidity('Email atau telepon pelapor wajib diisi');
            phoneInput.setCustomValidity('Email atau telepon pelapor wajib diisi');
        } else {
            emailInput.setCustomValidity('');
            phoneInput.setCustomValidity('');
        }
    }

    // Event listeners
    inputMethodSelect.addEventListener('change', function() {
        toggleOriginalMessage();
        syncChannel();
    });

    channelSelect.addEventListener('change', toggleEmailTimeField);

    emailInput.addEventListener('input', validateContact);
    phoneInput.addEventListener('input', validateContact);

    // Initialize
    toggleOriginalMessage();
    toggleEmailTimeField();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection