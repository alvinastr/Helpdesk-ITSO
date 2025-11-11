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

    // WhatsApp Integration endpoints (v1)
    Route::prefix('wa')->group(function () {
        Route::get('/health', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'health']);
        Route::post('/webhook', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'receive'])->middleware('whatsapp.api');
        
        // Threading support endpoints
        Route::post('/check-recent-ticket', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'checkRecentTicket']);
        Route::post('/append-message', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'appendMessage']);
    });
    
    // Public API (with API token)
    //Route::middleware('auth:sanctum')->group(function () {
        Route::get('/tickets', [App\Http\Controllers\Api\TicketApiController::class, 'index']);
        Route::post('/tickets', [App\Http\Controllers\Api\TicketApiController::class, 'store']);
        Route::get('/tickets/{ticket}', [App\Http\Controllers\Api\TicketApiController::class, 'show']);
    //});
});

// Backward compatible endpoints (root)
Route::get('/health', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'health']);
Route::post('/wa-webhook', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'receive'])->middleware('whatsapp.api');