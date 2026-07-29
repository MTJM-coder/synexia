<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Actions\CancelInvitationAction;
use Modules\Identity\Actions\ResendInvitationAction;
use Modules\Identity\Models\ShopEmployeeInvitation;
use Tests\TestCase;

class InvitationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_cancel_a_pending_invitation(): void
    {
        $invitation = ShopEmployeeInvitation::factory()->create();

        app(CancelInvitationAction::class)->execute($invitation);

        $this->assertSame(ShopEmployeeInvitation::STATUS_CANCELLED, $invitation->fresh()->status);
    }

    public function test_cannot_cancel_an_already_accepted_invitation(): void
    {
        $invitation = ShopEmployeeInvitation::factory()->accepted()->create();

        $this->expectException(\DomainException::class);

        app(CancelInvitationAction::class)->execute($invitation);
    }

    public function test_resending_generates_a_new_token_and_extends_expiry(): void
    {
        $invitation = ShopEmployeeInvitation::factory()->create([
            'expires_at' => now()->addHour(),
        ]);
        $originalTokenHash = $invitation->token;

        app(ResendInvitationAction::class)->execute($invitation);
        $invitation->refresh();

        $this->assertNotSame($originalTokenHash, $invitation->token);
        $this->assertTrue($invitation->expires_at->greaterThan(now()->addDays(6)));
    }
}
