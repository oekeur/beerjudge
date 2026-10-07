<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        $clubName = is_string($v = config('app.club_name')) ? $v : '';
        View::share('appTitle', $clubName !== '' ? $clubName.' BeerJudge' : 'BeerJudge');
    }
}
