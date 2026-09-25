<?php
namespace App\Providers;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;



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
        /*
        * Connexion :
        * maximum 5 tentatives toutes les 15 minutes
        * pour une combinaison téléphone + IP.
        */
        RateLimiter::for('connexion', function (Request $request) {
            $telephone = (string) $request->input('telephone');

            return Limit::perMinutes(15, 5)
                ->by(
                    'connexion:'.$telephone.'|'.$request->ip()
                );
        });

        /*
        * Vérification d'un OTP :
        * maximum 5 essais toutes les 10 minutes.
        */
        RateLimiter::for('otp_verification', function (Request $request) {
            $telephone = (string) $request->input('telephone');

            return Limit::perMinutes(10, 5)
                ->by(
                    'otp-verification:'.$telephone.'|'.$request->ip()
                );
        });

        /*
        * Envoi / génération d'un OTP :
        * maximum 3 demandes toutes les 10 minutes.
        */
        RateLimiter::for('otp_envoi', function (Request $request) {
            $telephone = (string) $request->input('telephone');

            return Limit::perMinutes(10, 3)
                ->by(
                    'otp-envoi:'.$telephone.'|'.$request->ip()
                );
        });
    }
}
