<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Numeric throttle:N,M keys guests on IP alone, so those routes share one counter.
        // A named limiter keeps GET and POST /magic-link/{token} off that shared counter.
        RateLimiter::for('magic-link-token', fn (Request $request): Limit => Limit::perMinute(5)->by('magic-link-token|'.$request->ip()));

        $clubName = is_string($v = config('app.club_name')) ? $v : '';
        View::share('appTitle', $clubName !== '' ? $clubName.' BeerJudge' : 'BeerJudge');
    }
}
