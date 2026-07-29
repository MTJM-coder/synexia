<?php

namespace Modules\Identity\Actions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\LoginHistory;
use Modules\Identity\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticateUserAction
{
    /**
     * @param array{login:string,password:string,device_name?:string} $data
     * @return array{user:User,token:string}
     *
     * @throws AuthenticationException|HttpException
     */
    public function execute(array $data, ?string $ip = null, ?string $userAgent = null): array
    {
        $login = strtolower(trim($data['login']));

        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        // Identifiants invalides (401)
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            if ($user) {
                $this->recordAttempt($user, 'failed', $ip, $userAgent);
            }

            throw new AuthenticationException('Identifiants invalides.');
        }

        // Compte non autorisé à se connecter (403)
        if ($user->status !== 'active') {
            $this->recordAttempt($user, 'failed', $ip, $userAgent);

            $message = match ($user->status) {
                'suspended' => 'Ce compte est suspendu.',
                'banned'    => 'Ce compte est banni.',
                default     => "Ce compte n'est pas encore actif.",
            };

            throw new HttpException(403, $message);
        }

        // Mise à jour des informations de connexion
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        // Historique
        $this->recordAttempt($user, 'success', $ip, $userAgent);

        // Création du token Sanctum
        $token = $user
            ->createToken($data['device_name'] ?? 'default')
            ->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    protected function recordAttempt(
        User $user,
        string $status,
        ?string $ip,
        ?string $userAgent
    ): void {
        LoginHistory::create([
            'user_id'    => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status'     => $status,
        ]);
    }
}