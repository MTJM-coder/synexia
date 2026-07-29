<?php

namespace Modules\Identity\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Identity\Models\User;

class UploadAvatarAction
{
    private const DISK = 'public';
    private const DIRECTORY = 'avatars';

    public function execute(User $user, UploadedFile $file): User
    {
        $previousPath = $user->avatar_path;

        $path = $file->store(self::DIRECTORY, self::DISK);

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previousPath) {
            Storage::disk(self::DISK)->delete($previousPath);
        }

        return $user->fresh();
    }
}
