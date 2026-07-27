<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;

class CreateShopEmployeeAction
{
    /**
     * @param  array{first_name:string,last_name:string,email:string,phone?:string,job_title?:string}  $userData
     */
    public function execute(Shop $shop, array $userData, Role $role, int $invitedByUserId): ShopEmployee
    {
        if ($role->guard_scope !== Role::GUARD_SHOP) {
            throw new \InvalidArgumentException('Seul un rôle de type boutique peut être assigné à un employé.');
        }

        return DB::transaction(function () use ($shop, $userData, $role, $invitedByUserId) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'phone' => $userData['phone'] ?? null,
                    'password' => bcrypt(Str::random(32)), // l'utilisateur définira son mot de passe via l'invitation
                    'status' => 'pending',
                ],
            );

            $employee = ShopEmployee::create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'job_title' => $userData['job_title'] ?? null,
                'status' => ShopEmployee::STATUS_ACTIVE,
                'hired_at' => now(),
            ]);

            EmployeeRoleAssigned::dispatch($employee, null, $role, $invitedByUserId);

            return $employee->fresh(['user', 'role']);
        });
    }
}
