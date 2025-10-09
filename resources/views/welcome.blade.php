@extends('layouts.app-production')

@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center text-white">
                <div class="mb-5">
                    <i class="fas fa-headset fa-5x mb-4" style="opacity: 0.9;"></i>
                    <h1 class="display-4 fw-bold mb-3">ITSO Helpdesk System</h1>
                    <p class="lead mb-4">
                        Sistem manajemen tiket support yang profesional dan efisien untuk mengelola 
                        permintaan bantuan dari email, WhatsApp, dan web portal.
                    </p>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-lg" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                            <div class="card-body text-white">
                                <i class="fas fa-envelope fa-3x mb-3"></i>
                                <h5>Email Integration</h5>
                                <p class="small">Otomatis membuat tiket dari email masuk</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-lg" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                            <div class="card-body text-white">
                                <i class="fab fa-whatsapp fa-3x mb-3"></i>
                                <h5>WhatsApp Support</h5>
                                <p class="small">Handle support via WhatsApp webhook</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-lg" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                            <div class="card-body text-white">
                                <i class="fas fa-cogs fa-3x mb-3"></i>
                                <h5>Admin Panel</h5>
                                <p class="small">Dashboard lengkap untuk manajemen tiket</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-light btn-lg px-4 py-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg px-4 py-2">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg px-4 py-2">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="{{ route('tickets.create') }}" class="btn btn-outline-light btn-lg px-4 py-2">
                            <i class="fas fa-plus me-2"></i>Create Ticket
                        </a>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="row mt-5 pt-5">
            <div class="col-12">
                <div class="card border-0 shadow-lg" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="text-primary">Key Features</h2>
                            <p class="text-muted">Comprehensive helpdesk solution for modern support teams</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h4 class="text-primary mb-3">
                                    <i class="fas fa-ticket-alt me-2"></i>Ticket Management
                                </h4>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Auto ticket generation</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Status tracking</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Priority management</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email notifications</li>
                                </ul>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h4 class="text-primary mb-3">
                                    <i class="fas fa-chart-bar me-2"></i>Analytics & Reports
                                </h4>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Performance metrics</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Response time tracking</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Customer satisfaction</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Export to Excel/PDF</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h4 class="text-primary mb-3">
                                    <i class="fas fa-plug me-2"></i>API Integration
                                </h4>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>REST API endpoints</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Webhook support</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Third-party integration</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Real-time updates</li>
                                </ul>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h4 class="text-primary mb-3">
                                    <i class="fas fa-shield-alt me-2"></i>Security & Access
                                </h4>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Role-based access</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Secure authentication</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Data encryption</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Audit logging</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection