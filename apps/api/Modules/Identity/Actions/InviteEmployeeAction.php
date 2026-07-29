<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Str;
use Modules\Identity\Events\EmployeeInvited;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeeInvitation;

class InviteEmployeeAction
{
    private const EXPIRATION_DAYS = 7;

    public function execute(int $shopId, string $email, Role $role, int $invitedByUserId): ShopEmployeeInvitation
    {
        if ($role->guard_scope !== Role::GUARD_SHOP) {
            throw new \InvalidArgumentException('Seul un rôle de type boutique peut être proposé par invitation.');
        }

        $alreadyMember = ShopEmployee::query()
            ->where('shop_id', $shopId)
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->exists();

        if ($alreadyMember) {
            throw new \DomainException('Cette personne est déjà employée de cette boutique.');
        }

        // Le token en clair n'est JAMAIS stocké — seul son hash SHA-256 vit en
        // base. Il ne redevient disponible qu'une fois, via l'event ci-dessous,
        // pour que Notifications puisse composer le lien à envoyer par email.
        $plainToken = Str::random(64);

        $invitation = ShopEmployeeInvitation::create([
            'shop_id' => $shopId,
            'email' => $email,
            'role_id' => $role->id,
            'invited_by' => $invitedByUserId,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(self::EXPIRATION_DAYS),
            'status' => ShopEmployeeInvitation::STATUS_PENDING,
        ]);

        EmployeeInvited::dispatch($invitation, $plainToken);

        return $invitation;
    }
}
