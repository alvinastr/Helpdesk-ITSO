<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API for external integrations (WhatsApp, Email)
Route::prefix('v1')->group(function () {
    
    // Email Parser API (for admin form)
    Route::post('/parse-email', [App\Http\Controllers\Api\EmailParserController::class, 'parse']);
    
    // Webhook for incoming messages
    Route::post('/webhooks/email', [App\Http\Controllers\Api\WebhookController::class, 'handleEmail']);
    Route::post('/webhooks/whatsapp', [App\Http\Controllers\Api\WebhookController::class, 'handleWhatsApp']);
    
    // Public API (with API token)
    //Route::middleware('auth:sanctum')->group(function () {
        Route::get('/tickets', [App\Http\Controllers\Api\TicketApiController::class, 'index']);
        Route::post('/tickets', [App\Http\Controllers\Api\TicketApiController::class, 'store']);
        Route::get('/tickets/{ticket}', [App\Http\Controllers\Api\TicketApiController::class, 'show']);
    //});
});