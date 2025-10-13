@extends('layouts.app-production')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus-circle me-2"></i>Buat Ticket Baru</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                <option value="">Pilih Priority</option>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                                <option value="">Pilih Category</option>
                                <option value="technical" {{ old('category') == 'technical' ? 'selected' : '' }}>Technical Support</option>
                                <option value="billing" {{ old('category') == 'billing' ? 'selected' : '' }}>Billing</option>
                                <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>General Inquiry</option>
                                <option value="feature" {{ old('category') == 'feature' ? 'selected' : '' }}>Feature Request</option>
                                <option value="bug" {{ old('category') == 'bug' ? 'selected' : '' }}>Bug Report</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="6" required 
                                      placeholder="Jelaskan detail masalah atau permintaan Anda...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attachment</label>
                            <input type="file" class="form-control @error('attachment') is-invalid @enderror" 
                                   id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <div class="form-text">Max file size: 10MB. Allowed types: JPG, PNG, PDF, DOC, DOCX</div>
                            @error('attachment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('tickets.index') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Buat Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection