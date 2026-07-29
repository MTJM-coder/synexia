<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\URL;
use Modules\Identity\Events\EmailVerificationRequested;
use Modules\Identity\Models\User;

class SendEmailVerificationAction
{
    private const EXPIRATION_MINUTES = 60;

    public function execute(User $user): void
    {
        if ($user->email === null) {
            throw new \DomainException("Ce compte n'a pas d'adresse email — rien à vérifier.");
        }

        if ($user->email_verified_at !== null) {
            return; // idempotent, pas d'erreur si déjà vérifié
        }

        $signedUrl = URL::temporarySignedRoute(
            'identity.auth.verify-email',
            now()->addMinutes(self::EXPIRATION_MINUTES),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ],
        );

        EmailVerificationRequested::dispatch($user, $signedUrl);
    }
}
