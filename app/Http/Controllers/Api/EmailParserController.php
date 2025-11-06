<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailParserService;
use Illuminate\Http\Request;

class EmailParserController extends Controller
{
    protected $emailParser;

    public function __construct(EmailParserService $emailParser)
    {
        $this->emailParser = $emailParser;
    }

    /**
     * Parse raw email content
     */
    public function parse(Request $request)
    {
        $request->validate([
            'raw_email' => 'required|string|min:10',
        ]);

        try {
            // Parse email
            $parsed = $this->emailParser->parseEmailContent($request->raw_email);
            
            // Format for form
            $formData = $this->emailParser->formatForForm($parsed);

            return response()->json([
                'success' => true,
                'data' => $formData,
                'raw_parsed' => $parsed, // For debugging
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse email: ' . $e->getMessage(),
            ], 400);
        }
    }
}
