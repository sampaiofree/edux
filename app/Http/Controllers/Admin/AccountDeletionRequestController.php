<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountDeletionRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', AccountDeletionRequest::STATUS_PENDING);
        $allowedStatuses = [
            'all',
            AccountDeletionRequest::STATUS_PENDING,
            AccountDeletionRequest::STATUS_DELETED,
            AccountDeletionRequest::STATUS_REJECTED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = AccountDeletionRequest::STATUS_PENDING;
        }

        $requests = AccountDeletionRequest::query()
            ->whereHas('user')
            ->with(['user', 'resolver'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.account-deletion-requests.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function destroyAccount(Request $request, AccountDeletionRequest $accountDeletionRequest, AccountDeletionService $deletionService): RedirectResponse
    {
        $this->ensureRequestBelongsToCurrentSystem($request, $accountDeletionRequest);

        if ($accountDeletionRequest->status !== AccountDeletionRequest::STATUS_PENDING) {
            return back()->with('status', 'Solicitacao ja resolvida anteriormente.');
        }

        $actor = $request->user();
        $target = $accountDeletionRequest->user;

        if (! $target) {
            $accountDeletionRequest->update([
                'status' => AccountDeletionRequest::STATUS_DELETED,
                'resolved_at' => now(),
                'resolved_by' => $actor?->id,
                'resolution_note' => 'Conta ja inexistente no momento da analise.',
            ]);

            return back()->with('status', 'Solicitacao resolvida: conta ja inexistente.');
        }

        if (! $target->isStudent()) {
            $accountDeletionRequest->update([
                'status' => AccountDeletionRequest::STATUS_REJECTED,
                'resolved_at' => now(),
                'resolved_by' => $actor?->id,
                'resolution_note' => 'Solicitacao recusada: apenas contas de aluno podem ser excluidas por este fluxo.',
            ]);

            return back()->with('status', 'Solicitacao recusada: conta nao elegivel para este fluxo.');
        }

        $deletionService->deleteUser($target);

        $accountDeletionRequest->update([
            'status' => AccountDeletionRequest::STATUS_DELETED,
            'resolved_at' => now(),
            'resolved_by' => $actor?->id,
            'resolution_note' => 'Conta excluida pelo admin.',
        ]);

        return back()->with('status', 'Conta excluida e solicitacao finalizada.');
    }

    public function reject(Request $request, AccountDeletionRequest $accountDeletionRequest): RedirectResponse
    {
        $this->ensureRequestBelongsToCurrentSystem($request, $accountDeletionRequest);

        if ($accountDeletionRequest->status !== AccountDeletionRequest::STATUS_PENDING) {
            return back()->with('status', 'Solicitacao ja resolvida anteriormente.');
        }

        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $accountDeletionRequest->update([
            'status' => AccountDeletionRequest::STATUS_REJECTED,
            'resolved_at' => now(),
            'resolved_by' => $request->user()?->id,
            'resolution_note' => isset($data['resolution_note']) && trim($data['resolution_note']) !== ''
                ? trim((string) $data['resolution_note'])
                : 'Solicitacao recusada pelo administrador.',
        ]);

        return back()->with('status', 'Solicitacao recusada.');
    }

    private function ensureRequestBelongsToCurrentSystem(Request $request, AccountDeletionRequest $accountDeletionRequest): void
    {
        $systemSettingId = DB::table('users')
            ->where('id', $accountDeletionRequest->user_id)
            ->value('system_setting_id');

        if ($systemSettingId === null) {
            return;
        }

        abort_if((int) $systemSettingId !== (int) ($request->user()?->system_setting_id ?? 0), 404);
    }
}
