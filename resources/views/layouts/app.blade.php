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
            --navbar-height: 56px;
        }
        
        body {
            background-color: #f8f9fa;
            padding-top: 0;
            margin: 0;
        }
        
        #app {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Minimalist Navbar Styles */
        .navbar {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e9ecef;
            background: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 0;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        .navbar .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--primary-color) !important;
            padding: 0.25rem 0;
        }
        
        .navbar-brand i {
            font-size: 1.1rem;
        }
        
        main {
            flex: 1;
        }
        
        .navbar-light .navbar-nav .nav-link {
            color: #6c757d;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            transition: all 0.15s ease;
            margin: 0 0.1rem;
        }
        
        .navbar-light .navbar-nav .nav-link i {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .navbar-light .navbar-nav .nav-link:hover {
            color: var(--primary-color);
            background-color: #f8f9fa;
        }
        
        .navbar-light .navbar-nav .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(13, 110, 253, 0.08);
            font-weight: 600;
        }
        
        /* Quick Action Buttons */
        .nav-quick-action {
            padding: 0.4rem !important;
            margin: 0 0.15rem !important;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50% !important;
        }
        
        .nav-quick-action i {
            font-size: 1rem;
        }
        
        /* Compact Dropdown Menu */
        .dropdown-menu {
            border: 1px solid #e9ecef;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
            border-radius: 0.375rem;
            padding: 0.5rem;
            margin-top: 0.25rem;
            min-width: 200px;
        }
        
        .dropdown-item {
            padding: 0.4rem 0.75rem;
            border-radius: 0.25rem;
            transition: all 0.15s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .dropdown-item i {
            width: 18px;
            text-align: center;
            font-size: 0.85rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        
        .dropdown-item.active {
            background-color: rgba(13, 110, 253, 0.08);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .dropdown-header {
            padding: 0.4rem 0.75rem;
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            color: #6c757d !important;
            text-transform: uppercase;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            margin: 0.4rem 0;
            opacity: 0.15;
        }
        
        /* User Avatar in Navbar */
        .user-avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.85rem;
        }
        
        .user-info-compact {
            font-size: 0.85rem;
            line-height: 1.2;
        }
        
        .badge {
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }
        
        /* Pagination Styles */
        .pagination {
            margin: 1.5rem 0 !important;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        .pagination .page-item {
            margin: 0 !important;
        }
        
        .pagination .page-link {
            color: var(--primary-color) !important;
            background-color: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
            border-radius: 0.375rem !important;
            margin: 0 !important;
            transition: all 0.15s ease !important;
            min-width: 40px;
            min-height: 40px;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
        }
        
        .pagination .page-link:hover {
            background-color: #f8f9fa !important;
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
            transform: translateY(-1px);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            z-index: 3;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d !important;
            background-color: #ffffff !important;
            border-color: #dee2e6 !important;
            cursor: not-allowed !important;
            opacity: 0.5;
        }
        
        /* Fix pagination arrow/icon size */
        .pagination .page-link svg,
        .pagination .page-link i {
            width: 0.875rem !important;
            height: 0.875rem !important;
            font-size: 0.875rem !important;
            vertical-align: middle !important;
            display: inline-block !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
        }
        
        /* Ensure SVG doesn't get too large */
        .pagination svg {
            max-width: 1rem !important;
            max-height: 1rem !important;
            width: 1rem !important;
            height: 1rem !important;
        }
        
        /* Remove default Bootstrap pagination spacing */
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            border-radius: 0.375rem !important;
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
            .navbar {
                padding: 0.4rem 0;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-nav {
                padding: 0.5rem 0;
            }
            
            .navbar-nav .nav-link {
                padding: 0.6rem 1rem;
                margin: 0.2rem 0;
                border-radius: 0.375rem;
            }
            
            .navbar-nav .nav-link i {
                width: 24px;
            }
            
            .dropdown-menu {
                margin-top: 0.25rem;
                border-radius: 0.375rem;
            }
            
            .user-info-compact {
                display: inline !important;
            }
        }
        
        /* Hide text on smaller screens, show icons only */
        @media (max-width: 991px) {
            .nav-link span:not(.badge) {
                display: none !important;
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
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-md navbar-light bg-white" role="navigation" aria-label="Main navigation">
            <div class="container-fluid">
                <!-- Brand -->
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-headset"></i>
                    <span class="ms-2">{{ config('app.name', 'ITSO') }}</span>
                </a>
                
                <!-- Mobile Toggle -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    @auth
                        <!-- Main Navigation -->
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" 
                                   href="{{ route('dashboard') }}">
                                    <i class="fas fa-home"></i>
                                    <span class="d-none d-lg-inline ms-1">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('tickets*') ? 'active' : '' }}" 
                                   href="{{ route('tickets.index') }}">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span class="d-none d-lg-inline ms-1">Tickets</span>
                                </a>
                            </li>
                            
                            @if(auth()->user()->role === 'admin')
                                <!-- Admin Quick Actions -->
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin/pending-review') ? 'active' : '' }}" 
                                       href="{{ route('admin.pending-review') }}"
                                       title="Pending Review">
                                        <i class="fas fa-clock"></i>
                                        @php
                                            $pendingCount = \App\Models\Ticket::where('status', 'pending_review')->count();
                                        @endphp
                                        @if($pendingCount > 0)
                                            <span class="badge bg-warning text-dark rounded-pill ms-1" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">{{ $pendingCount }}</span>
                                        @endif
                                        <span class="d-none d-lg-inline ms-1">Review</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin/kpi*') ? 'active' : '' }}" 
                                       href="{{ route('kpi.dashboard') }}"
                                       title="KPI Dashboard">
                                        <i class="fas fa-chart-line"></i>
                                        <span class="d-none d-lg-inline ms-1">KPI</span>
                                    </a>
                                </li>
                                
                                <!-- Admin Dropdown -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ Request::is('admin/*') && !Request::is('admin/pending-review') && !Request::is('admin/kpi*') ? 'active' : '' }}" 
                                       href="#" 
                                       id="adminDropdown"
                                       data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                        <span class="d-none d-lg-inline ms-1">More</span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-start">
                                        <li><h6 class="dropdown-header">Admin Tools</h6></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                            <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.tickets.create') }}">
                                            <i class="fas fa-plus"></i> Create Ticket
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">Reports</h6></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.reports') }}">
                                            <i class="fas fa-file-alt"></i> Ticket Reports
                                        </a></li>
                                    </ul>
                                </li>
                            @endif
                        </ul>

                        <!-- User Menu -->
                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle p-1" 
                                   href="#" 
                                   id="userDropdown"
                                   data-bs-toggle="dropdown">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center user-avatar-sm">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    <span class="d-none d-md-inline ms-2 user-info-compact">{{ Auth::user()->name }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li class="px-3 py-2">
                                        <div class="fw-semibold">{{ Auth::user()->name }}</div>
                                        <div class="text-muted small">{{ Auth::user()->email }}</div>
                                        <span class="badge bg-{{ Auth::user()->role === 'admin' ? 'danger' : 'primary' }} mt-2">
                                            {{ Auth::user()->role === 'admin' ? 'Admin' : 'User' }}
                                        </span>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    @else
                        <!-- Guest Menu -->
                        <ul class="navbar-nav ms-auto">
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">
                                        <i class="fas fa-user-plus"></i> Register
                                    </a>
                                </li>
                            @endif
                        </ul>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-4" id="main-content" role="main">
            @if(session('success'))
                <div class="container-fluid">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="container-fluid">
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