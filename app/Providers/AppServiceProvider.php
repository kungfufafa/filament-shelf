<?php

namespace App\Providers;

use App\Services\Core\CoreSsoProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        $socialite = $this->app->make(Factory::class);
        $socialite->extend('core', function ($app) use ($socialite) {
            $config = $app['config']['services.core'];
            return $socialite->buildProvider(CoreSsoProvider::class, $config);
        });
    }
}
