<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateWhatsAppApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('Authorization');
        $expectedKey = 'Bearer ' . config('services.whatsapp.api_key');

        if (!$apiKey || $apiKey !== $expectedKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Invalid API Key'
            ], 401);
        }

        return $next($request);
    }
}
