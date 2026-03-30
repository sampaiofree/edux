@extends('layouts.sa')

@section('title', 'Super Admin | Editar Escola')

@section('content')
    @php
        $tenantLabel = trim((string) ($tenant->escola_nome ?? '')) ?: trim((string) ($tenant->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Super Admin / Escolas</p>
                <h1 class="font-display text-3xl text-edux-primary">Editar escola</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Você está editando o tenant <span class="font-semibold text-edux-primary">{{ $tenantLabel }}</span>
                    @if ($tenant->domain)
                        ({{ $tenant->domain }})
                    @endif
                    sem depender do domínio atual.
                </p>
            </div>

            <a href="{{ route('sa.tenants.index') }}" class="inline-flex items-center rounded-full border border-edux-line bg-white px-5 py-3 text-sm font-semibold text-edux-primary shadow-sm transition hover:bg-edux-background">
                Voltar para escolas
            </a>
        </header>

        <livewire:admin.system-assets-manager :systemSettingId="$tenant->id" />
    </section>
@endsection
