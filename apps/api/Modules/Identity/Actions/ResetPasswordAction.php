<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;

class ResetPasswordAction
{
    /**
     * @throws \DomainException si le token est invalide, expiré, ou l'email inconnu
     */
    public function execute(string $email, string $plainToken, string $newPassword): void
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($record === null) {
            throw new \DomainException('Aucune demande de réinitialisation en cours pour cet email.');
        }

        if (! hash_equals($record->token, hash('sha256', $plainToken))) {
            throw new \DomainException('Token de réinitialisation invalide.');
        }

        $expiresAt = \Carbon\Carbon::parse($record->created_at)
            ->addMinutes(RequestPasswordResetAction::tokenValidityMinutes());

        if (now()->greaterThan($expiresAt)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw new \DomainException('Ce lien de réinitialisation a expiré, merci d\'en redemander un.');
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw new \DomainException('Aucun compte associé à cet email.');
        }

        $user->forceFill(['password' => Hash::make($newPassword)])->save();

        // Usage unique : le token ne doit plus jamais fonctionner après ça.
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}