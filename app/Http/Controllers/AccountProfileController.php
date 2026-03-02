<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AccountProfileController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $pendingDeletionRequest = null;
        if ($user && $user->isStudent()) {
            $pendingDeletionRequest = AccountDeletionRequest::query()
                ->where('user_id', $user->id)
                ->where('status', AccountDeletionRequest::STATUS_PENDING)
                ->latest('requested_at')
                ->first();
        }

        return view('account.profile', [
            'pendingDeletionRequest' => $pendingDeletionRequest,
        ]);
    }
}
