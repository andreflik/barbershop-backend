<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        if (app()->isProduction()) {
            Model::preventLazyLoading();
            Model::shouldBeStrict();
            DB::disableQueryLog();
            URL::forceScheme('https');
        }

         Model::handleLazyLoadingViolationUsing(function($model, $relation) {
             Log::warning('Lazy loading: '.get_class($model).'->'.$relation);
         });
    }
}
