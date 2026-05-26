<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    public function created(User $user): void
    {
        File::ensureDirectoryExists($user->storagePath());
        File::put($user->storagePath().'/.gitkeep', '');
    }

    public function deleted(User $user): void
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        Storage::disk('public')->deleteDirectory('profile-photos/'.$user->id);

        if (File::isDirectory($user->storagePath())) {
            File::deleteDirectory($user->storagePath());
        }
    }
}
