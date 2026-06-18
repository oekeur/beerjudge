<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->login()
            ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
            ])
            ->renderHook('panels::head.end', function (): HtmlString {
                $dsn = (string) (config('sentry.dsn') ?? '');
                $user = auth()->user();
                $userJson = $user !== null
                    ? json_encode(['name' => $user->name, 'email' => $user->email])
                    : 'null';

                return new HtmlString(<<<HTML
<meta name="sentry-dsn" content="{$dsn}">
<meta name="sentry-user" content='{$userJson}'>
<script type="module">
import { init, feedbackIntegration, setUser } from 'https://browser.sentry-cdn.com/10.58.0/bundle.esm.min.js';

const dsn = document.querySelector('meta[name="sentry-dsn"]')?.content ?? '';
if (!dsn) {
    // No DSN configured — skip Sentry initialisation
} else {
    init({
        dsn,
        integrations: [
            feedbackIntegration({ colorScheme: 'light', autoInject: true }),
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
</script>
HTML);
            });
    }
}
