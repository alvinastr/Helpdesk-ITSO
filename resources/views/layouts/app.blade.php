<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ITSO') }} - Helpdesk System</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        /* Custom styles */
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --navbar-height: 76px;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .navbar {
            padding: 0.75rem 0;
            border-bottom: 3px solid var(--primary-color);
            transition: box-shadow 0.3s ease;
        }
        
        .navbar-light .navbar-nav .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            margin: 0 0.25rem;
        }
        
        .navbar-light .navbar-nav .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .navbar-light .navbar-nav .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(13, 110, 253, 0.1);
            font-weight: 600;
            position: relative;
        }
        
        .navbar-light .navbar-nav .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -0.75rem;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 2px 2px 0 0;
        }
        
        /* Skip to main content link for accessibility */
        .skip-to-main {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 100;
            border-radius: 0 0 4px 0;
        }
        
        .skip-to-main:focus {
            top: 0;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-top: 0.5rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            font-weight: 500;
            color: #212529 !important;
        }
        
        .dropdown-item:hover {
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--primary-color) !important;
            transform: translateX(5px);
        }
        
        .dropdown-item.active {
            background-color: rgba(13, 110, 253, 0.15);
            color: var(--primary-color) !important;
            font-weight: 600;
            position: relative;
        }
        
        .dropdown-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background-color: var(--primary-color);
        }
        
        .dropdown-header {
            padding: 0.5rem 1rem;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6c757d !important;
            margin-top: 0.25rem;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
            opacity: 0.1;
        }
        
        .badge {
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }
        
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            border: none;
            border-radius: 0.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: #ffffff !important;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: #f8f9fa !important;
            font-weight: 600;
            color: #212529 !important;
        }
        
        .card-body {
            background-color: #ffffff !important;
            color: #212529 !important;
        }
        
        /* Force light theme - prevent dark mode */
        html, body {
            color-scheme: light !important;
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
        
        /* Force all form elements to be light */
        input, select, textarea, .form-control, .form-select {
            background-color: #ffffff !important;
            color: #212529 !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus,
        .form-control:focus, .form-select:focus {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .table {
            background-color: #ffffff !important;
            color: #212529 !important;
        }
        
        .table thead th {
            background-color: #f8f9fa !important;
            color: #212529 !important;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6 !important;
        }
        
        .table tbody tr {
            background-color: #ffffff !important;
            color: #212529 !important;
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa !important;
        }
        
        .table tbody td {
            background-color: transparent !important;
            color: #212529 !important;
        }
        
        /* Override any dark mode media queries */
        @media (prefers-color-scheme: dark) {
            html, body, .card, .table, .card-header, .card-body,
            .table thead th, .table tbody tr, .table tbody td,
            input, select, textarea, .form-control, .form-select {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
            
            body {
                background-color: #f8f9fa !important;
            }
        }
        
        /* Button Styles */
        .btn {
            font-weight: 500;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.3);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(25, 135, 84, 0.3);
        }
        
        /* Focus Visible for Accessibility */
        *:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Smooth Transitions */
        * {
            transition: none !important;
        }
        
        a, button, .btn, .nav-link, .dropdown-item, .card {
            transition: all 0.2s ease !important;
        }
        
        /* Responsive Navbar */
        @media (max-width: 768px) {
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                margin: 0.25rem 0;
            }
            
            .dropdown-menu {
                margin-top: 0;
                border-radius: 0.375rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        /* Table Styles */
        .table thead th {
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Smooth Transitions */
        * {
            transition: none !important;
        }
        
        a, button, .btn, .nav-link, .dropdown-item, .card {
            transition: all 0.2s ease !important;
        }
        
        /* Responsive Navbar */
        @media (max-width: 768px) {
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                margin: 0.25rem 0;
            }
            
            .dropdown-menu {
                margin-top: 0;
                border-radius: 0.375rem;
            }
        }
        
        /* Loading States */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Focus Visible for Accessibility */
        *:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-to-main">Skip to main content</a>
    
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm" role="navigation" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-headset me-2"></i>
                    {{ config('app.name', 'ITSO') }}
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Left Side -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" 
                                   href="{{ route('dashboard') }}"
                                   aria-current="{{ Request::is('dashboard') ? 'page' : 'false' }}">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('tickets') || Request::is('tickets/*') ? 'active' : '' }}" 
                                   href="{{ route('tickets.index') }}"
                                   aria-current="{{ Request::is('tickets*') ? 'page' : 'false' }}">
                                    <i class="fas fa-ticket-alt me-1"></i>My Tickets
                                </a>
                            </li>
                            @if(auth()->user()->role === 'admin')
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ Request::is('admin/*') ? 'active' : '' }}" 
                                       href="#" 
                                       id="adminDropdown"
                                       role="button" 
                                       data-bs-toggle="dropdown" 
                                       aria-expanded="false"
                                       aria-haspopup="true">
                                        <i class="fas fa-tools me-1"></i>Admin
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown" style="min-width: 300px;">
                                        <!-- Dashboard -->
                                        <li><a class="dropdown-item {{ Request::is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Admin Dashboard
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- Ticket Management -->
                                        <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            <i class="fas fa-ticket-alt me-2"></i>Ticket Management
                                        </h6></li>
                                        <li><a class="dropdown-item {{ Request::is('admin/pending-review') ? 'active' : '' }}" href="{{ route('admin.pending-review') }}">
                                            <i class="fas fa-clock me-2 text-warning"></i>Pending Review
                                            @php
                                                $pendingCount = \App\Models\Ticket::where('status', 'pending_review')->count();
                                            @endphp
                                            @if($pendingCount > 0)
                                                <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }}</span>
                                            @endif
                                        </a></li>
                                        <li><a class="dropdown-item {{ Request::is('admin/tickets/create') ? 'active' : '' }}" href="{{ route('admin.tickets.create') }}">
                                            <i class="fas fa-plus-circle me-2 text-success"></i>Create Ticket (Admin)
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- Reports & Analytics -->
                                        <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                                        </h6></li>
                                        <li><a class="dropdown-item {{ Request::is('admin/kpi*') ? 'active' : '' }}" href="{{ route('kpi.dashboard') }}">
                                            <i class="fas fa-chart-line me-2 text-info"></i>KPI Dashboard
                                        </a></li>
                                        <li><a class="dropdown-item {{ Request::is('admin/reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                                            <i class="fas fa-file-alt me-2 text-secondary"></i>Ticket Reports
                                        </a></li>
                                    </ul>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt me-1"></i>Login
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">
                                        <i class="fas fa-user-plus me-1"></i>Register
                                    </a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" 
                                   href="#" 
                                   id="userDropdown"
                                   role="button" 
                                   data-bs-toggle="dropdown"
                                   aria-expanded="false"
                                   aria-haspopup="true">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 0.875rem; font-weight: 600;"
                                                 role="img"
                                                 aria-label="User avatar">
                                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div class="d-none d-md-block">
                                            <div class="fw-semibold" style="font-size: 0.9rem;">{{ Auth::user()->name }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                @if(Auth::user()->role === 'admin')
                                                    <i class="fas fa-shield-alt me-1"></i>Administrator
                                                @else
                                                    <i class="fas fa-user me-1"></i>User
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="min-width: 280px;">
                                    <li class="px-3 py-2 border-bottom">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 48px; height: 48px; font-size: 1.25rem; font-weight: 600;"
                                                 role="img"
                                                 aria-label="User avatar">
                                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark">{{ Auth::user()->name }}</div>
                                                <div class="text-muted small">{{ Auth::user()->email }}</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-{{ Auth::user()->role === 'admin' ? 'danger' : 'primary' }} bg-opacity-10 text-{{ Auth::user()->role === 'admin' ? 'danger' : 'primary' }}">
                                                @if(Auth::user()->role === 'admin')
                                                    <i class="fas fa-shield-alt me-1"></i>Administrator
                                                @else
                                                    <i class="fas fa-user me-1"></i>User
                                                @endif
                                            </span>
                                        </div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-dark" href="{{ route('dashboard') }}">
                                            <i class="fas fa-home me-2"></i>Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-dark" href="{{ route('tickets.index') }}">
                                            <i class="fas fa-ticket-alt me-2"></i>My Tickets
                                        </a>
                                    </li>
                                    @if(auth()->user()->role === 'admin')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-dark" href="{{ route('admin.dashboard') }}">
                                                <i class="fas fa-shield-alt me-2 text-danger"></i>Admin Panel
                                            </a>
                                        </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" 
                                           href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-4" id="main-content" role="main">
            @if(session('success'))
                <div class="container">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="container">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-5 py-4 border-top" role="contentinfo">
            <div class="container text-center text-muted">
                <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>