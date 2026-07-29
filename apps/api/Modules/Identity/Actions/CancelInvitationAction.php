<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Models\ShopEmployeeInvitation;

class CancelInvitationAction
{
    public function execute(ShopEmployeeInvitation $invitation): ShopEmployeeInvitation
    {
        if (! $invitation->isPending()) {
            throw new \DomainException('Seule une invitation en attente peut être annulée.');
        }

        $invitation->update(['status' => ShopEmployeeInvitation::STATUS_CANCELLED]);

        return $invitation;
    }
}
