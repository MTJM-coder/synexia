<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Identity\Actions\AcceptShopInvitationAction;
use Modules\Identity\Actions\InviteEmployeeAction;
use Modules\Identity\Http\Requests\AcceptInvitationRequest;
use Modules\Identity\Http\Requests\InviteEmployeeRequest;
use Modules\Identity\Http\Resources\ShopEmployeeResource;
use Modules\Identity\Models\Role;

class EmployeeInvitationController extends Controller
{
    public function invite(InviteEmployeeRequest $request, InviteEmployeeAction $action): JsonResponse
    {
        $role = Role::findOrFail($request->validated('role_id'));

        try {
            $invitation = $action->execute(
                shopId: $request->validated('shop_id'),
                email: $request->validated('email'),
                role: $role,
                invitedByUserId: $request->user()->id,
            );
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Invitation envoyée.',
            'invitation_id' => $invitation->id,
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ], 201);
    }

    public function accept(AcceptInvitationRequest $request, AcceptShopInvitationAction $action): JsonResponse
    {
        // Résolution volontairement manuelle (pas de middleware auth:sanctum sur
        // cette route) : accepter une invitation doit rester possible SANS être
        // connecté (cas "je n'ai pas encore de compte"). Si un Bearer token
        // valide est quand même fourni, on l'utilise ; sinon $authenticatedUser
        // reste null et AcceptShopInvitationAction créera le compte.
        $authenticatedUser = Auth::guard('sanctum')->user();

        try {
            $employee = $action->execute(
                plainToken: $request->validated('token'),
                authenticatedUser: $authenticatedUser,
                newUserData: $authenticatedUser ? null : $request->only(['first_name', 'last_name', 'password']),
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'employee' => new ShopEmployeeResource($employee),
        ]);
    }
}
