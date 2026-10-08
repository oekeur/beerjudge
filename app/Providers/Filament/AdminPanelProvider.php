<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RequireActiveMember;
use App\Http\Middleware\SetLocale;
use App\Support\SentryFeedbackLabels;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $clubName = config('app.club_name');
        $brandName = is_string($clubName) && $clubName !== '' ? $clubName.' BeerJudge' : 'BeerJudge';

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName($brandName)
            ->homeUrl(fn () => url('/'))
            ->login()
            ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetLocale::class,
                RequireActiveMember::class,
            ], isPersistent: true)
            ->renderHook('panels::head.end', function (): HtmlString {
                $dsn = is_string(config('sentry.dsn')) ? config('sentry.dsn') : '';
                $user = auth()->user();
                $userJson = $user !== null
                    ? SentryFeedbackLabels::attributeJson(['name' => $user->name, 'email' => $user->email])
                    : 'null';

                $labelsJson = SentryFeedbackLabels::json();

                return new HtmlString(<<<HTML
<meta name="sentry-dsn" content="{$dsn}">
<meta name="sentry-user" content='{$userJson}'>
<meta name="sentry-feedback-labels" content='{$labelsJson}'>
<script src="/vendor/error-report/bundle.js"></script>
<script>
(function () {
const dsn = document.querySelector('meta[name="sentry-dsn"]')?.content ?? '';
if (!dsn || !window.Sentry) {
    // No DSN configured, or the bundle failed to load — skip Sentry initialisation
} else {
    const { init, feedbackIntegration, setUser } = window.Sentry;
    let feedbackLabels = {};
    try {
        feedbackLabels = JSON.parse(document.querySelector('meta[name="sentry-feedback-labels"]')?.content ?? '{}');
    } catch (_) {
        // Malformed labels meta — fall back to the widget's English defaults
    }

    init({
        dsn,
        integrations: [
            feedbackIntegration({ colorScheme: 'light', autoInject: true, ...feedbackLabels }),
        ],
    });

    try {
        const raw = document.querySelector('meta[name="sentry-user"]')?.content ?? 'null';
        const userData = JSON.parse(raw);
        if (userData && typeof userData === 'object') {
            setUser(userData);
        }
    } catch (_) {
        // Malformed user meta — skip setUser
    }
}
})();
</script>
HTML);
            });
    }
}
