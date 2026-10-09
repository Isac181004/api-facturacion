<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\CreateDirectoryStructure;
use App\Console\Commands\PrivatizeCompanyDocumentFiles;
use App\Support\TenantContext;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateDirectoryStructure::class,
                PrivatizeCompanyDocumentFiles::class,
            ]);
        }
    }
}
