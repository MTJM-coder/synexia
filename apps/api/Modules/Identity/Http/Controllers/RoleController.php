<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Actions\CreateRoleAction;
use Modules\Identity\Actions\DeleteRoleAction;
use Modules\Identity\Actions\SyncRolePermissionsAction;
use Modules\Identity\Actions\UpdateRoleAction;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Models\Role;
use Modules\Marketplace\Models\Shop;

/**
 * SÉCURITÉ : ce contrôleur n'avait AUCUNE autorisation avant ce correctif —
 * n'importe quel utilisateur authentifié pouvait créer/modifier/supprimer
 * les rôles de N'IMPORTE QUELLE boutique. Réutilise la permission
 * "employees.manage" déjà seedée (pas de nouvel espace de noms de
 * permission "roles.*" créé sans validation — à discuter si vous en voulez
 * un dédié, voir BACKLOG_IDENTITY.md).
 */
class RoleController extends Controller
{
    public function __construct(
        private readonly PermissionResolverContract $resolver,
    ) {}

    public function index(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeManage($request, $shop->id);

        $roles = Role::where('shop_id', $shop->id)
            ->orWhereNull('shop_id')
            ->with('permissions')
            ->get();

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request, Shop $shop, CreateRoleAction $action): JsonResponse
    {
        $this->authorizeManage($request, $shop->id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id', // était "string", incohérent avec une PK entière
        ]);

        $role = $action->execute($shop->id, $validated);

        return response()->json(['data' => $role], 201);
    }

    public function update(Request $request, Shop $shop, Role $role, UpdateRoleAction $action): JsonResponse
    {
        $this->authorizeManage($request, $shop->id);
        abort_unless($role->shop_id === $shop->id || $role->shop_id === null, 404);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $updatedRole = $action->execute($role, $validated);

        return response()->json(['data' => $updatedRole]);
    }

    public function destroy(Request $request, Shop $shop, Role $role, DeleteRoleAction $action): JsonResponse
    {

        $this->authorizeManage($request, $shop->id);

        abort_unless($role->shop_id === $shop->id || $role->shop_id === null, 404);

        $action->execute($role);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function syncPermissions(Request $request, Shop $shop, Role $role, SyncRolePermissionsAction $action): JsonResponse
    {
        $this->authorizeManage($request, $shop->id);
        abort_unless($role->shop_id === $shop->id || $role->shop_id === null, 404);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $updatedRole = $action->execute($role, $validated['permissions']);

        return response()->json(['data' => $updatedRole]);
    }

    private function authorizeManage(Request $request, int $shopId): void
    {
        $user = $request->user();

        if ($user->is_super_admin) {
            return;
        }

        $employee = $user->shopEmployees()->where('shop_id', $shopId)->first();

        abort_unless(
            $employee && $this->resolver->resolveForEmployee($employee)->has('employees.manage'),
            403,
        );
    }
}
