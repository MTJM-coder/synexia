<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Str;
use Modules\Identity\Events\EmployeeInvited;
use Modules\Identity\Models\ShopEmployeeInvitation;

class ResendInvitationAction
{
    private const EXPIRATION_DAYS = 7;

    public function execute(ShopEmployeeInvitation $invitation): ShopEmployeeInvitation
    {
        if (! $invitation->isPending()) {
            throw new \DomainException('Seule une invitation en attente peut être renvoyée.');
        }

        // Nouveau token : l'ancien lien envoyé par email devient invalide,
        // volontairement — on ne veut pas deux liens valides en même temps
        // pour la même invitation.
        $plainToken = Str::random(64);

        $invitation->update([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(self::EXPIRATION_DAYS),
        ]);

        EmployeeInvited::dispatch($invitation->fresh(), $plainToken);

        return $invitation;
    }
}
