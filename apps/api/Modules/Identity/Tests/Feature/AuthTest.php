<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jean@test.cm',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jean@test.cm',
            'password' => 'secret123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.email', 'jean@test.cm');
        $this->assertNotEmpty($response->json('meta.token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'jean@test.cm',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jean@test.cm',
            'password' => 'mauvais-mot-de-passe',
        ]);

        $response->assertStatus(401);
    }

    public function test_inactive_account_cannot_login_even_with_correct_password(): void
    {
        User::factory()->create([
            'email' => 'jean@test.cm',
            'password' => Hash::make('secret123'),
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jean@test.cm',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }
}
