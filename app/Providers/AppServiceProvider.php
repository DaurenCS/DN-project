<?php

namespace App\Providers;

use App\Events\CertificateRequested;
use App\Interfaces\CertificateGeneratorInterface;
use App\Interfaces\DocumentGeneratorInterface;
use App\Listeners\SendCommissionNotificationListener;
use App\Services\CertificateService;
use App\Services\WordDocumentGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailjet\Transport\MailjetApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CertificateGeneratorInterface::class, CertificateService::class
        );
        $this->app->bind(
            DocumentGeneratorInterface::class, WordDocumentGenerator::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Event::listen(
            CertificateRequested::class,
            SendCommissionNotificationListener::class
        );

        Mail::extend('mailjet', function (array $config) {
            return new MailjetApiTransport(
                $config['key'],
                $config['secret']
            );
        });
    }
}
