<?php

namespace Modules\Marketplace\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Identity\Contracts\ShopMembershipContract;
use Modules\Identity\Models\User;
use Modules\Inventory\Contracts\WarehouseProvisioningContract;
use Modules\Marketplace\Events\ShopCreated;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\ShopSetting;
use Modules\Marketplace\Models\ShopSubscription;
use Modules\Marketplace\Models\SubscriptionPlan;

/**
 * Orchestrateur de la création complète d'une boutique. Marketplace dépend
 * ici explicitement d'Identity (via ShopMembershipContract) et d'Inventory
 * (via WarehouseProvisioningContract) — jamais l'inverse. Ni Identity ni
 * Inventory ne doivent jamais écouter un événement de Marketplace pour
 * créer quoi que ce soit ; tout se fait ici, de façon synchrone et
 * transactionnelle.
 */
class CreateShopAction
{
    public function __construct(
        private readonly ShopMembershipContract $shopMembership,
        private readonly WarehouseProvisioningContract $warehouseProvisioning,
    ) {
    }

    /**
     * @param  array{name:string,email?:string,phone?:string,country?:string}  $shopData
     */
    public function execute(User $owner, array $shopData, SubscriptionPlan $plan): Shop
    {
        return DB::transaction(function () use ($owner, $shopData, $plan) {
            $shop = Shop::create([
                'uuid' => (string) Str::uuid(),
                'owner_id' => $owner->id,
                'subscription_plan_id' => $plan->id,
                'name' => $shopData['name'],
                'slug' => Str::slug($shopData['name']).'-'.Str::random(6),
                'email' => $shopData['email'] ?? null,
                'phone' => $shopData['phone'] ?? null,
                'country' => $shopData['country'] ?? 'Cameroun',
                'status' => Shop::STATUS_PENDING,
            ]);

            ShopSubscription::create([
                'shop_id' => $shop->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
                'status' => ShopSubscription::STATUS_TRIAL,
                'amount_paid' => 0,
            ]);

            ShopSetting::create([
                'shop_id' => $shop->id,
                'currency' => 'XAF',
                'language' => 'fr',
                'timezone' => 'Africa/Douala',
                'tax_rate' => 19.25,
                'tax_inclusive' => true,
                'allow_pickup' => true,
                'allow_delivery' => true,
            ]);

            $this->warehouseProvisioning->createDefaultWarehouse($shop->id);

            $this->shopMembership->createOwnerMembership($shop->id, $owner);

            // Conservé pour d'éventuels écouteurs futurs (Notifications, un
            // jour, pour un email de bienvenue) — mais plus personne dans
            // Identity ou Inventory ne doit s'y abonner pour créer quoi que
            // ce soit : c'est fait ci-dessus, de façon synchrone.
            ShopCreated::dispatch($shop->fresh(['subscriptionPlan', 'settings']));

            return $shop->fresh(['subscriptionPlan', 'settings']);
        });
    }
}
