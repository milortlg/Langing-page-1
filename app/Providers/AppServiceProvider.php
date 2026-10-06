<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        $this->runPendingMigrationsOnce();
    }

    /**
     * Mise à jour automatique (hébergement sans accès terminal) :
     * à la première visite après l'ajout d'une nouvelle migration,
     * applique les migrations en attente puis vide les caches.
     *
     * Un fichier "verrou" (nommé d'après la dernière migration) évite de
     * relancer la vérification à chaque visite.
     */
    private function runPendingMigrationsOnce(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $migrations = glob(database_path('migrations/*.php')) ?: [];
        if (! $migrations) {
            return;
        }
        sort($migrations);
        $latest = basename(end($migrations), '.php');

        $flag = storage_path('framework/migrated-'.$latest);
        if (file_exists($flag)) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            Log::info('Migrations appliquées automatiquement.', ['jusqu_a' => $latest]);

            @file_put_contents($flag, now()->toDateTimeString());
        } catch (Throwable $e) {
            // Pas de verrou : nouvelle tentative à la prochaine visite.
            Log::error('Migration automatique impossible : '.$e->getMessage());
        }
    }
}
