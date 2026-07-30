<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;
use Tests\TestCase;

class ShopUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_general_shop_information(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id, 'name' => 'Ancien Nom']);

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/v1/shops/{$shop->id}", [
            'name' => 'Nouveau Nom',
            'slogan' => 'La meilleure boutique',
            'city' => 'Douala',
            'social_links' => ['facebook' => 'https://facebook.com/nouvelle-boutique'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Nouveau Nom');
        $response->assertJsonPath('data.slogan', 'La meilleure boutique');

        $this->assertDatabaseHas('shops', ['id' => $shop->id, 'name' => 'Nouveau Nom', 'city' => 'Douala']);
    }

    public function test_non_owner_cannot_update_shop_information(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->patchJson("/api/v1/shops/{$shop->id}", ['name' => 'Boutique Volée'])
            ->assertForbidden();

        $this->assertDatabaseMissing('shops', ['id' => $shop->id, 'name' => 'Boutique Volée']);
    }

    public function test_status_and_subscription_plan_are_ignored_on_general_update(): void
    {
        $owner = User::factory()->create();
        $originalPlan = SubscriptionPlan::factory()->create();
        $otherPlan = SubscriptionPlan::factory()->create();
        $shop = Shop::factory()->create([
            'owner_id' => $owner->id,
            'subscription_plan_id' => $originalPlan->id,
            'status' => Shop::STATUS_PENDING,
        ]);

        Sanctum::actingAs($owner);

        // Tentative de contourner les endpoints dédiés (status/abonnement)
        // via la mise à jour générale : ces champs ne sont pas dans les
        // règles de validation, ils doivent être silencieusement ignorés.
        $this->patchJson("/api/v1/shops/{$shop->id}", [
            'name' => 'Nom Toujours Valide',
            'status' => Shop::STATUS_ACTIVE,
            'subscription_plan_id' => $otherPlan->id,
        ])->assertOk();

        $shop->refresh();

        $this->assertSame('Nom Toujours Valide', $shop->name);
        $this->assertSame(Shop::STATUS_PENDING, $shop->status);
        $this->assertSame($originalPlan->id, $shop->subscription_plan_id);
    }

    public function test_partial_update_only_changes_provided_fields(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Nom Original',
            'email' => 'original@test.cm',
        ]);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/shops/{$shop->id}", ['name' => 'Nom Modifié'])
            ->assertOk();

        $shop->refresh();

        $this->assertSame('Nom Modifié', $shop->name);
        $this->assertSame('original@test.cm', $shop->email); // inchangé
    }
}
