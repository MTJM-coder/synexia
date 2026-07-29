<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Events\EmployeeJoinedShop;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeeInvitation;
use Modules\Identity\Models\User;

class AcceptShopInvitationAction
{
    public function __construct(
        private readonly RegisterUserAction $registerUser,
    ) {}

    /**
     * @param  User|null  $authenticatedUser  utilisateur déjà connecté qui accepte l'invitation
     * @param  array{first_name:string,last_name:string,password:string}|null  $newUserData
     *         requis uniquement si $authenticatedUser est null ET qu'aucun compte
     *         n'existe déjà pour l'email de l'invitation
     */
    public function execute(
        string $plainToken,
        ?User $authenticatedUser,
        ?array $newUserData,
    ): ShopEmployee {
        $invitation = ShopEmployeeInvitation::query()
            ->where('token', hash('sha256', $plainToken))
            ->first();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => ShopEmployeeInvitation::STATUS_EXPIRED]);

            throw new \DomainException('Cette invitation a expiré.');
        }
        if (! $invitation || ! $invitation->isPending()) {
            throw new \DomainException('Invitation invalide ou déjà utilisée.');
        }

        if ($authenticatedUser && $authenticatedUser->email !== $invitation->email) {
            throw new \DomainException("Cette invitation est destinée à une autre adresse email.");
        }

        return DB::transaction(function () use ($invitation, $authenticatedUser, $newUserData) {
            $user = $authenticatedUser
                ?? User::where('email', $invitation->email)->first()
                ?? $this->registerUser->execute([
                    ...$newUserData,
                    'email' => $invitation->email,
                ]);

            $employee = ShopEmployee::create([
                'shop_id' => $invitation->shop_id,
                'user_id' => $user->id,
                'role_id' => $invitation->role_id,
                'status' => ShopEmployee::STATUS_ACTIVE,
                'hired_at' => now(),
            ]);

            $invitation->update([
                'status' => ShopEmployeeInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            EmployeeRoleAssigned::dispatch($employee, null, $invitation->role, $invitation->invited_by);
            EmployeeJoinedShop::dispatch($employee);

            return $employee->fresh(['user', 'role']);
        });
    }
}
