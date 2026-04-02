@extends('layouts.sa')

@section('title', 'Super Admin | Logs')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Super Admin / Diagnóstico</p>
                    <h1 class="font-display text-3xl text-edux-primary">Logs do sistema</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">
                        Baixe os arquivos de log gerados em <code>storage/logs</code> para analisar erros de produção, integrações e fluxo de notificações.
                    </p>
                </div>
                <a href="{{ route('sa.dashboard') }}" class="edux-btn bg-white text-edux-primary">
                    Voltar ao dashboard
                </a>
            </div>
        </header>

        <section class="rounded-card bg-white p-6 shadow-card">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Arquivos disponíveis</p>
                    <h2 class="font-display text-2xl text-edux-primary">Download de logs</h2>
                </div>
                <span class="rounded-full bg-edux-background px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ count($files) }} arquivo(s)
                </span>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-edux-line/60">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-edux-line/60 text-sm">
                        <thead class="bg-edux-background/70 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Arquivo</th>
                                <th class="px-4 py-3 font-semibold">Atualizado em</th>
                                <th class="px-4 py-3 font-semibold">Tamanho</th>
                                <th class="px-4 py-3 font-semibold text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-edux-line/60 bg-white">
                            @forelse ($files as $file)
                                <tr class="align-middle">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-edux-primary">{{ $file['name'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $file['modified_at']->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $file['size_human'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('sa.logs.download', $file['name']) }}" class="inline-flex items-center rounded-full border border-edux-line bg-edux-background px-4 py-2 text-xs font-semibold text-edux-primary transition hover:bg-white">
                                            Baixar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                        Nenhum arquivo de log foi encontrado em <code>storage/logs</code>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </section>
@endsection
