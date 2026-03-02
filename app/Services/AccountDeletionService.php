<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AccountDeletionService
{
    public function deleteUser(User $user): void
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();
    }
}
