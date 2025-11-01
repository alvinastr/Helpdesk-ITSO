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
        }
        
        .dropdown-item:hover {
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
            transform: translateX(5px);
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
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: #f8f9fa;
            font-weight: 600;
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
        
        /* Form Elements */
        input, select, textarea, .form-control, .form-select {
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus,
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
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-ticket-alt"></i> {{ config('app.name', 'Helpdesk') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                                        <!-- Left Side -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('dashboard') ? 'active fw-bold' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('tickets') || Request::is('tickets/*') ? 'active fw-bold' : '' }}" href="{{ route('tickets.index') }}">
                                    <i class="fas fa-ticket-alt me-1"></i>My Tickets
                                </a>
                            </li>
                            @if(auth()->user()->role === 'admin')
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin/kpi') || Request::is('admin/kpi/*') ? 'active fw-bold' : '' }}" href="{{ route('kpi.dashboard') }}">
                                        <i class="fas fa-chart-line me-1"></i>KPI Dashboard
                                    </a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ Request::is('admin/*') && !Request::is('admin/kpi*') ? 'active fw-bold' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog me-1"></i>Admin
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.pending-review') }}">
                                            <i class="fas fa-clock me-2"></i>Pending Review
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.reports') }}">
                                            <i class="fas fa-file-alt me-2"></i>Reports
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('kpi.dashboard') }}">
                                            <i class="fas fa-chart-line me-2"></i>KPI Dashboard
                                        </a></li>
                                    </ul>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt me-1"></i>Masuk
                                    </a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">
                                        <i class="fas fa-user-plus me-1"></i>Daftar
                                    </a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 0.875rem; font-weight: 600;">
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

                                <div class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                                    <div class="px-3 py-2 border-bottom">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 48px; height: 48px; font-size: 1.25rem; font-weight: 600;">
                                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ Auth::user()->name }}</div>
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
                                    </div>
                                    <a class="dropdown-item" href="{{ route('dashboard') }}">
                                        <i class="fas fa-home me-2"></i>Dashboard
                                    </a>
                                    <a class="dropdown-item" href="{{ route('tickets.index') }}">
                                        <i class="fas fa-ticket-alt me-2"></i>My Tickets
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-4">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-5 py-4 border-top">
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