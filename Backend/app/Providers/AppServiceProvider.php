<?php

namespace App\Providers;

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
    }
}
