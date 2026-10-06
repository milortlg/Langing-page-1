<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfilePageController;
use App\Models\Profile;
use Illuminate\Support\Facades\Route;

// Routes pour servir les images (sécurisées)
Route::get('/media/blurred/{mediaId}', [MediaController::class, 'showBlurred'])
    ->name('media.blurred')
    ->where('mediaId', '[0-9]+');

Route::get('/media/original/{mediaId}', [MediaController::class, 'showOriginal'])
    ->name('media.original')
    ->where('mediaId', '[0-9]+');

// Landing page par défaut (premier profil) : monvip.fr/
Route::get('/', [ProfilePageController::class, 'home'])->name('home');


// Route dashboard commentée - pas d'authentification publique
// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

// Landing page d'un profil : monvip.fr/{slug} (ex : monvip.fr/alycia)
// Doit rester en DERNIER pour ne pas masquer les autres pages du site.
Route::get('/{slug}', [ProfilePageController::class, 'show'])
    ->where('slug', '(?!('.implode('|', array_map('preg_quote', Profile::RESERVED_SLUGS)).')$)[a-z0-9-]+')
    ->name('profile.show');
