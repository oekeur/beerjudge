<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * app()->setLocale() overwrites config('app.locale'), so the configured default
     * is captured on first use. Without this, a long-lived process (tests, Octane)
     * would treat the previous request's locale as the default.
     */
    private static ?string $defaultLocale = null;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Member) {
            app()->setLocale($user->locale ?: self::defaultLocale());
        } else {
            app()->setLocale($this->guestLocale($request));
        }

        return $next($request);
    }

    /**
     * Also read by mail sent from guest routes, where config('app.locale') already holds
     * the guest's Accept-Language match.
     */
    public static function defaultLocale(): string
    {
        return self::$defaultLocale ??= Config::string('app.locale');
    }

    /**
     * Picks the best Accept-Language match among the locales that have a lang/*.json file.
     * The app default is listed first because getPreferredLanguage() returns the first
     * entry when the header is missing or matches nothing.
     */
    private function guestLocale(Request $request): string
    {
        $default = self::defaultLocale();
        $supported = collect(glob(lang_path('*.json')) ?: [])
            ->map(fn (string $path): string => basename($path, '.json'))
            ->reject(fn (string $locale): bool => $locale === $default)
            ->prepend($default)
            ->values()
            ->all();

        return $request->getPreferredLanguage($supported) ?? $default;
    }
}
