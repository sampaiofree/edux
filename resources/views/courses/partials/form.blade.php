@php
    use App\Models\Course as CourseModel;

    $isEdit = $course?->exists;
    $action = $isEdit ? route('courses.update.post', $course) : route('courses.store');
    $formClasses = $formClasses ?? 'rounded-card bg-white p-6 shadow-card space-y-5';
    $supportWhatsappNumbers = $supportWhatsappNumbers ?? collect();
    $supportWhatsappModeOld = old('support_whatsapp_mode', $course->support_whatsapp_mode ?? CourseModel::SUPPORT_WHATSAPP_MODE_ALL);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="{{ $formClasses }}">
    @csrf

    <div class="grid gap-4 md:grid-cols-2">
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
        @if ($user->isAdmin())
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
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>ID Kavoo</span>
                <input
                    type="number"
                    name="kavoo_id"
                    min="0"
                    value="{{ old('kavoo_id', $course->kavoo_id) }}"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                <p class="text-xs text-slate-500">ID do produto utilizado pela integração Kavoo (opcional).</p>
                @error('kavoo_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
        @endif
    </div>

    @if ($user->isAdmin())
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

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="edux-btn">{{ $isEdit ? 'Salvar alterações' : 'Criar curso' }}</button>
        <a href="{{ route('admin.dashboard') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
    </div>
</form>
