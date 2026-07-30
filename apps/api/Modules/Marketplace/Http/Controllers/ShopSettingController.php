<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Http\Requests\UpdateShopSettingRequest;
use Modules\Marketplace\Http\Resources\ShopSettingResource;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Policies\ShopPolicy;

class ShopSettingController extends Controller
{
    public function __construct(
        private readonly ShopPolicy $policy,
    ) {
    }

    public function show(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($this->policy->view($request->user(), $shop), 403);

        return (new ShopSettingResource($shop->settings))->response();
    }

    public function update(UpdateShopSettingRequest $request, Shop $shop): JsonResponse
    {
        // manage() ici, pas changeStatus() : modifier ses paramètres (devise,
        // TVA, livraison...) est une action légitime du propriétaire, à la
        // différence de suspendre/réactiver sa propre boutique.
        abort_unless($this->policy->manage($request->user(), $shop), 403);

        $shop->settings()->update($request->validated());

        return (new ShopSettingResource($shop->settings()->first()))->response();
    }
}
