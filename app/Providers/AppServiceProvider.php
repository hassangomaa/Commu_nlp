<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
        }
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
