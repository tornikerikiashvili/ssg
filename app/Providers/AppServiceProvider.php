<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Banner;
use App\Models\PortalNotification;
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
        \Illuminate\Support\Facades\View::composer(['client.dashboard', 'client.documentation'], function (View $view): void {
            $user = auth()->user();
            $view->with('notifications', $user
                ? PortalNotification::visibleTo($user)->latest()->orderByDesc('id')->limit(4)->get()
                : collect());
            $view->with('announcements', $user
                ? Announcement::visibleTo($user)->where('show_on_dashboard', true)->latest()->orderByDesc('id')->limit(4)->get()
                : collect());
        });

        \Illuminate\Support\Facades\View::composer('layouts.original-client', function (View $view): void {
            $user = auth()->user();
            $view->with('headerNotifications', $user
                ? PortalNotification::visibleTo($user)->latest()->orderByDesc('id')->limit(4)->get()
                : collect());
        });

        \Illuminate\Support\Facades\View::composer(['client.dashboard', 'client.games', 'client.roadmap'], function (View $view): void {
            $user = auth()->user();
            $page = substr($view->name(), strlen('client.'));
            $view->with('pageBanners', $user
                ? Banner::visibleTo($user)->whereJsonContains('pages', $page)->with('game.volatility')->orderBy('sort_order')->orderBy('id')->when($page === 'dashboard', fn ($query) => $query->limit(1))->get()
                : collect());
        });
    }
}
