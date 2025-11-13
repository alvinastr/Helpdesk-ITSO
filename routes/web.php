<?php

use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public routes - Redirect to login or dashboard
Route::get('/', function () {
    if (Auth::check()) {
        // If user is logged in, redirect to appropriate dashboard
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
    // If not logged in, redirect to login page
    return redirect()->route('login');
});

// Auth routes
Auth::routes();

// User ticket routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    
    // Tickets
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
});

// Admin routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminTicketController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/pending-review', [AdminTicketController::class, 'pendingTickets'])->name('admin.pending-review');
    
    // Admin Ticket Creation
    Route::get('/tickets/create', [AdminController::class, 'createTicket'])->name('admin.tickets.create');
    Route::post('/tickets', [AdminController::class, 'storeTicket'])->name('admin.tickets.store');
    Route::get('/tickets/{ticket}', [AdminController::class, 'showTicket'])->name('admin.tickets.show');
    
    // Ticket management
    Route::post('/tickets/{ticket}/approve', [AdminTicketController::class, 'approve'])->name('admin.tickets.approve');
    Route::post('/tickets/{ticket}/reject', [AdminTicketController::class, 'reject'])->name('admin.tickets.reject');
    Route::post('/tickets/{ticket}/revision', [AdminTicketController::class, 'requestRevision'])->name('admin.tickets.revision');
    Route::post('/tickets/{ticket}/update-status', [AdminTicketController::class, 'updateStatus'])->name('admin.tickets.update-status');
    Route::post('/tickets/{ticket}/close', [AdminTicketController::class, 'close'])->name('admin.tickets.close');
    Route::post('/tickets/{ticket}/assign', [AdminTicketController::class, 'assign'])->name('admin.tickets.assign');
    Route::post('/tickets/{ticket}/add-note', [AdminTicketController::class, 'addNote'])->name('admin.tickets.add-note');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('admin.reports.excel');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('admin.reports.pdf');
    
    // KPI Dashboard (New comprehensive routes)
    Route::get('/kpi-dashboard', [App\Http\Controllers\Admin\KpiDashboardController::class, 'index'])->name('admin.kpi-dashboard');
    Route::get('/kpi-dashboard/live-stats', [App\Http\Controllers\Admin\KpiDashboardController::class, 'liveStats'])->name('admin.kpi-dashboard.live-stats');
    
    // KPI Dashboard (Legacy routes - keep for backward compatibility)
    Route::get('/kpi', [App\Http\Controllers\KpiDashboardController::class, 'index'])->name('kpi.dashboard');
    Route::get('/kpi/export', [App\Http\Controllers\KpiDashboardController::class, 'export'])->name('kpi.export');
    
    // Email Auto-Fetch Monitor
    Route::get('/email-monitor', [App\Http\Controllers\Admin\EmailMonitorController::class, 'index'])->name('admin.email-monitor');
    Route::post('/email-monitor/test-fetch', [App\Http\Controllers\Admin\EmailMonitorController::class, 'testFetch'])->name('admin.email-monitor.test-fetch');
    Route::get('/email-monitor/live-stats', [App\Http\Controllers\Admin\EmailMonitorController::class, 'liveStats'])->name('admin.email-monitor.live-stats');

    // WhatsApp Tickets (Admin UI)
    Route::get('/whatsapp', [App\Http\Controllers\Admin\WhatsAppController::class, 'index'])->name('admin.whatsapp.index');
    
    // WhatsApp Bot Monitoring Dashboard (HARUS SEBELUM /whatsapp/{ticket})
    Route::get('/whatsapp/monitor', [App\Http\Controllers\WhatsAppMonitorController::class, 'index'])->name('whatsapp.monitor');
    Route::get('/whatsapp/api/status', [App\Http\Controllers\WhatsAppMonitorController::class, 'apiStatus'])->name('whatsapp.api.status');
    Route::get('/whatsapp/api/logs', [App\Http\Controllers\WhatsAppMonitorController::class, 'logs'])->name('whatsapp.api.logs');
    Route::get('/whatsapp/api/statistics', [App\Http\Controllers\WhatsAppMonitorController::class, 'statistics'])->name('whatsapp.api.statistics');
    
    Route::get('/whatsapp/dashboard', [App\Http\Controllers\Admin\WhatsAppController::class, 'dashboard'])->name('admin.whatsapp.dashboard');
    
    // Route dengan parameter {ticket} HARUS DI PALING BAWAH
    Route::get('/whatsapp/{ticket}', [App\Http\Controllers\Admin\WhatsAppController::class, 'show'])->name('admin.whatsapp.show');
    Route::post('/whatsapp/{ticket}/assign', [App\Http\Controllers\Admin\WhatsAppController::class, 'assign'])->name('admin.whatsapp.assign');
    Route::post('/whatsapp/{ticket}/status', [App\Http\Controllers\Admin\WhatsAppController::class, 'updateStatus'])->name('admin.whatsapp.status');
    Route::post('/whatsapp/{ticket}/response', [App\Http\Controllers\Admin\WhatsAppController::class, 'addResponse'])->name('admin.whatsapp.response');
    Route::post('/whatsapp/{ticket}/template', [App\Http\Controllers\Admin\WhatsAppController::class, 'sendTemplate'])->name('admin.whatsapp.template');
    Route::put('/whatsapp/{ticket}/update-actual-time', [App\Http\Controllers\Admin\WhatsAppController::class, 'updateActualTime'])->name('admin.whatsapp.update-actual-time');
});

// KPI API routes (for AJAX calls)
Route::prefix('api')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/kpi/summary', [App\Http\Controllers\KpiDashboardController::class, 'apiSummary'])->name('api.kpi.summary');
    Route::get('/kpi/trends', [App\Http\Controllers\KpiDashboardController::class, 'apiTrends'])->name('api.kpi.trends');
});
