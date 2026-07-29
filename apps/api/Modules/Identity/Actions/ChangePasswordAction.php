<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;

class ChangePasswordAction
{
    /**
     * @throws \DomainException si le mot de passe actuel est incorrect
     */
    public function execute(User $user, string $currentPassword, string $newPassword, bool $revokeOtherSessions = true): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \DomainException('Le mot de passe actuel est incorrect.');
        }

        $user->forceFill(['password' => Hash::make($newPassword)])->save();

        if ($revokeOtherSessions) {
            // Révoque tous les tokens SAUF celui utilisé pour cette requête —
            // changer son mot de passe doit fermer les autres sessions par
            // précaution (ex: appareil volé), mais pas déconnecter la session
            // qui vient de faire le changement.
            $currentTokenId = $user->currentAccessToken()?->id;

            $user->tokens()
                ->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))
                ->delete();
        }
    }
}
