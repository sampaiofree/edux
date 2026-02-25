<div class="space-y-6">
    @php
        $exportQuery = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'utmSource' => $utmSource,
            'utmCampaign' => $utmCampaign,
            'citySlug' => $citySlug,
            'pageType' => $pageType,
            'eventName' => $eventName,
        ];
    @endphp

    <section class="rounded-card bg-white p-5 shadow-card">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Relatorio de marketing e funil</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900">Tracking Interno (First-Party)</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Origem de trafego, cliques, funil e eventos das paginas de cidade e LP de cursos.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.tracking.export.sources', $exportQuery) }}"
                    class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-edux-line bg-white px-4 py-2 text-sm font-semibold text-edux-primary hover:bg-edux-background"
                >
                    Exportar CSV (Origens)
                </a>
                <a
                    href="{{ route('admin.tracking.export.attributions', $exportQuery) }}"
                    class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-edux-line bg-white px-4 py-2 text-sm font-semibold text-edux-primary hover:bg-edux-background"
                >
                    Exportar CSV (Atribuicoes)
                </a>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-edux-line bg-white px-4 py-2 text-sm font-semibold text-edux-primary hover:bg-edux-background"
                >
                    Limpar filtros
                </button>
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-semibold text-slate-600">
                Periodo inicial
                <input type="date" wire:model.live="dateFrom" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <label class="text-sm font-semibold text-slate-600">
                Periodo final
                <input type="date" wire:model.live="dateTo" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <label class="text-sm font-semibold text-slate-600">
                UTM Source
                <select wire:model.live="utmSource" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
                    <option value="">Todos</option>
                    @foreach ($filterOptions['utmSources'] as $source)
                        <option value="{{ $source }}">{{ $source }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold text-slate-600">
                UTM Campaign
                <select wire:model.live="utmCampaign" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
                    <option value="">Todas</option>
                    @foreach ($filterOptions['utmCampaigns'] as $campaign)
                        <option value="{{ $campaign }}">{{ $campaign }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold text-slate-600">
                Cidade
                <select wire:model.live="citySlug" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
                    <option value="">Todas</option>
                    @foreach ($filterOptions['citySlugs'] as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold text-slate-600">
                Tipo de pagina
                <select wire:model.live="pageType" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
                    <option value="">Todas</option>
                    @foreach ($filterOptions['pageTypes'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold text-slate-600">
                Evento
                <select wire:model.live="eventName" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
                    <option value="">Todos</option>
                    @foreach ($filterOptions['eventNames'] as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="rounded-xl border border-dashed border-edux-line bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <p class="font-semibold text-slate-800">Filtro atual</p>
                <p class="mt-1">
                    {{ $dateFrom ?: '...' }} a {{ $dateTo ?: '...' }}
                    @if ($utmSource) • {{ $utmSource }} @endif
                    @if ($utmCampaign) • {{ $utmCampaign }} @endif
                    @if ($citySlug) • {{ $citySlug }} @endif
                </p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Eventos</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['events_count'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Total de eventos capturados</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sessoes</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['sessions_count'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Sessoes unicas no periodo</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visitantes</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['visitors_count'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Visitantes (cookie first-party)</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Leads</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['leads'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Evento Lead registrado</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Compras aprovadas</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['purchases'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">PurchaseApproved (webhook)</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Receita capturada</p>
            <p class="mt-2 text-2xl font-black text-slate-900">R$ {{ number_format((float) ($summary['revenue'] ?? 0), 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Somatorio de PurchaseApproved</p>
        </article>

        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Views da pagina cidade</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['city_views'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">CityCampaignView</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliques no hero</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['hero_clicks'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">CTA principal da cidade</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliques em curso</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['course_row_clicks'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">CTA course_row</p>
        </article>
        <article class="rounded-card bg-white p-4 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Checkout clicks</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($summary['checkout_clicks'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">LPCheckoutClick</p>
        </article>
    </section>

    <section class="rounded-card bg-white p-5 shadow-card">
        <div class="mb-4">
            <h2 class="text-lg font-black text-slate-900">Funil (eventos principais)</h2>
            <p class="text-sm text-slate-600">Taxa calculada em cima do primeiro passo do funil no filtro atual.</p>
        </div>
        <div class="space-y-3">
            @foreach ($funnelRows as $row)
                <div class="rounded-2xl border border-edux-line/70 bg-slate-50 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $row['label'] }}</p>
                            <p class="text-xs text-slate-500">{{ $row['event'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-base font-black text-slate-900">{{ number_format($row['total'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-500">{{ number_format($row['rate'], 1, ',', '.') }}%</p>
                        </div>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-edux-primary" style="width: {{ min(100, max(0, (float) $row['rate'])) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-card bg-white p-5 shadow-card">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-900">Origens de trafego</h2>
                <p class="text-sm text-slate-600">Agrupado por UTM Source / Medium / Campaign.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edux-line text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Origem</th>
                            <th class="px-2 py-2">Campanha</th>
                            <th class="px-2 py-2 text-right">Sessoes</th>
                            <th class="px-2 py-2 text-right">Cliques curso</th>
                            <th class="px-2 py-2 text-right">Checkout</th>
                            <th class="px-2 py-2 text-right">Lead</th>
                            <th class="px-2 py-2 text-right">Compras</th>
                            <th class="px-2 py-2 text-right">Receita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sourceRows as $row)
                            <tr class="border-b border-slate-100 align-top">
                                <td class="px-2 py-2">
                                    <p class="font-semibold text-slate-900">{{ $row->source_label }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->medium_label }}</p>
                                </td>
                                <td class="px-2 py-2 text-slate-700">{{ $row->campaign_label }}</td>
                                <td class="px-2 py-2 text-right font-semibold text-slate-900">{{ number_format((int) $row->sessions_count, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->course_clicks, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->checkout_clicks, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->leads, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->purchases, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">R$ {{ number_format((float) $row->revenue, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-2 py-4 text-center text-sm text-slate-500">Sem dados para os filtros selecionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-card bg-white p-5 shadow-card">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-900">Desempenho por cidade</h2>
                <p class="text-sm text-slate-600">Agrupado por cidade detectada nos eventos/sessao.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edux-line text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Cidade</th>
                            <th class="px-2 py-2 text-right">Sessoes</th>
                            <th class="px-2 py-2 text-right">Views cidade</th>
                            <th class="px-2 py-2 text-right">Cliques curso</th>
                            <th class="px-2 py-2 text-right">Checkout</th>
                            <th class="px-2 py-2 text-right">Compras</th>
                            <th class="px-2 py-2 text-right">Receita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cityRows as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-2 py-2">
                                    <p class="font-semibold text-slate-900">{{ $row->city_name_label }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->city_slug_label }}</p>
                                </td>
                                <td class="px-2 py-2 text-right font-semibold text-slate-900">{{ number_format((int) $row->sessions_count, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->city_views, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->course_clicks, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->checkout_clicks, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">{{ number_format((int) $row->purchases, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right text-slate-700">R$ {{ number_format((float) $row->revenue, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-2 py-4 text-center text-sm text-slate-500">Sem dados para os filtros selecionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="rounded-card bg-white p-5 shadow-card">
        <div class="mb-4">
            <h2 class="text-lg font-black text-slate-900">Desempenho por curso</h2>
            <p class="text-sm text-slate-600">Comparativo de cliques na cidade, views de LP e cliques em checkout.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-edux-line text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-2 py-2">Curso</th>
                        <th class="px-2 py-2 text-right">Clique cidade</th>
                        <th class="px-2 py-2 text-right">LP views</th>
                        <th class="px-2 py-2 text-right">ViewContent</th>
                        <th class="px-2 py-2 text-right">Checkout</th>
                        <th class="px-2 py-2 text-right">InitCheckout</th>
                        <th class="px-2 py-2 text-right">Compras</th>
                        <th class="px-2 py-2 text-right">Receita</th>
                        <th class="px-2 py-2 text-right">Eventos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courseRows as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-2 py-2">
                                <p class="font-semibold text-slate-900">{{ $row->course_slug_label }}</p>
                                <p class="text-xs text-slate-500">ID: {{ $row->course_id ?: 'n/a' }}</p>
                            </td>
                            <td class="px-2 py-2 text-right">{{ number_format((int) $row->city_course_clicks, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right">{{ number_format((int) $row->lp_views, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right">{{ number_format((int) $row->view_content, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right font-semibold text-slate-900">{{ number_format((int) $row->checkout_clicks, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right">{{ number_format((int) $row->initiate_checkout, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right">{{ number_format((int) $row->purchases, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right">R$ {{ number_format((float) $row->revenue, 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right text-slate-500">{{ number_format((int) $row->events_count, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-2 py-4 text-center text-sm text-slate-500">Sem eventos de curso para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-card bg-white p-5 shadow-card xl:col-span-1">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-900">Atribuição de conversão</h2>
                <p class="text-sm text-slate-600">Resumo por modelo de atribuição salvo no webhook.</p>
            </div>
            <div class="space-y-3">
                @forelse ($attributionModelRows as $row)
                    <div class="rounded-2xl border border-edux-line/70 bg-slate-50 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-900">{{ $row->attribution_model }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ number_format((int) $row->transactions_count, 0, ',', '.') }} transacoes
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-black text-slate-900">{{ number_format((int) $row->conversions_count, 0, ',', '.') }}</p>
                                <p class="text-xs text-slate-500">registros</p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2 text-xs text-slate-600">
                            <span>Kavoo IDs: {{ number_format((int) $row->kavoo_count, 0, ',', '.') }}</span>
                            <strong class="text-slate-900">R$ {{ number_format((float) $row->revenue, 2, ',', '.') }}</strong>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-edux-line bg-slate-50 p-4 text-sm text-slate-500">
                        Nenhuma atribuicao registrada no periodo selecionado.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-card bg-white p-5 shadow-card xl:col-span-2">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-900">Conversões atribuídas (detalhe)</h2>
                <p class="text-sm text-slate-600">Linhas de atribuição geradas no webhook com origem, cidade e transação.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edux-line text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Quando</th>
                            <th class="px-2 py-2">Modelo</th>
                            <th class="px-2 py-2">Transação</th>
                            <th class="px-2 py-2">Origem</th>
                            <th class="px-2 py-2">Campanha</th>
                            <th class="px-2 py-2">Cidade</th>
                            <th class="px-2 py-2">Curso</th>
                            <th class="px-2 py-2 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttributions as $row)
                            <tr class="border-b border-slate-100 align-top">
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-slate-600">
                                    {{ $row->occurred_at ? \Illuminate\Support\Carbon::parse($row->occurred_at)->format('d/m H:i:s') : '-' }}
                                </td>
                                <td class="px-2 py-2">
                                    <p class="font-semibold text-slate-900">{{ $row->attribution_model }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $row->session_uuid ? Str::limit($row->session_uuid, 18) : 'sem sessão' }}</p>
                                </td>
                                <td class="px-2 py-2 text-xs text-slate-600">
                                    <p class="font-semibold text-slate-800">{{ $row->transaction_code ?: '-' }}</p>
                                    <p class="text-[11px] text-slate-400">item {{ $row->item_product_id ?: '-' }}</p>
                                </td>
                                <td class="px-2 py-2 text-xs text-slate-600">{{ $row->source ?: '(direto)' }} / {{ $row->medium ?: '(direto)' }}</td>
                                <td class="px-2 py-2 text-xs text-slate-600">{{ $row->campaign ?: '(sem campanha)' }}</td>
                                <td class="px-2 py-2 text-xs text-slate-600">
                                    {{ $row->city_name ?: ($row->city_slug ?: '-') }}
                                </td>
                                <td class="px-2 py-2 text-xs text-slate-600">
                                    {{ $row->course_slug ?: ($row->course_id ? 'ID '.$row->course_id : '-') }}
                                </td>
                                <td class="px-2 py-2 text-right text-xs font-semibold text-slate-700">
                                    @if ($row->amount !== null)
                                        {{ ($row->currency ?: 'BRL') }} {{ number_format((float) $row->amount, 2, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-2 py-4 text-center text-sm text-slate-500">Nenhuma conversão atribuída encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="rounded-card bg-white p-5 shadow-card">
        <div class="mb-4">
            <h2 class="text-lg font-black text-slate-900">Eventos recentes (detalhe)</h2>
            <p class="text-sm text-slate-600">Ultimos eventos capturados no filtro atual para auditoria e diagnostico.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-edux-line text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-2 py-2">Quando</th>
                        <th class="px-2 py-2">Evento</th>
                        <th class="px-2 py-2">Pagina</th>
                        <th class="px-2 py-2">Origem</th>
                        <th class="px-2 py-2">Cidade</th>
                        <th class="px-2 py-2">Curso</th>
                        <th class="px-2 py-2">CTA</th>
                        <th class="px-2 py-2 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentEvents as $event)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-slate-600">
                                {{ \Illuminate\Support\Carbon::parse($event->occurred_at)->format('d/m H:i:s') }}
                            </td>
                            <td class="px-2 py-2">
                                <p class="font-semibold text-slate-900">{{ $event->event_name }}</p>
                            </td>
                            <td class="px-2 py-2 text-xs text-slate-600">{{ $event->page_type ?: '-' }}</td>
                            <td class="px-2 py-2 text-xs text-slate-600">
                                {{ $event->utm_source ?: '(direto)' }} / {{ $event->utm_medium ?: '(direto)' }}
                                <div class="text-[11px] text-slate-400">{{ $event->utm_campaign ?: '' }}</div>
                            </td>
                            <td class="px-2 py-2 text-xs text-slate-600">{{ $event->city_slug ?: '-' }}</td>
                            <td class="px-2 py-2 text-xs text-slate-600">{{ $event->course_slug ?: '-' }}</td>
                            <td class="px-2 py-2 text-xs text-slate-600">{{ $event->cta_source ?: '-' }}</td>
                            <td class="px-2 py-2 text-right text-xs font-semibold text-slate-700">
                                @if ($event->value !== null)
                                    {{ ($event->currency ?: 'BRL') }} {{ number_format((float) $event->value, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-2 py-4 text-center text-sm text-slate-500">Nenhum evento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
