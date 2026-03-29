<?php

namespace App\Providers;

use App\Auth\TenantAwareEloquentUserProvider;
use App\Models\Lesson;
use App\Models\Module;
use App\Observers\LessonObserver;
use App\Observers\ModuleObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Auth::provider('tenant-aware-eloquent', function ($app, array $config) {
            return new TenantAwareEloquentUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Module::observe(ModuleObserver::class);
        Lesson::observe(LessonObserver::class);
    }
}
