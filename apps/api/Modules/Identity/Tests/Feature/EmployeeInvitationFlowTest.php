<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Actions\AcceptShopInvitationAction;
use Modules\Identity\Actions\InviteEmployeeAction;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeeInvitation;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class EmployeeInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cree_une_invitation_avec_un_token_hache_jamais_en_clair(): void
    {
        $shop = Shop::factory()->create();
        $role = Role::factory()->create(['guard_scope' => Role::GUARD_SHOP]);
        $inviter = User::factory()->create();

        $invitation = app(InviteEmployeeAction::class)->execute(
            shopId: $shop->id,
            email: 'nouvel.employe@example.com',
            role: $role,
            invitedByUserId: $inviter->id,
        );

        $this->assertSame(ShopEmployeeInvitation::STATUS_PENDING, $invitation->status);
        $this->assertSame(64, strlen($invitation->token)); // hash SHA-256 hexadécimal
    }

    public function test_accepte_une_invitation_et_cree_le_compte_quand_aucun_compte_nexiste(): void
    {
        $shop = Shop::factory()->create();
        $role = Role::factory()->create(['guard_scope' => Role::GUARD_SHOP]);
        $inviter = User::factory()->create();

        $plainToken = 'un-token-de-test-suffisamment-long-1234567890';

        $invitation = ShopEmployeeInvitation::factory()->create([
            'shop_id' => $shop->id,
            'email' => 'sans.compte@example.com',
            'role_id' => $role->id,
            'invited_by' => $inviter->id,
            'token' => hash('sha256', $plainToken),
        ]);

        $employee = app(AcceptShopInvitationAction::class)->execute(
            plainToken: $plainToken,
            authenticatedUser: null,
            newUserData: [
                'first_name' => 'Nouvelle',
                'last_name' => 'Recrue',
                'password' => 'password1234',
            ],
        );

        $this->assertSame(ShopEmployee::STATUS_ACTIVE, $employee->status);
        $this->assertSame('sans.compte@example.com', $employee->user->email);
        $this->assertSame(ShopEmployeeInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);
    }

    public function test_rejette_une_invitation_expiree(): void
    {
        $plainToken = 'token-expire-1234567890';

        ShopEmployeeInvitation::factory()->expired()->create([
            'token' => hash('sha256', $plainToken),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cette invitation a expiré.');

        app(AcceptShopInvitationAction::class)->execute(
            plainToken: $plainToken,
            authenticatedUser: null,
            newUserData: ['first_name' => 'X', 'last_name' => 'Y', 'password' => 'password1234'],
        );
    }
}
