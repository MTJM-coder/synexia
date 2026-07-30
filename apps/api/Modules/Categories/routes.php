<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\CategoryController;

/**
 * IMPORTANT : pas de préfixe "api/" ici — routes/api.php l'ajoute déjà
 * automatiquement (voir Modules\Identity\routes.php pour l'explication complète).
 *
 * index/show publics (navigation catalogue) ; store/update/destroy protégés,
 * autorisation vérifiée à l'intérieur du Controller (global -> Super Admin,
 * boutique -> propriétaire).
 */
Route::prefix('v1/categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{category}', [CategoryController::class, 'show'])->name('show');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::patch('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });
});
