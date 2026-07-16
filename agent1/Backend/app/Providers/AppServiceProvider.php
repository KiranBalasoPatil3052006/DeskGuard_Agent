<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Service bindings, repository registrations, and other
     * container entries are registered here.
     */
    public function register(): void
    {
        // Register repository bindings (services extend BaseRepository directly)
        // If interfaces are introduced later, bind them here:
        // $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);

        // Register custom service providers for agent, alert, and reporting modules.
        // These are auto-discovered by Laravel but can be explicitly registered:
        // $this->app->register(AgentServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Initialize default roles and permissions
        if (!app()->runningInConsole()) {
            // Will be handled by seeder
        }

        // Register query execution logger for performance monitoring.
        // Logs all queries taking longer than 200ms to the 'audit_log' channel.
        // This helps identify slow queries in production without external tools.
        if (config('app.debug') || config('database.log_slow_queries', false)) {
            DB::listen(function (QueryExecuted $query) {
                if ($query->time > 200) {
                    Log::channel('audit_log')->warning('Slow SQL Query', [
                        'sql'       => $query->sql,
                        'bindings'  => $query->bindings,
                        'time_ms'   => $query->time,
                        'connection'=> $query->connectionName,
                    ]);
                }
            });
        }
    }
}
