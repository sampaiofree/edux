@php
    use App\Models\Course as CourseModel;

    $isEdit = $course?->exists;
    $action = $isEdit ? route('courses.update.post', $course) : route('courses.store');
    $formClasses = $formClasses ?? 'rounded-card bg-white p-6 shadow-card space-y-5';
    $supportWhatsappNumbers = $supportWhatsappNumbers ?? collect();
    $supportWhatsappModeOld = old('support_whatsapp_mode', $course->support_whatsapp_mode ?? CourseModel::SUPPORT_WHATSAPP_MODE_ALL);
    $accessModeOld = old('access_mode', $course->access_mode ?? CourseModel::ACCESS_MODE_PAID);
    $courseWebhookIdsOld = old('curso_webhook_ids');
    $courseWebhookIdsSource = is_array($courseWebhookIdsOld)
        ? $courseWebhookIdsOld
        : ($course?->exists
            ? $course->courseWebhookIds->map(fn ($courseWebhookId) => [
                'webhook_id' => $courseWebhookId->webhook_id,
                'platform' => $courseWebhookId->platform,
            ])->all()
            : []);
    $courseWebhookIdsInitial = collect($courseWebhookIdsSource)
        ->map(function ($courseWebhookId) {
            $webhookId = trim((string) data_get($courseWebhookId, 'webhook_id'));
            $platform = trim((string) data_get($courseWebhookId, 'platform'));

            return [
                'webhook_id' => $webhookId,
                'platform' => $platform,
            ];
        })
        ->filter(fn (array $courseWebhookId) => $courseWebhookId['webhook_id'] !== '')
        ->values()
        ->all();
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="{{ $formClasses }}">
    @csrf

    <div class="grid gap-4 md:grid-cols-3">
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Título</span>
            <input type="text" name="title" value="{{ old('title', $course->title) }}" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Status</span>
            <select name="status" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @foreach (['draft' => 'Rascunho', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $course->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Modo de acesso</span>
            <select name="access_mode" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @foreach (CourseModel::accessModeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($accessModeOld === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('access_mode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>
    </div>

    <label class="space-y-1 text-sm font-semibold text-slate-600">
        <span>Resumo curto</span>
        <input type="text" name="summary" value="{{ old('summary', $course->summary) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
        @error('summary') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <label class="space-y-1 text-sm font-semibold text-slate-600">
        <span>Descrição completa</span>
        <textarea name="description" rows="5" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">{{ old('description', $course->description) }}</textarea>
        @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <div class="grid gap-4 md:grid-cols-2">
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Onde pode atuar (lista)</span>
            <textarea name="atuacao" rows="4" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30" placeholder="Ex.: Escolas; Secretarias; Coordenação; Apoio administrativo">{{ old('atuacao', $course->atuacao) }}</textarea>
            <p class="text-xs text-slate-500">Separe os itens com <span class="font-semibold">;</span> (ponto e vírgula).</p>
            @error('atuacao') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>O que faz na prática (lista)</span>
            <textarea name="oquefaz" rows="4" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30" placeholder="Ex.: Atende alunos; Organiza documentos; Preenche planilhas; Dá suporte à equipe">{{ old('oquefaz', $course->oquefaz) }}</textarea>
            <p class="text-xs text-slate-500">Separe os itens com <span class="font-semibold">;</span> (ponto e vírgula).</p>
            @error('oquefaz') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Duração (min)</span>
            <input type="number" name="duration_minutes" min="1" value="{{ old('duration_minutes', $course->duration_minutes) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            @error('duration_minutes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Data publicação</span>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($course->published_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            @error('published_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>
        @if ($user->hasAdminPrivileges())
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Responsável</span>
                <select name="owner_id" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}" @selected(old('owner_id', $course->owner_id ?? $user->id) == $owner->id)>
                            {{ $owner->name }} ({{ $owner->role->label() }})
                        </option>
                    @endforeach
                </select>
                @error('owner_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
        @endif
    </div>

    @if ($user->hasAdminPrivileges())
        <div class="rounded-2xl border border-edux-line/70 p-4 space-y-4">
            <div>
                <p class="text-sm font-semibold text-slate-800">Atendimento via WhatsApp</p>
                <p class="text-xs text-slate-500">Defina se este curso usa todos os números cadastrados em rotatividade ou um número específico.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Modo de atendimento</span>
                    <select name="support_whatsapp_mode" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="{{ CourseModel::SUPPORT_WHATSAPP_MODE_ALL }}" @selected($supportWhatsappModeOld === CourseModel::SUPPORT_WHATSAPP_MODE_ALL)>
                            Todos (rotatividade)
                        </option>
                        <option value="{{ CourseModel::SUPPORT_WHATSAPP_MODE_SPECIFIC }}" @selected($supportWhatsappModeOld === CourseModel::SUPPORT_WHATSAPP_MODE_SPECIFIC)>
                            Número específico
                        </option>
                    </select>
                    @error('support_whatsapp_mode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Número específico (quando selecionado)</span>
                    <select name="support_whatsapp_number_id" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="">Selecione um número</option>
                        @foreach ($supportWhatsappNumbers as $supportWhatsappNumber)
                            <option value="{{ $supportWhatsappNumber->id }}" @selected((string) old('support_whatsapp_number_id', $course->support_whatsapp_number_id) === (string) $supportWhatsappNumber->id)>
                                {{ $supportWhatsappNumber->label }} — {{ $supportWhatsappNumber->whatsapp }}{{ $supportWhatsappNumber->is_active ? '' : ' (inativo)' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($supportWhatsappNumbers->isEmpty())
                        <p class="text-xs text-amber-600">Nenhum número cadastrado ainda. Cadastre em “WhatsApp atendimento”.</p>
                    @else
                        <p class="text-xs text-slate-500">Se o modo for “Todos”, este campo é ignorado.</p>
                    @endif
                    @error('support_whatsapp_number_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>
        </div>
    @endif

    @if ($user->hasAdminPrivileges())
        <div
            class="rounded-2xl border border-edux-line/70 p-4 space-y-4"
            x-data="{
                items: @js($courseWebhookIdsInitial),
                draftWebhookId: '',
                draftPlatform: '',
                error: '',
                normalize(value) {
                    return String(value ?? '').trim();
                },
                add() {
                    const webhookId = this.normalize(this.draftWebhookId);
                    const platform = this.normalize(this.draftPlatform);

                    if (webhookId === '') {
                        this.error = 'Informe um ID de webhook antes de adicionar.';
                        return;
                    }

                    if (platform === '') {
                        this.error = 'Informe a plataforma do ID de webhook antes de adicionar.';
                        return;
                    }

                    const duplicate = this.items.some((item) => this.normalize(item.webhook_id).toLowerCase() === webhookId.toLowerCase());
                    if (duplicate) {
                        this.error = 'Este ID de webhook já foi adicionado.';
                        return;
                    }

                    this.items.push({
                        webhook_id: webhookId,
                        platform: platform,
                    });

                    this.draftWebhookId = '';
                    this.draftPlatform = '';
                    this.error = '';

                    this.$nextTick(() => this.$refs.webhookIdInput?.focus());
                },
                remove(index) {
                    this.items.splice(index, 1);
                    this.error = '';
                },
            }"
        >
            <div>
                <p class="text-sm font-semibold text-slate-800">IDs de webhook</p>
                <p class="text-xs text-slate-500">Adicione múltiplos IDs por curso. Cada item deve informar a plataforma.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto] md:items-end">
                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>ID de webhook</span>
                    <input
                        x-ref="webhookIdInput"
                        x-model="draftWebhookId"
                        x-on:keydown.enter.prevent="add()"
                        type="text"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        placeholder="Ex.: 123456"
                    >
                </label>

                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Plataforma</span>
                    <input
                        x-model="draftPlatform"
                        x-on:keydown.enter.prevent="add()"
                        type="text"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        placeholder="Ex.: Hotmart"
                    >
                </label>

                <button type="button" class="edux-btn md:self-end" x-on:click="add()">Adicionar</button>
            </div>

            <p x-show="error" x-text="error" class="text-xs text-red-500"></p>
            @error('curso_webhook_ids') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            @if ($errors->first('curso_webhook_ids.*.webhook_id'))
                <span class="text-xs text-red-500">{{ $errors->first('curso_webhook_ids.*.webhook_id') }}</span>
            @endif
            @if ($errors->first('curso_webhook_ids.*.platform'))
                <span class="text-xs text-red-500">{{ $errors->first('curso_webhook_ids.*.platform') }}</span>
            @endif

            <div class="flex flex-wrap gap-2" x-show="items.length > 0">
                <template x-for="(item, index) in items" :key="`${item.webhook_id}-${index}`">
                    <div class="inline-flex items-center gap-2 rounded-full border border-edux-line bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <span class="font-mono text-xs sm:text-sm" x-text="item.webhook_id"></span>
                        <span
                            x-show="item.platform"
                            class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500"
                            x-text="item.platform"
                        ></span>
                        <button type="button" class="text-xs font-semibold text-rose-500" x-on:click="remove(index)">Remover</button>

                        <input type="hidden" :name="`curso_webhook_ids[${index}][webhook_id]`" :value="item.webhook_id">
                        <input type="hidden" :name="`curso_webhook_ids[${index}][platform]`" :value="item.platform ?? ''">
                    </div>
                </template>
            </div>

            <p x-show="items.length === 0" class="text-sm text-slate-500">Nenhum ID de webhook cadastrado ainda.</p>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Imagem de destaque</span>
            <input type="file" name="cover_image" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
            @error('cover_image') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            @if ($course?->coverImageUrl())
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $course->coverImageUrl() }}" alt="Imagem do curso" class="h-20 w-20 rounded-xl border border-edux-line object-cover">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <input type="checkbox" name="remove_cover_image" value="1" class="rounded border-edux-line text-edux-primary focus:ring-edux-primary/50">
                        <span>Remover imagem atual</span>
                    </label>
                </div>
            @endif
        </label>
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Video promocional (URL)</span>
            <input type="url" name="promo_video_url" value="{{ old('promo_video_url', $course->promo_video_url) }}" class="w-full rounded-xl border border-edux-line px-4 py-3">
            @error('promo_video_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>
    </div>

    @if ($user->hasAdminPrivileges())
        <div class="rounded-2xl border border-dashed border-edux-line p-4 space-y-4">
            <p class="font-semibold text-slate-700">Fundos personalizados do certificado</p>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Frente</span>
                    <input type="file" name="certificate_front_background" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
                    @if ($course?->certificateBranding?->front_background_url)
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ $course->certificateBranding->front_background_url }}" alt="Frente atual" class="h-20 w-20 rounded-xl border border-edux-line object-cover">
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <input type="checkbox" name="remove_certificate_front_background" value="1" class="rounded border-edux-line text-edux-primary focus:ring-edux-primary/50">
                                <span>Remover frente padrão</span>
                            </label>
                        </div>
                    @endif
                </label>
                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Verso</span>
                    <input type="file" name="certificate_back_background" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
                    @if ($course?->certificateBranding?->back_background_url)
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ $course->certificateBranding->back_background_url }}" alt="Verso atual" class="h-20 w-20 rounded-xl border border-edux-line object-cover">
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <input type="checkbox" name="remove_certificate_back_background" value="1" class="rounded border-edux-line text-edux-primary focus:ring-edux-primary/50">
                                <span>Remover verso padrão</span>
                            </label>
                        </div>
                    @endif
                </label>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="edux-btn">{{ $isEdit ? 'Salvar alterações' : 'Criar curso' }}</button>
        <a href="{{ route('admin.dashboard') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
    </div>
</form>
