<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Numeric throttle:N,M keys guests on IP alone, so those routes share one counter.
        // Named limiters give each route its own. Login also keys on email, so members
        // sharing one club Wi-Fi IP do not lock each other out.
        RateLimiter::for('login', function (Request $request): array {
            $email = $request->input('email');
            $perEmailKey = is_string($email)
                ? 'login|'.Str::lower($email).'|'.$request->ip()
                : 'login|'.$request->ip();

            return [
                Limit::perMinute(6)->by($perEmailKey),
                Limit::perMinute(30)->by('login-ip|'.$request->ip()),
            ];
        });
        RateLimiter::for('magic-link-request', fn (Request $request): Limit => Limit::perMinutes(5, 3)->by('magic-link-request|'.$request->ip()));
        RateLimiter::for('password-reset-confirm', fn (Request $request): Limit => Limit::perMinute(5)->by('password-reset-confirm|'.$request->ip()));
        RateLimiter::for('magic-link-token', fn (Request $request): Limit => Limit::perMinute(5)->by('magic-link-token|'.$request->ip()));

        $clubName = is_string($v = config('app.club_name')) ? $v : '';
        View::share('appTitle', $clubName !== '' ? $clubName.' BeerJudge' : 'BeerJudge');
    }
}
