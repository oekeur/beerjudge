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
        // A club evening puts everyone behind one IP (shared Wi-Fi), so each limit is keyed per
        // email or per link, with a per-IP cap sized for about 15 members at once.
        RateLimiter::for('magic-link-request', fn (Request $request): array => [
            Limit::perMinutes(5, 3)->by('magic-link-request|'.self::emailKey($request).'|'.$request->ip()),
            Limit::perMinutes(5, 45)->by('magic-link-request-ip|'.$request->ip()),
        ]);
        RateLimiter::for('password-reset-confirm', fn (Request $request): array => [
            Limit::perMinute(5)->by('password-reset-confirm|'.self::emailKey($request).'|'.$request->ip()),
            Limit::perMinute(30)->by('password-reset-confirm-ip|'.$request->ip()),
        ]);
        // Opening a link costs a GET (confirm page) and a POST (log in). The per-IP cap bounds
        // guessing; a guess at a 64-character token is not a practical attack at that rate.
        RateLimiter::for('magic-link-token', fn (Request $request): array => [
            Limit::perMinute(5)->by('magic-link-token|'.self::tokenKey($request).'|'.$request->ip()),
            Limit::perMinute(60)->by('magic-link-token-ip|'.$request->ip()),
        ]);

        $clubName = is_string($v = config('app.club_name')) ? $v : '';
        View::share('appTitle', $clubName !== '' ? $clubName.' BeerJudge' : 'BeerJudge');
    }

    /** Lowercased email from the request, or '' when it is missing or not a string. */
    private static function emailKey(Request $request): string
    {
        $email = $request->input('email');

        return is_string($email) ? Str::lower($email) : '';
    }

    /** Hash of the magic-link token in the route, so the plain token never lands in the cache. */
    private static function tokenKey(Request $request): string
    {
        $token = $request->route('token');

        return sha1(is_string($token) ? $token : '');
    }
}
