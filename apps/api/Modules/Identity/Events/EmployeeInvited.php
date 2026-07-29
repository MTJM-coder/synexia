<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Identity\Models\ShopEmployeeInvitation;

class EmployeeInvited
{
    use Dispatchable;

    /**
     * @param  string  $plainToken  Le token en clair, DISPONIBLE UNIQUEMENT ici.
     *                              Jamais stocké nulle part — seul son hash SHA-256
     *                              vit dans shop_employee_invitations.token. Le
     *                              module Notifications (à construire) doit
     *                              écouter cet event pour composer le lien
     *                              d'invitation et l'envoyer par email, puisqu'il
     *                              ne pourra plus jamais récupérer ce token ensuite.
     */
    public function __construct(
        public readonly ShopEmployeeInvitation $invitation,
        public readonly string $plainToken,
    ) {
    }
}
