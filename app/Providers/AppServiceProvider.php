<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
     * à la première visite après un déploiement, applique les migrations
     * en attente (ex : multi-profils) puis vide les caches.
     *
     * Un fichier "verrou" dans storage/ évite de refaire la vérification
     * à chaque visite : le coût est d'un simple file_exists().
     */
    private function runPendingMigrationsOnce(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $flag = storage_path('framework/migrated-multi-profils');
        if (file_exists($flag)) {
            return;
        }

        try {
            if (Schema::hasTable('profiles') && ! Schema::hasColumn('profiles', 'slug')) {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('optimize:clear');
                Log::info('Migrations multi-profils appliquées automatiquement.', ['output' => Artisan::output()]);
            }

            @file_put_contents($flag, now()->toDateTimeString());
        } catch (Throwable $e) {
            // On ne crée pas le verrou : nouvelle tentative à la prochaine visite.
            Log::error('Migration automatique impossible : '.$e->getMessage());
        }
    }
}
