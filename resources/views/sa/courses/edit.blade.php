@extends('layouts.sa')

@section('title', 'Super Admin | Editar curso')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Curso global</p>
                <h1 class="font-display text-3xl text-edux-primary break-words">{{ $course->title }}</h1>
                <p class="text-sm text-slate-600">Edite apenas os dados básicos do curso e, se necessário, reatribua a escola e o responsável.</p>
            </div>
            <a href="{{ route('sa.courses.index') }}" class="edux-btn bg-white text-edux-primary">Voltar para a lista</a>
        </header>

        <form
            method="POST"
            action="{{ route('sa.courses.update', $course->id) }}"
            class="rounded-card bg-white p-6 shadow-card space-y-5"
            x-data="{
                ownersByTenant: @js($ownersByTenant),
                selectedTenantId: @js($initialTenantId),
                selectedOwnerId: @js($initialOwnerId),
                init() {
                    this.syncOwnerSelection(true);
                    this.$watch('selectedTenantId', () => this.syncOwnerSelection(false));
                },
                get availableOwners() {
                    return this.ownersByTenant[String(this.selectedTenantId)] ?? [];
                },
                syncOwnerSelection(preserveCurrent) {
                    const owners = this.availableOwners;

                    if (owners.length === 0) {
                        this.selectedOwnerId = '';
                        return;
                    }

                    const hasCurrentOwner = owners.some((owner) => String(owner.id) === String(this.selectedOwnerId));

                    if (preserveCurrent && hasCurrentOwner) {
                        return;
                    }

                    if (! hasCurrentOwner) {
                        this.selectedOwnerId = String(owners[0].id);
                    }
                }
            }"
            x-init="init()"
        >
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Escola / tenant</span>
                    <select
                        name="system_setting_id"
                        x-model="selectedTenantId"
                        required
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) old('system_setting_id', $course->system_setting_id) === (string) $tenant->id)>
                                {{ $tenantLabel($tenant) }} — ID #{{ $tenant->id }}
                            </option>
                        @endforeach
                    </select>
                    @error('system_setting_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Responsável</span>
                    <select
                        name="owner_id"
                        x-model="selectedOwnerId"
                        :disabled="availableOwners.length === 0"
                        required
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        :class="{ 'cursor-not-allowed bg-slate-100 text-slate-400': availableOwners.length === 0 }"
                    >
                        <template x-if="availableOwners.length === 0">
                            <option value="">Nenhum administrador disponível</option>
                        </template>

                        <template x-for="owner in availableOwners" :key="owner.id">
                            <option :value="owner.id" x-text="owner.label"></option>
                        </template>
                    </select>
                    @error('owner_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    <p x-cloak x-show="availableOwners.length === 0" class="text-xs text-amber-600">
                        Nenhum administrador disponível para a escola selecionada.
                    </p>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Título</span>
                    <input type="text" name="title" value="{{ old('title', $course->title) }}" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Status</span>
                    <select name="status" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        @foreach (['draft' => 'Rascunho', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $course->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="block space-y-2 text-sm font-semibold text-slate-600">
                <span>Resumo</span>
                <input type="text" name="summary" value="{{ old('summary', $course->summary) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @error('summary') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="block space-y-2 text-sm font-semibold text-slate-600">
                <span>Descrição</span>
                <textarea name="description" rows="6" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">{{ old('description', $course->description) }}</textarea>
                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Duração (min)</span>
                    <input type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', $course->duration_minutes) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('duration_minutes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Publicação</span>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($course->published_at)->format('Y-m-d\\TH:i')) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('published_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Vídeo promocional</span>
                    <input type="url" name="promo_video_url" value="{{ old('promo_video_url', $course->promo_video_url) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('promo_video_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="rounded-2xl border border-edux-line/60 bg-edux-background/70 p-4 text-sm text-slate-600">
                <p class="font-semibold text-edux-primary">Contexto atual</p>
                <p class="mt-2">Slug atual: <span class="font-mono">{{ $course->slug }}</span></p>
                <p class="mt-1">Escola atual: {{ $tenantLabel($course->systemSetting) }}</p>
                <p class="mt-1">Responsável atual: {{ $course->owner?->name ?? '—' }}</p>
                <p class="mt-1">Módulos, teste final, branding, checkout e webhook IDs continuam fora desta edição global.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="submit"
                    class="edux-btn"
                    :disabled="availableOwners.length === 0"
                    :class="{ 'cursor-not-allowed opacity-60': availableOwners.length === 0 }"
                >
                    Salvar alterações
                </button>
                <a href="{{ route('sa.courses.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
