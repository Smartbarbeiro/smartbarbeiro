<?php

namespace App\Observers;

use App\Models\User;
use App\Services\BarbershopServicePlanService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    public function created(User $user): void
    {
        if (! $user->isBarbershop()) {
            return;
        }

        File::ensureDirectoryExists($user->storagePath());
        File::put($user->storagePath().'/.gitkeep', '');

        app(BarbershopServicePlanService::class)->ensureDefaultPackages($user);
    }

    public function deleted(User $user): void
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        if ($user->background_photo_path) {
            Storage::disk('public')->delete($user->background_photo_path);
        }

        Storage::disk('public')->deleteDirectory('profile-photos/'.$user->id);
        Storage::disk('public')->deleteDirectory('background-photos/'.$user->id);

        if (File::isDirectory($user->storagePath())) {
            File::deleteDirectory($user->storagePath());
        }
    }
}
