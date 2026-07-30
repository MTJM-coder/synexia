<?php

namespace Modules\Categories\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Categories\Models\Category;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_global_categories(): void
    {
        Category::factory()->count(3)->create(['shop_id' => null]);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_super_admin_can_create_a_global_category(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', ['name' => 'Électronique']);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', ['name' => 'Électronique', 'shop_id' => null]);
    }

    public function test_regular_user_cannot_create_a_global_category(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/categories', ['name' => 'Électronique'])
            ->assertStatus(403);
    }

    public function test_shop_owner_can_create_a_shop_specific_category(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/categories', [
            'shop_id' => $shop->id,
            'name' => 'Promotions maison',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', ['name' => 'Promotions maison', 'shop_id' => $shop->id]);
    }

    public function test_non_owner_cannot_create_category_for_someone_elses_shop(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($intruder);

        $this->postJson('/api/v1/categories', ['shop_id' => $shop->id, 'name' => 'Intrusion'])
            ->assertStatus(403);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $category = Category::factory()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/categories/{$category->id}", ['parent_id' => $category->id])
            ->assertStatus(422);
    }

    public function test_show_includes_children(): void
    {
        $parent = Category::factory()->create(['name' => 'Vêtements']);
        Category::factory()->childOf($parent)->create(['name' => 'Hommes']);

        $response = $this->getJson("/api/v1/categories/{$parent->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.children');
    }
}
