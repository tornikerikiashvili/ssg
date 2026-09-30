<?php

namespace App\Providers;

use App\Models\Banner;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer(['client.dashboard', 'client.games', 'client.roadmap'], function (View $view): void {
            $user = auth()->user();
            $page = substr($view->name(), strlen('client.'));
            $view->with('pageBanners', $user
                ? Banner::visibleTo($user)->whereJsonContains('pages', $page)->with('game.volatility')->orderBy('sort_order')->orderBy('id')->get()
                : collect());
        });
    }
}
