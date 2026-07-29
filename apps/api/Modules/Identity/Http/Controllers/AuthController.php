<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Actions\AuthenticateUserAction;
use Modules\Identity\Actions\RegisterUserAction;
use Modules\Identity\Actions\RequestPasswordResetAction;
use Modules\Identity\Actions\ResetPasswordAction;
use Modules\Identity\Actions\SendEmailVerificationAction;
use Modules\Identity\Actions\VerifyEmailAction;
use Modules\Identity\Http\Requests\ForgotPasswordRequest;
use Modules\Identity\Http\Requests\LoginRequest;
use Modules\Identity\Http\Requests\RegisterRequest;
use Modules\Identity\Http\Requests\ResetPasswordRequest;
use Modules\Identity\Http\Resources\UserResource;
use Modules\Identity\Models\User;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action, SendEmailVerificationAction $sendVerification): JsonResponse
    {
        $user = $action->execute($request->validated());

        if ($user->email !== null) {
            $sendVerification->execute($user);
        }

        $token = $user->createToken($request->input('device_name', 'default'))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request, AuthenticateUserAction $action): JsonResponse
    {
        try {
            $result = $action->execute(
                $request->validated(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json([
            'data' => new UserResource($result['user']),
            'meta' => [
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request, RequestPasswordResetAction $action): JsonResponse
    {
        $action->execute($request->validated('email'));

        return response()->json([
            'message' => 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        try {
            $action->execute(
                $request->validated('email'),
                $request->validated('token'),
                $request->validated('password'),
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    public function resendEmailVerification(Request $request, SendEmailVerificationAction $action): JsonResponse
    {
        try {
            $action->execute($request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Email de vérification envoyé.']);
    }

    /**
     * Route protégée par le middleware "signed" (voir routes.php) — la
     * signature elle-même est déjà vérifiée avant que ce code s'exécute.
     */
    public function verifyEmail(Request $request, int $id, string $hash, VerifyEmailAction $action): JsonResponse
    {
        $user = User::findOrFail($id);

        try {
            $action->execute($user, $hash);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Email vérifié avec succès.']);
    }
}
