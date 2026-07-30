<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Http\Resources\SubscriptionPlanResource;
use Modules\Marketplace\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    /**
     * Public — un visiteur non connecté doit pouvoir voir les tarifs avant
     * de s'inscrire.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return SubscriptionPlanResource::collection($plans)->response();
    }
}
