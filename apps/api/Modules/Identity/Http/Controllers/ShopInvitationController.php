<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Actions\CancelInvitationAction;
use Modules\Identity\Actions\ResendInvitationAction;
use Modules\Identity\Http\Resources\ShopEmployeeInvitationResource;
use Modules\Identity\Models\ShopEmployeeInvitation;
use Modules\Identity\Policies\ShopEmployeePolicy;
use Modules\Marketplace\Models\Shop;

class ShopInvitationController extends Controller
{
    public function __construct(
        private readonly ShopEmployeePolicy $policy,
    ) {
    }

    public function index(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($this->policy->viewAny($request->user(), $shop->id), 403);

        $invitations = ShopEmployeeInvitation::with(['role', 'inviter'])
            ->where('shop_id', $shop->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ShopEmployeeInvitationResource::collection($invitations)->response();
    }

    public function cancel(
        Request $request,
        Shop $shop,
        ShopEmployeeInvitation $invitation,
        CancelInvitationAction $action,
    ): JsonResponse {
        abort_unless($invitation->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $action->execute($invitation);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Invitation annulée.']);
    }

    public function resend(
        Request $request,
        Shop $shop,
        ShopEmployeeInvitation $invitation,
        ResendInvitationAction $action,
    ): JsonResponse {
        abort_unless($invitation->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $action->execute($invitation);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Invitation renvoyée.']);
    }
}
