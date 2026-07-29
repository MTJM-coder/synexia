<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('ancien-mdp-1234')]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/me/password', [
                'current_password' => 'ancien-mdp-1234',
                'password' => 'nouveau-mdp-5678',
                'password_confirmation' => 'nouveau-mdp-5678',
            ]);

        $response->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nouveau-mdp-5678', $user->fresh()->password));
    }

    public function test_changing_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('ancien-mdp-1234')]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/me/password', [
                'current_password' => 'mauvais-mot-de-passe',
                'password' => 'nouveau-mdp-5678',
                'password_confirmation' => 'nouveau-mdp-5678',
            ]);

        $response->assertStatus(422);
    }

    public function test_changing_password_revokes_other_sessions_but_not_current_one(): void
    {
        $user = User::factory()->create(['password' => bcrypt('ancien-mdp-1234')]);
        $currentToken = $user->createToken('appareil-actuel');
        $otherToken = $user->createToken('autre-appareil');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->patchJson('/api/v1/me/password', [
                'current_password' => 'ancien-mdp-1234',
                'password' => 'nouveau-mdp-5678',
                'password_confirmation' => 'nouveau-mdp-5678',
            ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/me', ['first_name' => 'Nouveau Prénom']);

        $response->assertOk();
        $this->assertSame('Nouveau Prénom', $user->fresh()->first_name);
    }

    public function test_user_can_list_and_revoke_a_specific_session(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('appareil-actuel');
        $otherToken = $user->createToken('autre-appareil');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->getJson('/api/v1/me/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->deleteJson("/api/v1/me/sessions/{$otherToken->accessToken->id}")
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }
}
