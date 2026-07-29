<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Porte le token EN CLAIR une seule fois, au moment de l'émission — jamais
 * stocké tel quel en base (voir RequestPasswordResetAction, même principe
 * que EmployeeInvited). Le module Notifications (pas encore fonctionnel)
 * écoutera cet event pour composer l'email "Réinitialiser mon mot de passe".
 */
class PasswordResetRequested
{
    use Dispatchable;

    public function __construct(
        public readonly string $email,
        public readonly string $plainToken,
    ) {
    }
}