<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\AuthController;
use Modules\Identity\Http\Controllers\EmployeeInvitationController;
use Modules\Identity\Http\Controllers\ProfileController;
use Modules\Identity\Http\Controllers\RoleController;
use Modules\Identity\Http\Controllers\SessionController;
use Modules\Identity\Http\Controllers\ShopEmployeeController;
use Modules\Identity\Http\Controllers\ShopInvitationController;

/**
 * IMPORTANT : pas de préfixe "api/" ici — routes/api.php l'ajoute déjà.
 */

Route::prefix('v1/auth')->name('identity.auth.')->group(function () {
    // Rate limiting explicite : ces 3 endpoints sont les cibles classiques
    // de brute-force / d'énumération de comptes.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    });

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

    // Signature temporaire (URL::temporarySignedRoute) : le lien expire et
    // ne peut pas être trafiqué (id/hash), donc pas besoin d'auth:sanctum ici.
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verify-email');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/email/resend', [AuthController::class, 'resendEmailVerification'])
            ->middleware('throttle:3,1')
            ->name('email.resend');
    });
});

Route::prefix('v1/me')->name('identity.profile.')->middleware(['auth:sanctum'])->group(function () {
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->name('avatar');
    Route::patch('/password', [ProfileController::class, 'changePassword'])->name('change-password');
});

Route::prefix('v1/me/sessions')->name('identity.sessions.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [SessionController::class, 'index'])->name('index');
    Route::delete('/others', [SessionController::class, 'destroyOthers'])->name('destroy-others');
    Route::delete('/{tokenId}', [SessionController::class, 'destroy'])->name('destroy');
});

Route::get('/v1/me/login-history', [SessionController::class, 'loginHistory'])
    ->middleware(['auth:sanctum'])
    ->name('identity.login-history');

// Création d'invitation + acceptation — seul point d'entrée pour rejoindre
// une boutique (voir décision d'architecture validée).
Route::prefix('v1/employees')->name('identity.employees.')->group(function () {
    Route::middleware(['auth:sanctum'])->post('/invite', [EmployeeInvitationController::class, 'invite'])->name('invite');
    Route::post('/invitations/accept', [EmployeeInvitationController::class, 'accept'])->name('invitations.accept');
});

Route::prefix('v1/shops/{shop}/employees')
    ->name('identity.shop-employees.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [ShopEmployeeController::class, 'index'])->name('index');
        Route::get('/{shopEmployee}', [ShopEmployeeController::class, 'show'])->name('show');
        Route::patch('/{shopEmployee}/role', [ShopEmployeeController::class, 'updateRole'])->name('update-role');
        Route::patch('/{shopEmployee}/permissions', [ShopEmployeeController::class, 'setPermission'])->name('set-permission');
        Route::patch('/{shopEmployee}/suspend', [ShopEmployeeController::class, 'suspend'])->name('suspend');
        Route::patch('/{shopEmployee}/reactivate', [ShopEmployeeController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{shopEmployee}', [ShopEmployeeController::class, 'destroy'])->name('destroy');
    });

Route::prefix('v1/shops/{shop}/invitations')
    ->name('identity.shop-invitations.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [ShopInvitationController::class, 'index'])->name('index');
        Route::post('/{invitation}/cancel', [ShopInvitationController::class, 'cancel'])->name('cancel');
        Route::post('/{invitation}/resend', [ShopInvitationController::class, 'resend'])->name('resend');
    });

Route::prefix('v1/shops/{shop}/roles')
    ->name('identity.roles.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::patch('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::patch('/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
    });
