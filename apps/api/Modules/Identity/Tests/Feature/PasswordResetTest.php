<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Events\PasswordResetRequested;
use Modules\Identity\Models\User;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_reset_for_existing_email_creates_a_hashed_token(): void
    {
        Event::fake();

        $user = User::factory()->create(['email' => 'jean@test.cm']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'jean@test.cm'])
            ->assertOk();

        $record = DB::table('password_reset_tokens')->where('email', 'jean@test.cm')->first();

        $this->assertNotNull($record);
        $this->assertNotEmpty($record->token);

        Event::assertDispatched(PasswordResetRequested::class, function ($event) {
            // Le token en clair n'est JAMAIS égal à sa version hachée stockée.
            return strlen($event->plainToken) === 64;
        });
    }

    public function test_requesting_reset_for_unknown_email_returns_the_same_response(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'inconnu@test.cm']);

        $response->assertOk();
        $response->assertJsonPath('message', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        Event::fake();

        $user = User::factory()->create(['email' => 'jean@test.cm', 'password' => Hash::make('ancien-mdp')]);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'jean@test.cm']);

        $plainToken = null;
        Event::assertDispatched(PasswordResetRequested::class, function ($event) use (&$plainToken) {
            $plainToken = $event->plainToken;

            return true;
        });

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'jean@test.cm',
            'token' => $plainToken,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0); // usage unique
    }

    public function test_reset_fails_with_wrong_token(): void
    {
        $user = User::factory()->create(['email' => 'jean@test.cm']);

        DB::table('password_reset_tokens')->insert([
            'email' => 'jean@test.cm',
            'token' => hash('sha256', 'le-vrai-token'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'jean@test.cm',
            'token' => 'un-mauvais-token',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_fails_with_expired_token(): void
    {
        $user = User::factory()->create(['email' => 'jean@test.cm']);

        DB::table('password_reset_tokens')->insert([
            'email' => 'jean@test.cm',
            'token' => hash('sha256', 'le-vrai-token'),
            'created_at' => now()->subHours(2), // > 60 minutes de validité
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'jean@test.cm',
            'token' => 'le-vrai-token',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);

        $response->assertStatus(422);
    }
}