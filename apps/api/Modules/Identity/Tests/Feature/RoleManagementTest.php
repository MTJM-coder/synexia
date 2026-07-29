<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_owner_role(): void
    {
        // is_super_admin : on isole ici la règle métier "un rôle système ne
        // se supprime pas", sans mélanger avec la vérification d'autorisation
        // (testée ailleurs, dans un test dédié aux permissions).
        /** @var User $user */
        $user = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);
        $shop = Shop::factory()->create(['owner_id' => $user->id]);

        $ownerRole = Role::factory()->create([
            'name' => 'Owner',
            'slug' => 'owner',
            'is_system' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/shops/{$shop->id}/roles/{$ownerRole->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_role_assigned_to_employees(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);
        $shop = Shop::factory()->create(['owner_id' => $user->id]);
        $role = Role::factory()->create(['is_system' => false]);

        ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'status' => ShopEmployee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/shops/{$shop->id}/roles/{$role->id}");

        $response->assertStatus(422);
    }
}
