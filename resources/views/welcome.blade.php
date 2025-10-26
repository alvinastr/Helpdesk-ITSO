@extends('layouts.app-production')

@section('content')
<div class="min-vh-100" style="background: linear-gradient(135deg, #fa9d1c 0%, #feebd2 100%);">
    <!-- Hero Section -->
    <div class="container py-5">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6 text-center text-lg-start mb-5 mb-lg-0">
                <div class="mb-4">
                    <span class="badge rounded-pill px-3 py-2 mb-3" style="background-color: rgba(255,255,255,0.2); color: #fff; font-size: 0.9rem;">
                        ITSO
                    </span>
                    <h1 class="display-3 fw-bold text-white mb-4">
                        Helpdesk
                        <span class="text-white" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">System</span>
                    </h1>
                    <p class="lead text-white mb-4" style="opacity: 0.9;">
                        Solusi helpdesk modern untuk tim support yang efisien
                    </p>
                </div>
                
                <div class="d-flex justify-content-center justify-content-lg-start gap-3 flex-wrap mb-4">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-light btn-lg px-5 py-3 rounded-pill shadow">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk Sekarang
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg px-5 py-3 rounded-pill shadow">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="{{ route('tickets.create') }}" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
                            <i class="fas fa-plus me-2"></i>Buat Ticket
                        </a>
                    @endguest
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="text-center">
                    <div class="logo-container">
                        <img
                            src="{{ asset('images/logos/Bank_Mega_2013.svg.png') }}"
                            alt="Bank Mega Logo"
                            class="img-fluid"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Features Section -->
    {{-- <div class="py-5" style="background-color: #feebd2;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3" style="color: #fa9d1c;">🚀 Fitur Unggulan</h2>
                <p class="lead text-muted">Solusi helpdesk lengkap untuk tim support modern</p>
            </div>
            
            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 h-100 shadow-lg hover-lift" style="transition: transform 0.3s;">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; background-color: #fa9d1c;">
                                <i class="fas fa-envelope fa-2x text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-3">📧 Email Integration</h5>
                            <p class="text-muted">Otomatis membuat tiket dari email masuk dengan parsing cerdas dan notifikasi real-time.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 h-100 shadow-lg hover-lift" style="transition: transform 0.3s;">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; background-color: #fa9d1c;">
                                <i class="fab fa-whatsapp fa-2x text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-3">📱 WhatsApp Support</h5>
                            <p class="text-muted">Handle support request melalui WhatsApp dengan webhook integration dan auto-response.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 h-100 shadow-lg hover-lift" style="transition: transform 0.3s;">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; background-color: #fa9d1c;">
                                <i class="fas fa-chart-line fa-2x text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-3">📊 Analytics Dashboard</h5>
                            <p class="text-muted">Dashboard analytics lengkap dengan metrik performa dan laporan detail.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 h-100 shadow-lg hover-lift" style="transition: transform 0.3s;">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; background-color: #fa9d1c;">
                                <i class="fas fa-users fa-2x text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-3">👥 Team Management</h5>
                            <p class="text-muted">Kelola tim support dengan role-based access dan assignment otomatis.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 h-100 shadow-lg hover-lift" style="transition: transform 0.3s;">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; background-color: #fa9d1c;">
                                <i class="fas fa-mobile-alt fa-2x text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-3">📱 Mobile Ready</h5>
                            <p class="text-muted">Interface responsif yang bekerja sempurna di desktop, tablet, dan mobile.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 h-100 shadow-lg hover-lift" style="transition: transform 0.3s;">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; background-color: #fa9d1c;">
                                <i class="fas fa-shield-alt fa-2x text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-3">🔒 Security First</h5>
                            <p class="text-muted">Keamanan tingkat enterprise dengan enkripsi data dan audit trail lengkap.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- CTA Section -->
    {{-- <div class="py-5" style="background-color: #fa9d1c;">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="text-white fw-bold mb-3">Siap Meningkatkan Layanan Support Anda?</h2>
                    <p class="text-white mb-4 opacity-90" style="font-size: 1.1rem;">
                        Bergabunglah dengan ratusan perusahaan yang telah mempercayai ITSO Helpdesk untuk mengelola support mereka.
                    </p>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-light btn-lg px-5 py-3 rounded-pill me-3">
                            <i class="fas fa-rocket me-2"></i>Mulai Gratis Sekarang
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
                            <i class="fas fa-sign-in-alt me-2"></i>Sudah Punya Akun?
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg px-5 py-3 rounded-pill me-3">
                            <i class="fas fa-tachometer-alt me-2"></i>Ke Dashboard
                        </a>
                        <a href="{{ route('tickets.create') }}" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
                            <i class="fas fa-plus me-2"></i>Buat Ticket Baru
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </div> --}}
</div>

<style>
/* Clean Logo Container */
.logo-container {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    max-width: 400px;
    margin: 0 auto;
}

.logo-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.logo-container img {
    width: 100%;
    height: auto;
    max-width: 350px;
}

/* Clean Button Styling */
.btn {
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-light {
    background: #ffffff;
    border: none;
    color: #fa9d1c;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.btn-light:hover {
    background: #f8f9fa;
    color: #e67e00;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.btn-outline-light {
    border: 2px solid rgba(255, 255, 255, 0.8);
    color: white;
    background: transparent;
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-3 {
        font-size: 2.5rem !important;
    }
    
    .logo-container {
        padding: 1.5rem;
        margin-top: 2rem;
    }
}

@media (max-width: 576px) {
    .logo-container {
        padding: 1rem;
        max-width: 300px;
    }
}
</style>
</style>

@endsection