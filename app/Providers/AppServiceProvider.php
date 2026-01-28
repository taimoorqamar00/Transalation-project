<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\TranslationInterface;
use App\Repositories\TranslationRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
        TranslationInterface::class,
        TranslationRepository::class
    );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
