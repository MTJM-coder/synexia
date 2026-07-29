<?php

namespace Modules\Marketplace\Actions;

use Modules\Marketplace\Events\ShopStatusChanged;
use Modules\Marketplace\Models\Shop;

class ChangeShopStatusAction
{
    /** @var array<string, string[]> transitions autorisées */
    private const ALLOWED_TRANSITIONS = [
        Shop::STATUS_PENDING => [Shop::STATUS_ACTIVE, Shop::STATUS_CLOSED],
        Shop::STATUS_ACTIVE => [Shop::STATUS_SUSPENDED, Shop::STATUS_CLOSED],
        Shop::STATUS_SUSPENDED => [Shop::STATUS_ACTIVE, Shop::STATUS_CLOSED],
        Shop::STATUS_CLOSED => [], // état terminal, aucune réouverture automatique
    ];

    public function execute(Shop $shop, string $newStatus, ?int $changedByUserId = null): Shop
    {
        $currentStatus = $shop->status;

        if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new \InvalidArgumentException(
                "Transition de statut invalide : \"{$currentStatus}\" → \"{$newStatus}\"."
            );
        }

        $shop->update(['status' => $newStatus]);

        ShopStatusChanged::dispatch($shop, $currentStatus, $newStatus, $changedByUserId);

        return $shop->fresh();
    }
}
