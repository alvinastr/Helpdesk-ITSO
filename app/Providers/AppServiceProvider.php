<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default pagination view to Bootstrap 5
        Paginator::useBootstrapFive();
        
        // Set Carbon locale to Indonesian
        Carbon::setLocale('id');
        
        // Set timezone for Carbon
        date_default_timezone_set('Asia/Jakarta');
        
        // Register custom Blade directive for translating status
        Blade::directive('translateStatus', function ($status) {
            return "<?php echo __('app.' . {$status}); ?>";
        });
        
        // Register custom Blade directive for translating priority
        Blade::directive('translatePriority', function ($priority) {
            return "<?php echo __('app.' . {$priority}); ?>";
        });
    }
}
