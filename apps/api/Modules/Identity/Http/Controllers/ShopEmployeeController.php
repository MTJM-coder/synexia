<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Actions\AssignRoleToEmployeeAction;
use Modules\Identity\Actions\ReactivateShopEmployeeAction;
use Modules\Identity\Actions\RemoveShopEmployeeAction;
use Modules\Identity\Actions\SetEmployeePermissionOverrideAction;
use Modules\Identity\Actions\SuspendShopEmployeeAction;
use Modules\Identity\Http\Requests\SetShopEmployeePermissionRequest;
use Modules\Identity\Http\Requests\UpdateShopEmployeeRoleRequest;
use Modules\Identity\Http\Resources\ShopEmployeeResource;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Policies\ShopEmployeePolicy;
use Modules\Marketplace\Models\Shop;

/**
 * NOTE : plus de méthode store() ici. La création directe d'un ShopEmployee
 * est supprimée — l'invitation (Modules\Identity\Http\Controllers\
 * EmployeeInvitationController) est le SEUL point d'entrée pour rejoindre
 * une boutique. Voir décision d'architecture validée + BACKLOG_IDENTITY.md
 * point 4.3.
 */
class ShopEmployeeController extends Controller
{
    public function __construct(
        private readonly ShopEmployeePolicy $policy,
    ) {
    }

    public function index(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($this->policy->viewAny($request->user(), $shop->id), 403);

        $employees = ShopEmployee::with(['user', 'role'])
            ->where('shop_id', $shop->id)
            ->paginate(20);

        return ShopEmployeeResource::collection($employees)->response();
    }

    public function show(Request $request, Shop $shop, ShopEmployee $shopEmployee): JsonResponse
    {
        abort_unless($shopEmployee->shop_id === $shop->id, 404);
        abort_unless($this->policy->viewAny($request->user(), $shop->id), 403);

        return (new ShopEmployeeResource($shopEmployee->load(['user', 'role'])))
            ->response();
    }

    public function updateRole(
        UpdateShopEmployeeRoleRequest $request,
        Shop $shop,
        ShopEmployee $shopEmployee,
        AssignRoleToEmployeeAction $action,
    ): JsonResponse {
        abort_unless($shopEmployee->shop_id === $shop->id, 404);
        abort_unless($this->policy->assignRole($request->user(), $shopEmployee), 403);

        $role = Role::findOrFail($request->validated('role_id'));

        $employee = $action->execute($shopEmployee, $role, $request->user()->id);

        return (new ShopEmployeeResource($employee))->response();
    }

    public function setPermission(
        SetShopEmployeePermissionRequest $request,
        Shop $shop,
        ShopEmployee $shopEmployee,
        SetEmployeePermissionOverrideAction $action,
    ): JsonResponse {
        abort_unless($shopEmployee->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $permission = Permission::findOrFail($request->validated('permission_id'));

        $action->execute(
            employee: $shopEmployee,
            permission: $permission,
            isGranted: $request->validated('is_granted'),
            changedByUserId: $request->user()->id,
        );

        return (new ShopEmployeeResource($shopEmployee->fresh(['user', 'role'])))->response();
    }

    public function suspend(
        Request $request,
        Shop $shop,
        ShopEmployee $shopEmployee,
        SuspendShopEmployeeAction $action,
    ): JsonResponse {
        abort_unless($shopEmployee->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $employee = $action->execute($shopEmployee, $request->user()->id);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ShopEmployeeResource($employee))->response();
    }

    public function reactivate(
        Request $request,
        Shop $shop,
        ShopEmployee $shopEmployee,
        ReactivateShopEmployeeAction $action,
    ): JsonResponse {
        abort_unless($shopEmployee->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $employee = $action->execute($shopEmployee, $request->user()->id);

        return (new ShopEmployeeResource($employee))->response();
    }

    public function destroy(
        Request $request,
        Shop $shop,
        ShopEmployee $shopEmployee,
        RemoveShopEmployeeAction $action,
    ): JsonResponse {
        abort_unless($shopEmployee->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $action->execute($shopEmployee, $request->user()->id);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Employé retiré de la boutique.']);
    }
}
