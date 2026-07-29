<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Models\User;

class VerifyEmailAction
{
    /**
     * La validité de la signature elle-même (temporary signed route) est
     * vérifiée par le middleware "signed" AVANT que cette Action soit
     * appelée — elle ne revérifie que la correspondance hash <-> email,
     * pour se protéger d'un lien valide mais pointant sur le mauvais compte.
     *
     * @throws \DomainException si le hash ne correspond pas à l'email actuel de l'utilisateur
     */
    public function execute(User $user, string $hash): void
    {
        if (! hash_equals(sha1($user->email ?? ''), $hash)) {
            throw new \DomainException('Lien de vérification invalide.');
        }

        if ($user->email_verified_at !== null) {
            return; // idempotent
        }

        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
