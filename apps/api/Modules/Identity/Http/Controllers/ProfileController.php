<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Actions\ChangePasswordAction;
use Modules\Identity\Actions\UpdateProfileAction;
use Modules\Identity\Actions\UploadAvatarAction;
use Modules\Identity\Http\Requests\ChangePasswordRequest;
use Modules\Identity\Http\Requests\UpdateProfileRequest;
use Modules\Identity\Http\Requests\UploadAvatarRequest;
use Modules\Identity\Http\Resources\UserResource;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());

        return response()->json(['data' => new UserResource($user)]);
    }

    public function uploadAvatar(UploadAvatarRequest $request, UploadAvatarAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->file('avatar'));

        return response()->json(['data' => new UserResource($user)]);
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        try {
            $action->execute(
                $request->user(),
                $request->validated('current_password'),
                $request->validated('password'),
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Mot de passe modifié. Vos autres sessions ont été déconnectées.']);
    }
}
