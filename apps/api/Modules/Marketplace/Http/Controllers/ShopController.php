<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Actions\ChangeShopStatusAction;
use Modules\Marketplace\Actions\CreateShopAction;
use Modules\Marketplace\Actions\SubscribeShopToPlanAction;
use Modules\Marketplace\Http\Requests\ChangeShopStatusRequest;
use Modules\Marketplace\Http\Requests\CreateShopRequest;
use Modules\Marketplace\Http\Requests\SubscribeToPlanRequest;
use Modules\Marketplace\Http\Requests\UpdateShopRequest;
use Modules\Marketplace\Http\Resources\ShopResource;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;
use Modules\Marketplace\Policies\ShopPolicy;

class ShopController extends Controller
{
    public function __construct(
        private readonly ShopPolicy $policy,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $shops = Shop::with(['subscriptionPlan', 'settings'])
            ->where('owner_id', $request->user()->id)
            ->paginate(20);

        return ShopResource::collection($shops)->response();
    }

    public function store(CreateShopRequest $request, CreateShopAction $action): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->validated('subscription_plan_id'));

        $shop = $action->execute(
            owner: $request->user(),
            shopData: $request->safe()->only(['name', 'email', 'phone', 'country']),
            plan: $plan,
        );

        return (new ShopResource($shop))->response()->setStatusCode(201);
    }

    public function show(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($this->policy->view($request->user(), $shop), 403);

        return (new ShopResource($shop->load(['subscriptionPlan', 'settings'])))->response();
    }

    /**
     * Modifie les informations générales (nom, coordonnées, adresse...) —
     * pas le statut ni l'abonnement, qui ont chacun leur propre endpoint et
     * leurs propres règles d'autorisation.
     */
    public function update(UpdateShopRequest $request, Shop $shop): JsonResponse
    {
        abort_unless($this->policy->manage($request->user(), $shop), 403);

        $shop->update($request->validated());

        return (new ShopResource($shop->fresh(['subscriptionPlan', 'settings'])))->response();
    }

    public function updateStatus(ChangeShopStatusRequest $request, Shop $shop, ChangeShopStatusAction $action): JsonResponse
    {
        abort_unless($this->policy->changeStatus($request->user()), 403, 'Réservé aux administrateurs de la plateforme.');

        try {
            $updated = $action->execute($shop, $request->validated('status'), $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ShopResource($updated))->response();
    }

    public function subscribe(SubscribeToPlanRequest $request, Shop $shop, SubscribeShopToPlanAction $action): JsonResponse
    {
        abort_unless($this->policy->manage($request->user(), $shop), 403);

        $plan = SubscriptionPlan::findOrFail($request->validated('subscription_plan_id'));

        $updated = $action->execute($shop, $plan, (float) ($request->validated('amount_paid') ?? 0));

        return (new ShopResource($updated))->response();
    }
}
