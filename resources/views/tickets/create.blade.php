@extends('layouts.app-production')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0 fw-semibold">
                        <i class="fas fa-plus-circle me-2"></i>{{ __('app.Create Ticket') }}
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

                    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        
                        {{-- Data Pelapor --}}
                        <div class="mb-4">
                            <h5 class="text-success border-bottom border-success border-2 pb-2 mb-4 fw-semibold">
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
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i>Isi salah satu: Email atau Telepon
                                </small>
                            </div>
                        </div>

                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label for="reporter_position" class="form-label fw-semibold mb-2">
                                    Jabatan/Posisi
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('reporter_position') is-invalid @enderror" 
                                       id="reporter_position" 
                                       name="reporter_position" 
                                       value="{{ old('reporter_position') }}"
                                       placeholder="Masukkan jabatan/posisi">
                                @error('reporter_position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-5">
                            <h5 class="text-success border-bottom border-success border-2 pb-2 mb-4 fw-semibold">
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
                                        Manual (Input Langsung)
                                    </option>
                                    <option value="whatsapp" {{ old('input_method') == 'whatsapp' ? 'selected' : '' }}>
                                        WhatsApp
                                    </option>
                                    <option value="email" {{ old('input_method') == 'email' ? 'selected' : '' }}>
                                        Email
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
                                    <option value="">Pilih Channel</option>
                                    <option value="portal" {{ old('channel') == 'portal' ? 'selected' : '' }}>Portal</option>
                                    <option value="email" {{ old('channel') == 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="whatsapp" {{ old('channel') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                    <option value="call" {{ old('channel') == 'call' ? 'selected' : '' }}>Telepon</option>
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
                                    <option value="">Pilih Prioritas</option>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <!-- Placeholder untuk keseimbangan layout -->
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
                                   placeholder="Ringkasan singkat masalah">
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
                                      placeholder="Jelaskan masalah secara detail...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4" id="original_message_row" style="display: none;">
                            <label for="original_message" class="form-label fw-semibold mb-2">
                                Pesan Asli (dari WA/Email)
                            </label>
                            <textarea class="form-control form-control-lg @error('original_message') is-invalid @enderror" 
                                      id="original_message" 
                                      name="original_message" 
                                      rows="3"
                                      placeholder="Salin pesan asli dari WhatsApp atau Email...">{{ old('original_message') }}</textarea>
                            @error('original_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-5">
                            <label for="attachments" class="form-label fw-semibold mb-2">
                                Lampiran
                            </label>
                            <input type="file" 
                                   class="form-control form-control-lg @error('attachments.*') is-invalid @enderror" 
                                   id="attachments" 
                                   name="attachments[]" 
                                   multiple>
                            <div class="form-text mt-2">
                                <small class="text-muted">
                                    📎 Maksimal 5MB per file. Format yang didukung: JPG, PNG, PDF, DOC, DOCX
                                </small>
                            </div>
                            @error('attachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Kembali
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Buat Ticket
                                    </button>
                                </div>
                            </div>
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

    // Show/hide original message based on input method
    function toggleOriginalMessage() {
        if (inputMethodSelect.value === 'whatsapp' || inputMethodSelect.value === 'email') {
            originalMessageRow.style.display = 'block';
        } else {
            originalMessageRow.style.display = 'none';
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

    emailInput.addEventListener('input', validateContact);
    phoneInput.addEventListener('input', validateContact);

    // Initialize
    toggleOriginalMessage();
});
</script>
@endsection