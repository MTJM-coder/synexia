<?php

namespace Modules\Marketplace\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Marketplace\Models\Shop;

/**
 * Émis après la création complète d'une boutique. Identity devrait écouter
 * cet événement pour créer automatiquement le ShopEmployee "Owner" du
 * propriétaire (CreateShopEmployeeAction) — pas encore câblé côté Identity,
 * à faire quand on reviendra dessus.
 */
class ShopCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Shop $shop,
    ) {
    }
}
