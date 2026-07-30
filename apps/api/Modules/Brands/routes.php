<?php

use Illuminate\Support\Facades\Route;
use Modules\Brands\Http\Controllers\BrandController;

Route::prefix('v1/brands')->name('brands.')->group(function () {
    Route::get('/', [BrandController::class, 'index'])->name('index');
    Route::get('/{brand}', [BrandController::class, 'show'])->name('show');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::patch('/{brand}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
    });
});
