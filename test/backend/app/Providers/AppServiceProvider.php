<?php

namespace App\Providers;

use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
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

        // Send welcome email when user registers
        Event::listen(Registered::class, function (Registered $event) {
            Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
        });
    }
}
