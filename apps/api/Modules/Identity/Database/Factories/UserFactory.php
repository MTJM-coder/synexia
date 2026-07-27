<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'password' => bcrypt('password'),
            'is_super_admin' => false,
            'status' => 'active',
            'locale' => 'fr',
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['is_super_admin' => true]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
