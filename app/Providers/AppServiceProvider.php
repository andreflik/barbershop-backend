<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
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
        // ⚙️ Configurações para produção
        if (app()->isProduction()) {
            Model::preventLazyLoading();
            Model::shouldBeStrict();
            DB::disableQueryLog();
            URL::forceScheme('https');
        }

        // 📧 Adiciona BCC (cópia oculta) em todos os e-mails enviados
        if (env('MAIL_COPY_ADDRESS')) {
            Event::listen(MessageSending::class, function (MessageSending $event) {
                $event->message->addBcc(env('MAIL_COPY_ADDRESS'));
            });
        }

        // 🔍 Loga lazy loading
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
            Log::warning('Lazy loading: ' . get_class($model) . '->' . $relation);
        });
    }
}
