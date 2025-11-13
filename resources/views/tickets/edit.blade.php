@extends('layouts.app-production')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-semibold">
                        <i class="fas fa-edit me-2"></i>{{ __('app.Edit Ticket') }}
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

                    <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        
                        {{-- Ticket Info --}}
                        <div class="alert alert-info mb-4">
                            <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
                            <strong>Status:</strong> <span class="badge bg-{{ $ticket->status === 'closed' ? 'success' : 'warning' }}">{{ $ticket->status }}</span>
                        </div>

                        {{-- Data Pelapor --}}
                        <div class="mb-4">
                            <h5 class="text-primary border-bottom border-primary border-2 pb-2 mb-4 fw-semibold">
                                <i class="fas fa-user me-2"></i>Data Pelapor
                            </h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="user_name" class="form-label fw-semibold mb-2">
                                    Nama <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('user_name') is-invalid @enderror" 
                                       id="user_name" 
                                       name="user_name" 
                                       value="{{ old('user_name', $ticket->user_name) }}" 
                                       required>
                                @error('user_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="user_email" class="form-label fw-semibold mb-2">
                                    Email
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg @error('user_email') is-invalid @enderror" 
                                       id="user_email" 
                                       name="user_email" 
                                       value="{{ old('user_email', $ticket->user_email) }}">
                                @error('user_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label for="user_phone" class="form-label fw-semibold mb-2">
                                    Telepon
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg @error('user_phone') is-invalid @enderror" 
                                       id="user_phone" 
                                       name="user_phone" 
                                       value="{{ old('user_phone', $ticket->user_phone) }}">
                                @error('user_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Data Keluhan --}}
                        <div class="mb-4">
                            <h5 class="text-primary border-bottom border-primary border-2 pb-2 mb-4 fw-semibold">
                                <i class="fas fa-clipboard-list me-2"></i>Data Keluhan
                            </h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="category" class="form-label fw-semibold mb-2">
                                    Kategori <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg @error('category') is-invalid @enderror" 
                                        id="category" 
                                        name="category" 
                                        required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="technical" {{ old('category', $ticket->category) == 'technical' ? 'selected' : '' }}>Technical</option>
                                    <option value="billing" {{ old('category', $ticket->category) == 'billing' ? 'selected' : '' }}>Billing</option>
                                    <option value="general" {{ old('category', $ticket->category) == 'general' ? 'selected' : '' }}>General</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-semibold mb-2">
                                    Prioritas <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg @error('priority') is-invalid @enderror" 
                                        id="priority" 
                                        name="priority" 
                                        required>
                                    <option value="">Pilih Prioritas</option>
                                    <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('priority', $ticket->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                                @error('priority')
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
                                   value="{{ old('subject', $ticket->subject) }}" 
                                   required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-5">
                            <label for="description" class="form-label fw-semibold mb-2">
                                Deskripsi Keluhan <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control form-control-lg @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="5" 
                                      required>{{ old('description', $ticket->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
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
@endsection
