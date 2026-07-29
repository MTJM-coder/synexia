<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Identity\Models\User;

class EmailVerificationRequested
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $signedUrl,
    ) {
    }
}
