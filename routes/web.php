<?php

use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Auth routes
Auth::routes();

// User ticket routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    
    // Tickets
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('tickets/{ticket}/feedback', [TicketController::class, 'feedback'])->name('tickets.feedback');
});

// Admin routes
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/pending-review', [AdminController::class, 'pendingReview'])->name('admin.pending-review');
    
    // Ticket management
    Route::post('/tickets/{ticket}/approve', [AdminController::class, 'approve'])->name('admin.tickets.approve');
    Route::post('/tickets/{ticket}/reject', [AdminController::class, 'reject'])->name('admin.tickets.reject');
    Route::post('/tickets/{ticket}/revision', [AdminController::class, 'requestRevision'])->name('admin.tickets.revision');
    Route::post('/tickets/{ticket}/update-status', [AdminController::class, 'updateStatus'])->name('admin.tickets.update-status');
    Route::post('/tickets/{ticket}/close', [AdminController::class, 'close'])->name('admin.tickets.close');
    Route::post('/tickets/{ticket}/assign', [AdminController::class, 'assign'])->name('admin.tickets.assign');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('admin.reports.excel');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('admin.reports.pdf');
});
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
