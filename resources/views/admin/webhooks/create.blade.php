@extends('layouts.app')

@section('title', 'Novo Link de Webhook')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Pagamentos</p>
                <h1 class="font-display text-3xl text-edux-primary">Novo link de webhook</h1>
                <p class="text-slate-600 text-sm">Crie um endpoint unico para receber eventos de pagamento.</p>
            </div>
            <a href="{{ route('admin.webhooks.index') }}" class="edux-btn bg-white text-edux-primary">
                Voltar para lista
            </a>
        </header>

        <form method="POST" action="{{ route('admin.webhooks.store') }}" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600 md:col-span-2">
                    <span>Nome do link</span>
                    <input type="text" name="name" required value="{{ old('name') }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Modo de seguranca</span>
                    <select name="security_mode" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="">Sem assinatura</option>
                        <option value="header_secret" @selected(old('security_mode') === 'header_secret')>Header secret</option>
                        <option value="hmac_sha256" @selected(old('security_mode') === 'hmac_sha256')>HMAC SHA256</option>
                    </select>
                    @error('security_mode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Header de assinatura</span>
                    <input type="text" name="signature_header" value="{{ old('signature_header') }}" placeholder="X-Signature" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('signature_header') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600 md:col-span-2">
                    <span>Segredo</span>
                    <input type="text" name="secret" value="{{ old('secret') }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('secret') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border border-edux-line text-edux-primary focus:ring-edux-primary/40">
                Link ativo
            </label>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Criar link</button>
                <a href="{{ route('admin.webhooks.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
