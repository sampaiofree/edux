@php
    use App\Models\PaymentWebhookLink as PaymentWebhookLinkModel;
@endphp

@extends('layouts.app')

@section('title', 'Configurar Webhook')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Pagamentos</p>
                <h1 class="font-display text-3xl text-edux-primary">{{ $link->name }}</h1>
                <p class="text-slate-600 text-sm">UUID: <code>{{ $link->endpoint_uuid }}</code></p>
                <p class="text-slate-600 text-sm">Endpoint: <code>{{ route('api.webhooks.in', ['endpoint_uuid' => $link->endpoint_uuid]) }}</code></p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.webhooks.events.index', $link) }}" class="edux-btn bg-white text-edux-primary">Ver eventos</a>
                <a href="{{ route('admin.webhooks.index') }}" class="edux-btn bg-white text-edux-primary">Voltar</a>
            </div>
        </header>

        @if (session('status'))
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('admin.webhooks.update', $link) }}" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf
            @method('PUT')

            <h2 class="font-semibold text-edux-primary">Configuracao do link</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600 md:col-span-2">
                    <span>Nome</span>
                    <input type="text" name="name" required value="{{ old('name', $link->name) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Acao do webhook</span>
                    <select name="action_mode" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        @foreach (PaymentWebhookLinkModel::actionModes() as $value => $label)
                            <option value="{{ $value }}" @selected(old('action_mode', $link->action_mode ?? PaymentWebhookLinkModel::ACTION_REGISTER) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('action_mode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Modo de seguranca</span>
                    <select name="security_mode" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="" @selected(old('security_mode', $link->security_mode) === null || old('security_mode', $link->security_mode) === '')>Sem assinatura</option>
                        <option value="header_secret" @selected(old('security_mode', $link->security_mode) === 'header_secret')>Header secret</option>
                        <option value="hmac_sha256" @selected(old('security_mode', $link->security_mode) === 'hmac_sha256')>HMAC SHA256</option>
                    </select>
                    @error('security_mode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Header de assinatura</span>
                    <input type="text" name="signature_header" value="{{ old('signature_header', $link->signature_header) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('signature_header') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600 md:col-span-2">
                    <span>Segredo</span>
                    <input type="text" name="secret" value="{{ old('secret', $link->secret) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('secret') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active)) class="rounded border border-edux-line text-edux-primary focus:ring-edux-primary/40">
                Link ativo
            </label>

            <button type="submit" class="edux-btn">Salvar configuracao</button>
        </form>

        <div class="space-y-6">
            <section class="rounded-card bg-white p-6 shadow-card space-y-4">
                <h2 class="font-semibold text-edux-primary">Payload base</h2>
                <p class="text-sm text-slate-600">
                    Cole um payload real para usar como referencia nos mapeamentos abaixo.
                </p>

                <form method="POST" action="{{ route('admin.webhooks.simulate', $link) }}" class="space-y-3">
                    @csrf
                    <textarea
                        name="payload_json"
                        rows="14"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 font-mono text-sm"
                        placeholder='{"customer":{"name":"Aluno Exemplo","email":"aluno@exemplo.com","whatsapp":"5511999999999"},"course":{"id":"CURSO-123"}}'
                    >{{ old('payload_json', $simulationPayload) }}</textarea>
                    @error('payload_json') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    <button type="submit" class="edux-btn w-full">Salvar payload para base</button>
                </form>

                @if ($simulationPreview)
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-edux-primary">Preview da extracao</h3>
                        <pre class="overflow-auto rounded-xl bg-slate-900 p-4 text-xs text-slate-100">{{ json_encode($simulationPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif
            </section>
        </div>

        <section class="rounded-card bg-white p-6 shadow-card space-y-4">
            @php
                $fieldMappingsByKey = $link->fieldMappings->keyBy('field_key');
                $fieldDescriptions = [
                    'buyer_name' => 'Nome do aluno/comprador',
                    'buyer_email' => 'Email do aluno/comprador',
                    'course_id' => 'ID externo do curso; comparado com curso_webhook_ids.webhook_id',
                    'buyer_whatsapp' => 'WhatsApp do aluno/comprador; salvo em users.whatsapp',
                ];
            @endphp
            <h2 class="font-semibold text-edux-primary">Mapeamento de campos</h2>
            @if ($jsonPathOptions === [])
                <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Salve um payload base na coluna da esquerda para liberar as opcoes de JSON path.
                </p>
            @endif
            <form method="POST" action="{{ route('admin.webhooks.field-mappings.upsert', $link) }}" class="space-y-4">
                @csrf
                @foreach ($fieldLabels as $fieldKey => $fieldLabel)
                    <div class="grid gap-3 rounded-xl border border-edux-line/70 p-4 md:grid-cols-[180px_minmax(0,1fr)] md:items-center">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-slate-800">{{ $fieldLabel }}</p>
                            <p class="text-xs text-slate-500">{{ $fieldDescriptions[$fieldKey] ?? '' }}</p>
                        </div>

                        <div class="space-y-2">
                            <select
                                name="field_mappings[{{ $fieldKey }}][json_path]"
                                class="w-full rounded-xl border border-edux-line px-4 py-3"
                                @disabled($jsonPathOptions === [])
                            >
                                <option value="">Nao mapear</option>
                                @foreach ($jsonPathOptions as $path)
                                    <option
                                        value="{{ $path }}"
                                        @selected(old("field_mappings.{$fieldKey}.json_path", $fieldMappingsByKey->get($fieldKey)?->json_path) === $path)
                                    >
                                        {{ $path }}
                                    </option>
                                @endforeach
                            </select>
                            @error("field_mappings.{$fieldKey}.json_path") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="edux-btn" @disabled($jsonPathOptions === [])>Salvar mapeamentos</button>
            </form>
        </section>

        <section class="rounded-card bg-white p-6 shadow-card space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-edux-primary">Eventos recentes</h2>
                <a href="{{ route('admin.webhooks.events.index', $link) }}" class="text-sm text-edux-primary hover:underline">Ver todos</a>
            </div>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-slate-500">
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Evento</th>
                            <th class="pb-2">Email</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Recebido</th>
                            <th class="pb-2 text-right">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($link->events as $event)
                            <tr>
                                <td class="py-2">#{{ $event->id }}</td>
                                <td class="py-2">{{ $event->external_event_code ?? '-' }}</td>
                                <td class="py-2">{{ $event->buyer_email ?? '-' }}</td>
                                <td class="py-2">{{ $event->processing_status?->value ?? $event->processing_status }}</td>
                                <td class="py-2">{{ $event->received_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('admin.webhooks.events.show', [$link, $event]) }}" class="text-edux-primary text-sm hover:underline">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-3 text-center text-slate-500">Sem eventos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
