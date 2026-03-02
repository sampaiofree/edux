<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountDeletionRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isStudent(), 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $existingPending = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', AccountDeletionRequest::STATUS_PENDING)
            ->exists();

        if ($existingPending) {
            return back()->with('status', 'Voce ja possui uma solicitacao de exclusao em analise.');
        }

        AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'requested_name' => $user->name,
            'requested_email' => $user->email,
            'requested_whatsapp' => $user->whatsapp,
            'reason' => isset($data['reason']) ? trim((string) $data['reason']) : null,
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        return back()->with('status', 'Solicitacao enviada. Nossa equipe vai analisar seu pedido.');
    }
}
