<?php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Get CSS assets based on environment
     */
    public static function css($path = null)
    {
        if (app()->environment('production') || !config('app.vite_enabled', true)) {
            // Production: Use CDN or pre-built assets
            switch ($path) {
                case 'bootstrap':
                    return 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css';
                case 'fontawesome':
                    return 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
                default:
                    return asset('css/' . $path);
            }
        } else {
            // Development: Use Vite
            return null; // Let Vite handle it
        }
    }

    /**
     * Get JS assets based on environment
     */
    public static function js($path = null)
    {
        if (app()->environment('production') || !config('app.vite_enabled', true)) {
            // Production: Use CDN or pre-built assets
            switch ($path) {
                case 'bootstrap':
                    return 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js';
                case 'axios':
                    return 'https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js';
                default:
                    return asset('js/' . $path);
            }
        } else {
            // Development: Use Vite
            return null; // Let Vite handle it
        }
    }

    /**
     * Check if should use Vite
     */
    public static function shouldUseVite(): bool
    {
        return app()->environment('local', 'development') && config('app.vite_enabled', true);
    }
}