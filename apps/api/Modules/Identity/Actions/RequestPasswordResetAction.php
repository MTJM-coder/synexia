<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Identity\Events\PasswordResetRequested;
use Modules\Identity\Models\User;

class RequestPasswordResetAction
{
    private const TOKEN_VALID_MINUTES = 60;

    /**
     * Ne révèle JAMAIS si l'email existe ou non (retour identique dans les
     * deux cas côté Controller) — sinon on donne un moyen d'énumérer les
     * comptes existants. Cette Action, elle, sait — mais l'appelant ne doit
     * pas transformer ça en réponse HTTP différente.
     */
    public function execute(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $plainToken = Str::random(64);

        // "email" est la clé primaire de password_reset_tokens (schéma par
        // défaut de Laravel) : une nouvelle demande remplace silencieusement
        // l'ancienne — un seul token valide à la fois par compte.
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => hash('sha256', $plainToken),
                'created_at' => now(),
            ],
        );

        PasswordResetRequested::dispatch($email, $plainToken);
    }

    public static function tokenValidityMinutes(): int
    {
        return self::TOKEN_VALID_MINUTES;
    }
}