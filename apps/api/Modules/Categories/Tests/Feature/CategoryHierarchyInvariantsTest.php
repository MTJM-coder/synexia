<?php

namespace Modules\Categories\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Categories\Models\Category;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class CategoryHierarchyInvariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $category = Category::factory()->create();

        $this->withHeader('Authorization', "Bearer {$superAdmin->createToken('t')->plainTextToken}")
            ->patchJson("/api/v1/categories/{$category->id}", ['parent_id' => $category->id])
            ->assertStatus(422);
    }

    public function test_no_cycle_is_allowed_in_the_tree(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);

        // Tentative : faire de root un enfant de child -> cycle root -> child -> root
        $this->withHeader('Authorization', "Bearer {$superAdmin->createToken('t')->plainTextToken}")
            ->patchJson("/api/v1/categories/{$root->id}", ['parent_id' => $child->id])
            ->assertStatus(422);
    }

    public function test_parent_must_belong_to_the_same_shop_scope(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $shop = Shop::factory()->create();
        $shopCategory = Category::factory()->create(['shop_id' => $shop->id]);

        // Une catégorie globale ne peut pas prendre un parent de boutique.
        $this->withHeader('Authorization', "Bearer {$superAdmin->createToken('t')->plainTextToken}")
            ->postJson('/api/v1/categories', [
                'name' => 'Catégorie Globale Invalide',
                'parent_id' => $shopCategory->id,
            ])
            ->assertStatus(422);
    }

    public function test_owner_can_create_a_shop_category_under_their_own_shop_parent(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $parent = Category::factory()->create(['shop_id' => $shop->id]);

        $this->withHeader('Authorization', "Bearer {$owner->createToken('t')->plainTextToken}")
            ->postJson('/api/v1/categories', [
                'name' => 'Sous-catégorie valide',
                'shop_id' => $shop->id,
                'parent_id' => $parent->id,
            ])
            ->assertCreated();
    }

    public function test_cannot_delete_a_category_with_children(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->withHeader('Authorization', "Bearer {$superAdmin->createToken('t')->plainTextToken}")
            ->deleteJson("/api/v1/categories/{$parent->id}")
            ->assertStatus(422);
    }

    public function test_can_delete_a_leaf_category_without_children(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $leaf = Category::factory()->create();

        $this->withHeader('Authorization', "Bearer {$superAdmin->createToken('t')->plainTextToken}")
            ->deleteJson("/api/v1/categories/{$leaf->id}")
            ->assertOk();
    }
}
