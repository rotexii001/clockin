<?php

namespace Edscool\Plugins\ClockIn;

use Illuminate\Support\ServiceProvider;

class ClockInPluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind any services or singletons if needed
    }

    /**
     * Bootstrap services for active schools.
     */
    public function boot(): void
    {
        // 1. Load isolated custom table migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 2. Load scoped plugin web routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // 3. Load namespaced Blade views (e.g. view('clockin::teacher-clockin'))
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'clockin');
    }
}
