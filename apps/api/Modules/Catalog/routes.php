<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AttributeTypeController;
use Modules\Catalog\Http\Controllers\AttributeValueController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\ProductImageController;
use Modules\Catalog\Http\Controllers\ProductSearchController;
use Modules\Catalog\Http\Controllers\ProductVariantController;
use Modules\Catalog\Http\Controllers\ProductVideoController;

/**
 * IMPORTANT : pas de préfixe "api/" ici — routes/api.php l'ajoute déjà
 * automatiquement (voir Modules\Identity\routes.php pour l'explication complète).
 */

// Recherche marketplace-wide, publique, pas scopée par boutique.
Route::get('/v1/products/search', [ProductSearchController::class, 'index'])
    ->name('catalog.products.search');

Route::prefix('v1/shops/{shop}/products')
    ->name('catalog.products.')
    ->group(function () {
        // index/show accessibles sans authentification (produits publiés) ;
        // l'accès aux brouillons est vérifié à l'intérieur du Controller
        // selon la permission "products.view" si un utilisateur est connecté.
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
         Route::get('/{product}/variants', [ProductVariantController::class, 'index'])->name('variants.index');

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::patch('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
            Route::post('/{product}/publish', [ProductController::class, 'publish'])->name('publish');
            Route::post('/{product}/archive', [ProductController::class, 'archive'])->name('archive');

           
            Route::post('/{product}/variants/generate', [ProductVariantController::class, 'generate'])->name('variants.generate');
            Route::patch('/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');

            Route::post('/{product}/images', [ProductImageController::class, 'store'])->name('images.store');
            Route::patch('/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary'])->name('images.set-primary');
            Route::delete('/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('images.destroy');

            // NOUVEAU : gestion des vidéos, absente jusqu'ici.
            Route::post('/{product}/videos', [ProductVideoController::class, 'store'])->name('videos.store');
            Route::delete('/{product}/videos/{video}', [ProductVideoController::class, 'destroy'])->name('videos.destroy');
        });
    });

Route::prefix('v1/shops/{shop}/attribute-types')
    ->name('catalog.attribute-types.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [AttributeTypeController::class, 'index'])->name('index');
        Route::post('/', [AttributeTypeController::class, 'store'])->name('store');
        Route::patch('/{attributeType}', [AttributeTypeController::class, 'update'])->name('update'); // NOUVEAU
        Route::delete('/{attributeType}', [AttributeTypeController::class, 'destroy'])->name('destroy');

        Route::post('/{attributeType}/values', [AttributeValueController::class, 'store'])->name('values.store');
        Route::patch('/{attributeType}/values/{value}', [AttributeValueController::class, 'update'])->name('values.update'); // NOUVEAU
        Route::delete('/{attributeType}/values/{value}', [AttributeValueController::class, 'destroy'])->name('values.destroy');
    });
