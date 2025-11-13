@extends('layouts.app-production')

@push('styles')
<style>
    /* Chevron rotation animation */
    button[data-bs-toggle="collapse"] .fa-chevron-down {
        transition: transform 0.3s ease;
    }
    button[data-bs-toggle="collapse"]:not(.collapsed) .fa-chevron-down {
        transform: rotate(180deg);
    }
    
    /* Smooth collapse animation */
    .collapse {
        transition: height 0.3s ease;
    }
    
    /* Import button styling */
    .btn-outline-success.btn-lg {
        border: 2px dashed #198754;
        background-color: #f8f9fa;
        color: #198754;
        font-weight: 600;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    .btn-outline-success.btn-lg:hover {
        background-color: #d1e7dd;
        border-style: solid;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(25, 135, 84, 0.2);
    }
    .btn-outline-success.btn-lg .text-muted {
        font-size: 0.875rem;
        font-weight: 400;
    }
</style>
@endpush

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

                    @if (isset($errors) && $errors->any())
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

                    {{-- Email Parser Section - Collapsible --}}
                    <div class="mb-4">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-success btn-lg collapsed" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#emailParserSection" 
                                    aria-expanded="false" 
                                    aria-controls="emailParserSection">
                                <i class="fas fa-magic me-2"></i>
                                <strong>✨ Import dari Email</strong>
                                <span class="text-muted ms-2">(Klik untuk expand)</span>
                                <i class="fas fa-chevron-down float-end"></i>
                            </button>
                        </div>
                        
                        <div class="collapse mt-3" id="emailParserSection">
                            <div class="card bg-light border-success">
                                <div class="card-body">
                                    <h5 class="card-title text-success mb-3">
                                        <i class="fas fa-envelope-open-text me-2"></i>Auto-Fill dari Email
                                    </h5>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Copy-paste seluruh konten email (termasuk header From, To, Date, dll), 
                                        lalu klik Parse. Sistem akan otomatis mengisi semua field!
                                    </p>
                                    
                                    <div class="mb-3">
                                        <label for="raw_email" class="form-label fw-semibold">
                                            Paste Seluruh Email Di Sini:
                                        </label>
                                        <textarea class="form-control font-monospace" 
                                                  id="raw_email" 
                                                  rows="8" 
                                                  placeholder="Paste email thread di sini...

Contoh format:
From: user@example.com
To: support@example.com
Date: Tuesday, 21 October 2025 13:35 WIB
Subject: PC Tidak Dapat Jaringan

Dear All,
mohon bantuannya untuk menyambungkan jaringan...
NIP : 04014247"></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button" 
                                                class="btn btn-success" 
                                                id="parse-email-btn"
                                                onclick="parseEmail()">
                                            <i class="fas fa-magic me-2"></i>Parse & Auto-Fill Form
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-sm" 
                                                onclick="document.getElementById('raw_email').value = ''">
                                            <i class="fas fa-eraser me-1"></i>Clear
                                        </button>
                                    </div>
                                    
                                    <div id="parse-status" class="mt-3" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.tickets.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate id="ticket-form">
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

                        {{-- KPI Manual Input (untuk email yang sudah diresolve sebelum diinput ke sistem) --}}
                        <div class="row g-3 mb-4" id="kpi-manual-wrapper" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Input Manual KPI:</strong> Isi field di bawah jika ticket email ini sudah direply/resolve SEBELUM diinput ke sistem.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="first_response_at" class="form-label fw-semibold mb-2">
                                    Waktu First Response (Opsional)
                                    <i class="fas fa-info-circle text-info ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Isi jika admin sudah reply di email SEBELUM ticket diinput ke sistem. Kosongkan jika belum direply."></i>
                                </label>
                                <input type="datetime-local" 
                                       class="form-control form-control-lg @error('first_response_at') is-invalid @enderror" 
                                       id="first_response_at" 
                                       name="first_response_at" 
                                       value="{{ old('first_response_at') }}">
                                <small class="form-text text-muted d-block mt-2">
                                    <i class="fas fa-reply text-success me-1"></i>
                                    Untuk menghitung Response Time yang akurat
                                </small>
                                @error('first_response_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="resolved_at" class="form-label fw-semibold mb-2">
                                    Waktu Resolved (Opsional)
                                    <i class="fas fa-info-circle text-info ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Isi jika ticket sudah diresolve di email SEBELUM diinput ke sistem. Kosongkan jika belum resolve."></i>
                                </label>
                                <input type="datetime-local" 
                                       class="form-control form-control-lg @error('resolved_at') is-invalid @enderror" 
                                       id="resolved_at" 
                                       name="resolved_at" 
                                       value="{{ old('resolved_at') }}">
                                <small class="form-text text-muted d-block mt-2">
                                    <i class="fas fa-check-double text-success me-1"></i>
                                    Untuk menghitung Resolution Time yang akurat
                                </small>
                                @error('resolved_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Email Content Section (untuk channel email) --}}
                        <div class="row g-3 mb-4" id="email-content-wrapper" style="display: none;">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom border-primary border-2 pb-2 mb-3 fw-semibold">
                                    <i class="fas fa-envelope-open-text me-2"></i>Konten Email (untuk Rekap Data)
                                </h5>
                                <div class="alert alert-secondary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Copy-paste isi email di sini untuk keperluan dokumentasi dan rekap data
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email_from" class="form-label fw-semibold mb-2">
                                    Email Pengirim (From)
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg @error('email_from') is-invalid @enderror" 
                                       id="email_from" 
                                       name="email_from" 
                                       value="{{ old('email_from') }}"
                                       placeholder="user@example.com">
                                @error('email_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email_to" class="form-label fw-semibold mb-2">
                                    Email Penerima (To)
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('email_to') is-invalid @enderror" 
                                       id="email_to" 
                                       name="email_to" 
                                       value="{{ old('email_to', 'it.support@bankmega.com') }}"
                                       placeholder="support@example.com">
                                @error('email_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="email_cc" class="form-label fw-semibold mb-2">
                                    CC (Opsional)
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('email_cc') is-invalid @enderror" 
                                       id="email_cc" 
                                       name="email_cc" 
                                       value="{{ old('email_cc') }}"
                                       placeholder="Pisahkan dengan koma: user1@example.com, user2@example.com">
                                @error('email_cc')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="email_subject" class="form-label fw-semibold mb-2">
                                    Subject Email Asli
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('email_subject') is-invalid @enderror" 
                                       id="email_subject" 
                                       name="email_subject" 
                                       value="{{ old('email_subject') }}"
                                       placeholder="Subject email asli (bisa beda dengan subject ticket)">
                                @error('email_subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="email_body_original" class="form-label fw-semibold mb-2">
                                    Isi Email Keluhan Pertama (dari User)
                                    <i class="fas fa-info-circle text-info ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Copy-paste isi email pertama dari user/pelapor"></i>
                                </label>
                                <textarea class="form-control @error('email_body_original') is-invalid @enderror" 
                                          id="email_body_original" 
                                          name="email_body_original" 
                                          rows="4"
                                          placeholder="Paste isi email keluhan dari user...">{{ old('email_body_original') }}</textarea>
                                @error('email_body_original')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="email_response_admin" class="form-label fw-semibold mb-2">
                                    Isi Email Response Admin (Opsional)
                                    <i class="fas fa-info-circle text-info ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Isi jika admin sudah reply di email"></i>
                                </label>
                                <textarea class="form-control @error('email_response_admin') is-invalid @enderror" 
                                          id="email_response_admin" 
                                          name="email_response_admin" 
                                          rows="4"
                                          placeholder="Paste isi email response dari admin...">{{ old('email_response_admin') }}</textarea>
                                @error('email_response_admin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="email_resolution_message" class="form-label fw-semibold mb-2">
                                    Isi Email Resolution (Opsional)
                                    <i class="fas fa-info-circle text-info ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Isi jika ticket sudah diresolve dan ada email penutup"></i>
                                </label>
                                <textarea class="form-control @error('email_resolution_message') is-invalid @enderror" 
                                          id="email_resolution_message" 
                                          name="email_resolution_message" 
                                          rows="4"
                                          placeholder="Paste isi email resolution/penutup...">{{ old('email_resolution_message') }}</textarea>
                                @error('email_resolution_message')
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
    const kpiManualWrapper = document.getElementById('kpi-manual-wrapper');
    const emailContentWrapper = document.getElementById('email-content-wrapper');
    const firstResponseInput = document.getElementById('first_response_at');
    const resolvedInput = document.getElementById('resolved_at');

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
            kpiManualWrapper.style.display = 'block';
            emailContentWrapper.style.display = 'block';
            emailTimeInput.required = true;
        } else {
            emailTimeWrapper.style.display = 'none';
            kpiManualWrapper.style.display = 'none';
            emailContentWrapper.style.display = 'none';
            emailTimeInput.required = false;
            emailTimeInput.value = '';
            firstResponseInput.value = '';
            resolvedInput.value = '';
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
    
    // Form validation handler
    const form = document.getElementById('ticket-form');
    form.addEventListener('submit', function(event) {
        console.log('=== FORM SUBMIT TRIGGERED ===');
        console.log('Form valid?', form.checkValidity());
        
        // Log all form data
        const formData = new FormData(form);
        console.log('Form data:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}:`, value);
        }
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Find ALL invalid fields
            const invalidFields = form.querySelectorAll(':invalid');
            console.log('Invalid fields count:', invalidFields.length);
            
            invalidFields.forEach((field, index) => {
                console.log(`Invalid field ${index + 1}:`, {
                    name: field.name,
                    id: field.id,
                    type: field.type,
                    value: field.value,
                    validationMessage: field.validationMessage,
                    required: field.required
                });
            });
            
            // Find first invalid field
            const firstInvalid = invalidFields[0];
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
                
                // Show alert with ALL invalid fields
                let errorMsg = `Form belum lengkap! Ada ${invalidFields.length} field yang bermasalah:\n\n`;
                invalidFields.forEach((field, index) => {
                    const label = field.labels[0]?.innerText || field.name || field.id;
                    errorMsg += `${index + 1}. ${label}: ${field.validationMessage}\n`;
                });
                alert(errorMsg);
            }
        } else {
            console.log('✅ Form valid! Submitting...');
        }
        
        form.classList.add('was-validated');
    }, false);
});

// Email Parser Function
async function parseEmail() {
    const rawEmail = document.getElementById('raw_email').value;
    const parseBtn = document.getElementById('parse-email-btn');
    const parseStatus = document.getElementById('parse-status');
    
    if (!rawEmail.trim()) {
        parseStatus.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Mohon paste email terlebih dahulu!</div>';
        parseStatus.style.display = 'block';
        return;
    }
    
    // Show loading
    parseBtn.disabled = true;
    parseBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Parsing...';
    parseStatus.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Sedang memproses email...</div>';
    parseStatus.style.display = 'block';
    
    try {
        // Call API
        const response = await fetch('/api/v1/parse-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ raw_email: rawEmail })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            console.log('=== PARSE SUCCESS ===');
            console.log('Parsed data:', data);
            
            // Helper function untuk safely set value
            function safeSetValue(id, value) {
                const element = document.getElementById(id);
                if (element && value) {
                    element.value = value;
                    console.log(`✅ Set ${id} = ${value.substring(0, 50)}`);
                    return true;
                } else if (!element) {
                    console.log(`❌ Element not found: ${id}`);
                } else if (!value) {
                    console.log(`⚠️ No value for ${id}`);
                }
                return false;
            }
            
            // Fill basic info
            safeSetValue('reporter_nip', data.reporter_nip);
            safeSetValue('reporter_name', data.reporter_name);
            safeSetValue('reporter_email', data.reporter_email);
            safeSetValue('reporter_department', data.reporter_department);
            
            // Fill ticket info
            safeSetValue('subject', data.subject);
            safeSetValue('description', data.description);
            safeSetValue('priority', data.priority);
            
            // Set channel to email & input method
            safeSetValue('channel', 'email');
            safeSetValue('input_method', 'email');
            
            // Trigger toggle for email fields
            console.log('Triggering channel change event...');
            const channelEl = document.getElementById('channel');
            if (channelEl) {
                channelEl.dispatchEvent(new Event('change'));
                console.log('✅ Channel changed to email, email fields should be visible now');
            }
            
            // Wait a bit for toggle to complete
            setTimeout(() => {
                // Email metadata
                safeSetValue('email_from', data.email_from);
                safeSetValue('email_to', data.email_to);
                safeSetValue('email_cc', data.email_cc);
                safeSetValue('email_subject', data.email_subject);
                
                // KPI timestamps
                safeSetValue('email_received_at', data.email_received_at);
                safeSetValue('first_response_at', data.first_response_at);
                safeSetValue('resolved_at', data.resolved_at);
                
                // Email bodies
                safeSetValue('email_body_original', data.email_body_original);
                safeSetValue('email_response_admin', data.email_response_admin);
                safeSetValue('email_resolution_message', data.email_resolution_message);
                
                console.log('=== FILL COMPLETE ===');
            }, 300);
            
            // Count filled fields
            let filledCount = 0;
            if (data.reporter_name) filledCount++;
            if (data.reporter_email) filledCount++;
            if (data.reporter_nip) filledCount++;
            if (data.subject) filledCount++;
            if (data.description) filledCount++;
            if (data.email_received_at) filledCount++;
            
            // Show success with count
            parseStatus.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Berhasil!</strong> ${filledCount} field ter-isi otomatis. 
                ${data.category ? '(Category: ' + data.category + ')' : ''}
                Silakan review dan submit.
            </div>`;
            
            // Scroll to form
            setTimeout(() => {
                const firstInput = document.getElementById('reporter_nip');
                if (firstInput) {
                    firstInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 500);
            
        } else {
            parseStatus.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + result.message + '</div>';
        }
        
    } catch (error) {
        parseStatus.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Terjadi kesalahan: ' + error.message + '</div>';
    } finally {
        // Reset button
        parseBtn.disabled = false;
        parseBtn.innerHTML = '<i class="fas fa-magic me-2"></i>Parse & Auto-Fill Form';
    }
}
</script>
@endsection