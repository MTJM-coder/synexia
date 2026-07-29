<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Models\User;

class UpdateProfileAction
{
    /**
     * @param array{first_name?: string, last_name?: string, phone?: ?string, locale?: string} $data
     */
    public function execute(User $user, array $data): User
    {
        $user->fill(array_intersect_key($data, array_flip([
            'first_name', 'last_name', 'phone', 'locale',
        ])));

        $user->save();

        return $user->fresh();
    }
}
