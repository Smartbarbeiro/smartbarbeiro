<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUserAccountService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->deleteProfilePhoto();
            $user->delete();
        });
    }
}
