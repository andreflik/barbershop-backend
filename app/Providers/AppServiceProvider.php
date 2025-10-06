<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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
        // ⚙️ Configurações para ambiente de produção
        if (app()->isProduction()) {
            Model::preventLazyLoading();
            Model::shouldBeStrict();
            DB::disableQueryLog();
            URL::forceScheme('https');
        }

        // 📧 Envia cópia oculta de todos os e-mails para o Marquinhos
        if (env('MAIL_COPY_ADDRESS')) {
            Mail::alwaysBcc(env('MAIL_COPY_ADDRESS'), env('MAIL_COPY_NAME', 'Marquinhos'));
        }

        // 🔍 Loga tentativas de lazy loading (boas práticas)
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
            Log::warning('Lazy loading: ' . get_class($model) . '->' . $relation);
        });
    }
}
