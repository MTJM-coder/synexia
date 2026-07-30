<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\ShopController;
use Modules\Marketplace\Http\Controllers\ShopSettingController;
use Modules\Marketplace\Http\Controllers\SubscriptionPlanController;

/**
 * IMPORTANT : pas de préfixe "api/" ici — routes/api.php l'ajoute déjà
 * automatiquement (voir Modules\Identity\routes.php pour l'explication complète).
 */

Route::get('/v1/subscription-plans', [SubscriptionPlanController::class, 'index'])
    ->name('marketplace.subscription-plans.index');

Route::prefix('v1/shops')
    ->name('marketplace.shops.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [ShopController::class, 'index'])->name('index');
        Route::post('/', [ShopController::class, 'store'])->name('store');
        Route::get('/{shop}', [ShopController::class, 'show'])->name('show');
        Route::patch('/{shop}', [ShopController::class, 'update'])->name('update');
        Route::patch('/{shop}/status', [ShopController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{shop}/subscription', [ShopController::class, 'subscribe'])->name('subscribe');

        Route::get('/{shop}/settings', [ShopSettingController::class, 'show'])->name('settings.show');
        Route::patch('/{shop}/settings', [ShopSettingController::class, 'update'])->name('settings.update');
    });
