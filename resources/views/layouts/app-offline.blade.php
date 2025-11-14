<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ITSO') }} - Helpdesk System</title>

    <!-- LOCAL ASSETS - Tidak butuh internet -->
    <!-- Fonts -->
    <link href="{{ asset('vendor/fonts/nunito.css') }}" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">

    <!-- Bootstrap CSS -->
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">

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
        
        .dropdown-toggle::after {
            font-size: 0.7rem;
            vertical-align: middle;
        }
        
        .dropdown-menu {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: background-color 0.15s ease;
        }
        
        .dropdown-item i {
            width: 20px;
            font-size: 0.85rem;
            opacity: 0.7;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        
        /* Container */
        .container-fluid {
            max-width: 1400px;
        }
        
        /* Cards */
        .card {
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: #212529;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Buttons */
        .btn {
            font-weight: 500;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            transition: all 0.15s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        
        /* Forms */
        .form-control, .form-select {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.15s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
        }
        
        /* Tables */
        .table {
            font-size: 0.9rem;
        }
        
        .table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 0.75rem;
        }
        
        .table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
        }
        
        /* Footer */
        footer {
            background-color: #fff;
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 0;
            margin-top: auto;
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1d20;
                color: #e9ecef;
            }
            
            .navbar, .card, footer {
                background-color: #2d3238 !important;
                border-color: #404449;
            }
            
            .form-control, .form-select {
                background-color: #2d3238;
                border-color: #404449;
                color: #e9ecef;
            }
            
            .table {
                color: #e9ecef;
            }
            
            .table thead th {
                border-color: #404449;
            }
        }
        
        /* Utility classes */
        .text-muted {
            color: #6c757d !important;
        }
        
        .text-small {
            font-size: 0.875rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div id="app">
        @include('layouts.navbar')

        <main class="py-4">
            @if(session('success'))
                <div class="container-fluid">
                    <div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="container-fluid">
                    <div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="text-center text-muted">
            <div class="container-fluid">
                <p class="mb-0 text-small">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </footer>
    </div>

    <!-- LOCAL SCRIPTS - Tidak butuh internet -->
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    
    @stack('scripts')
    
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
