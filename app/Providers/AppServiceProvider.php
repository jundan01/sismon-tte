<?php

namespace App\Providers;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Pendaftaran\Observers\AuditObserver;
use App\Domain\Sertifikat\Models\SertifikatTte;
use App\Policies\PendaftaranTtePolicy;
use App\Policies\SertifikatTtePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Otomatis paksa HTTPS jika diakses lewat HTTPS Tunnel/Proxy atau di environment production/URL https
        if (
            !$this->app->runningInConsole() && (
                str_starts_with(config('app.url', ''), 'https://') ||
                app()->environment('production') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            )
        ) {
            URL::forceScheme('https');
        }

        // Observer Transaksi Pendaftaran TTE
        PendaftaranTte::observe(AuditObserver::class);

        // Binding Kebijakan Otorisasi (Policies)
        Gate::policy(PendaftaranTte::class, PendaftaranTtePolicy::class);
        Gate::policy(SertifikatTte::class, SertifikatTtePolicy::class);
    }
}
