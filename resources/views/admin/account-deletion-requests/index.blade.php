@extends('layouts.app')

@section('title', 'Solicitacoes de exclusao')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Administracao</p>
            <h1 class="font-display text-3xl text-edux-primary">Solicitacoes de exclusao de conta</h1>
            <p class="text-sm text-slate-600">Gerencie os pedidos enviados pelos alunos e execute a exclusao quando necessario.</p>
        </header>

        <section class="rounded-card bg-white p-6 shadow-card">
            <form method="GET" action="{{ route('admin.account-deletion-requests.index') }}" class="flex flex-wrap items-end gap-3">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Status</span>
                    <select name="status" class="w-full min-w-44 rounded-xl border border-edux-line px-4 py-2.5 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="pending" @selected($status === 'pending')>Pendentes</option>
                        <option value="all" @selected($status === 'all')>Todos</option>
                        <option value="deleted" @selected($status === 'deleted')>Conta excluida</option>
                        <option value="rejected" @selected($status === 'rejected')>Recusadas</option>
                    </select>
                </label>
                <button type="submit" class="edux-btn">Filtrar</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-card bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Solicitante</th>
                            <th class="px-4 py-3">Contato</th>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Solicitado em</th>
                            <th class="px-4 py-3">Resolucao</th>
                            <th class="px-4 py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $item)
                            <tr class="border-t border-slate-100 align-top">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-edux-primary">{{ $item->requested_name }}</div>
                                    <div class="text-xs text-slate-500">User ID: {{ $item->user_id ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $item->requested_email }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->requested_whatsapp ?: '-' }}</div>
                                </td>
                                <td class="max-w-sm px-4 py-3 text-slate-700">
                                    {{ $item->reason ?: '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                        'bg-emerald-100 text-emerald-800' => $item->status === 'deleted',
                                        'bg-slate-200 text-slate-700' => $item->status === 'rejected',
                                    ])>
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ optional($item->requested_at)->format('d/m/Y H:i') ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>{{ optional($item->resolved_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                    <div>{{ $item->resolver?->name ?: '-' }}</div>
                                    @if ($item->resolution_note)
                                        <div class="mt-1 text-slate-500">{{ $item->resolution_note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($item->status === \App\Models\AccountDeletionRequest::STATUS_PENDING)
                                        <div class="space-y-2">
                                            <form method="POST" action="{{ route('admin.account-deletion-requests.destroy-account', $item) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="edux-btn bg-red-600 text-white hover:bg-red-700"
                                                    onclick="return confirm('Confirmar exclusao imediata desta conta?')"
                                                >
                                                    Excluir conta agora
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.account-deletion-requests.reject', $item) }}" class="space-y-2">
                                                @csrf
                                                <textarea
                                                    name="resolution_note"
                                                    rows="2"
                                                    maxlength="1000"
                                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs"
                                                    placeholder="Motivo da recusa (opcional)"
                                                ></textarea>
                                                <button type="submit" class="edux-btn bg-slate-100 text-slate-700">Recusar</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500">Resolvida</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                    Nenhuma solicitacao encontrada para o filtro atual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-4">
                {{ $requests->links() }}
            </div>
        </section>
    </section>
@endsection
