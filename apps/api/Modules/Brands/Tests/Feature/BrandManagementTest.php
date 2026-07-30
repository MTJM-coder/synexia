<?php

namespace Modules\Brands\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Brands\Models\Brand;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_global_brands(): void
    {
        Brand::factory()->count(2)->create();

        $this->getJson('/api/v1/brands')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_super_admin_can_create_a_global_brand(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/brands', ['name' => 'Samsung'])->assertCreated();

        $this->assertDatabaseHas('brands', ['name' => 'Samsung', 'shop_id' => null]);
    }

    public function test_regular_user_cannot_create_a_global_brand(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/brands', ['name' => 'Samsung'])->assertStatus(403);
    }

    public function test_shop_owner_can_create_their_own_brand(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/brands', [
            'shop_id' => $shop->id,
            'name' => 'Marque Maison',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('brands', ['name' => 'Marque Maison', 'shop_id' => $shop->id]);
    }

    public function test_brand_can_be_updated_without_touching_created_at(): void
    {
        // Vérifie explicitement que le modèle gère bien l'absence de
        // updated_at (pas d'exception "colonne inconnue" à la sauvegarde).
        $admin = User::factory()->create(['is_super_admin' => true]);
        $brand = Brand::factory()->create(['name' => 'Ancien Nom']);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/brands/{$brand->id}", ['name' => 'Nouveau Nom'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau Nom');
    }
}
