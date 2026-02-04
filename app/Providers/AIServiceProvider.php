<?php

namespace App\Providers;

use App\Services\AIQuestionGeneratorService;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AIQuestionGeneratorService::class, function ($app) {
            return new AIQuestionGeneratorService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}