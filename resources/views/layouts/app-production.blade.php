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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    <style>
        /* Custom styles */
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
        }
        
        input:focus, select:focus, textarea:focus,
        .form-control:focus, .form-select:focus {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: #0d6efd !important;
        }
        
        .card, .table, .card-header, .card-body {
            background-color: #ffffff !important;
            color: #212529 !important;
            border: 1px solid #dee2e6 !important;
        }
        
        .table thead th {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
        
        .table tbody tr, .table tbody td {
            background-color: #ffffff !important;
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
        }
        
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .sidebar {
            min-height: calc(100vh - 76px);
            background-color: #f8f9fa;
        }
        .ticket-status {
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending_review { background-color: #fff3cd; color: #664d03; }
        .status-open { background-color: #d1ecf1; color: #0c5460; }
        .status-in_progress { background-color: #cff4fc; color: #055160; }
        .status-resolved { background-color: #d4edda; color: #155724; }
        .status-closed { background-color: #e2e3e5; color: #383d41; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
    </style>

    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-headset me-2"></i>
                    {{ config('app.name', 'ITSO') }}
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Left Side -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-1"></i>{{ __('app.Dashboard') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('tickets.index') }}">
                                    <i class="fas fa-ticket-alt me-1"></i>{{ __('app.My Tickets') }}
                                </a>
                            </li>
                            @if(auth()->user()->role === 'admin')
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog me-1"></i>{{ __('app.Admin') }}
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">{{ __('app.Dashboard') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.pending-review') }}">{{ __('app.Pending Review') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.reports') }}">Laporan</a></li>
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
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('app.Login') }}</a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('app.Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-1"></i>{{ __('app.Logout') }}
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
        <main class="py-4">
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
        <footer class="bg-white mt-5 py-4 border-top">
            <div class="container text-center text-muted">
                <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Axios for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>
    
    <!-- Custom JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    <script>
        // Setup CSRF token for axios
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        }
    </script>

    @stack('scripts')
</body>
</html>