<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Identity\Models\User;

class RegisterUserAction
{
    /**
     * @param array{first_name: string, last_name: string, email: ?string, phone: ?string, password: string} $data
     */
    public function execute(array $data): User
    {
        return User::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'locale' => $data['locale'] ?? 'fr',
            'status' => 'active',
        ]);
    }
}
