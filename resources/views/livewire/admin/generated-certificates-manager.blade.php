<section x-data="{ modalOpen: false }" x-on:admin-certificate-saved.window="modalOpen = false" class="space-y-6" x-cloak>
    <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">Certificados</p>
            <h1 class="font-display text-3xl text-edux-primary">Certificados gerados</h1>
            <p class="text-slate-600 text-sm">Consulte todos os certificados emitidos e gere novos manualmente.</p>
        </div>
        <button type="button" class="edux-btn" @click="modalOpen = true">
            Gerar certificado
        </button>
    </header>

    @if ($errorMessage)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($statusMessage)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="rounded-card bg-white p-6 shadow-card space-y-4">
        <form method="GET" class="flex flex-col gap-3 md:flex-row" wire:submit.prevent>
            <label class="flex-1 text-sm font-semibold text-slate-600">
                <span class="sr-only">Buscar</span>
                <input
                    type="search"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="N\u00famero, aluno, curso, e-mail ou ID"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
            </label>
            <button type="button" class="edux-btn w-full md:w-auto" @click="$wire.resetPage()">Buscar</button>
        </form>

        <div class="overflow-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-slate-500 text-xs uppercase tracking-wide">
                        <th class="pb-2">N\u00famero</th>
                        <th class="pb-2">Curso</th>
                        <th class="pb-2">Aluno</th>
                        <th class="pb-2">Emitido em</th>
                        <th class="pb-2">Criado em</th>
                        <th class="pb-2 text-right">A\u00e7\u00f5es</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td class="py-3">
                                <div class="font-semibold text-edux-primary">{{ $certificate->number }}</div>
                                <p class="text-xs text-slate-500">ID #{{ $certificate->id }}</p>
                            </td>
                            <td class="py-3">
                                <div class="font-semibold text-edux-primary">
                                    {{ $certificate->course?->title ?? '-' }}
                                </div>
                                <p class="text-xs text-slate-500">ID #{{ $certificate->course_id ?? '-' }}</p>
                            </td>
                            <td class="py-3">
                                <div class="font-semibold text-edux-primary">
                                    {{ $certificate->user?->preferredName() ?? '-' }}
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ $certificate->user?->email ?? $certificate->user?->whatsapp ?? '-' }}
                                </p>
                            </td>
                            <td class="py-3">{{ $certificate->issued_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="py-3">{{ $certificate->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="py-3 text-right">
                                <a href="{{ route('admin.certificates.generated.download', $certificate) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">
                                    Baixar PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-sm text-slate-500">
                                Nenhum certificado encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $certificates->links() }}
    </div>

    <div
        x-show="modalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-start justify-center overflow-auto bg-black/60 px-4 py-10"
    >
        <div
            x-show="modalOpen"
            x-transition
            x-on:click.outside="modalOpen = false"
            x-on:keydown.escape.window="modalOpen = false"
            class="w-full max-w-5xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5"
        >
            <div class="flex items-center justify-between border-b border-edux-line/50 pb-4">
                <div>
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
                    <h2 class="font-display text-2xl text-slate-900">Gerar certificado</h2>
                    <p class="text-xs text-slate-500">Selecione curso e aluno para gerar o certificado.</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-500 hover:text-slate-800">
                    <span class="sr-only">Fechar</span>
                    &times;
                </button>
            </div>

            <div class="grid gap-6 mt-6 lg:grid-cols-[1.1fr,0.9fr]">
                <div class="rounded-card bg-white p-6 shadow-card space-y-4">
                    <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                        <span>Curso</span>
                        <select
                            name="course_id"
                            wire:model.live="courseId"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                            <option value="">Selecione um curso</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                        @error('courseId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                        <span>Aluno matriculado</span>
                        <select
                            name="user_id"
                            wire:model.live="userId"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            @disabled(! $courseId)
                        >
                            <option value="">Selecione um aluno</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->preferredName() }} - {{ $student->email }}</option>
                            @endforeach
                        </select>
                        @error('userId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        @if ($courseId && $students->isEmpty())
                            <p class="text-xs text-slate-500">
                                Nenhum aluno matriculado neste curso.
                            </p>
                        @endif
                    </label>

                    <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                        <span>Data de conclus\u00e3o</span>
                        <input
                            type="date"
                            wire:model.live="completionDate"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                        @error('completionDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        <p class="text-xs text-slate-500">
                            Se n\u00e3o informar, usaremos a data registrada na matr\u00edcula.
                        </p>
                    </label>

                    <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                        <span>CPF (opcional)</span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="cpf"
                            placeholder="Somente n\u00fameros"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                        @error('cpf') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    @php
                        $disableGenerate = ! $courseId || ! $userId;
                    @endphp

                    <button
                        type="button"
                        wire:click="generateCertificate"
                        class="edux-btn w-full"
                        @disabled($disableGenerate)
                    >
                        Gerar certificado
                    </button>
                </div>

                <div class="rounded-card bg-white p-6 shadow-card space-y-3">
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Resumo</p>
                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex items-center justify-between gap-3">
                            <span>Aluno</span>
                            <span class="font-semibold text-slate-800">{{ $selectedUser?->preferredName() ?? 'Selecione um aluno' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Curso</span>
                            <span class="font-semibold text-slate-800">{{ $selectedCourse?->title ?? 'Selecione um curso' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Conclus\u00e3o</span>
                            <span class="font-semibold text-slate-800">{{ $formattedCompletionDate ?? 'N\u00e3o definida' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>CPF</span>
                            <span class="font-semibold text-slate-800">{{ $formattedCpf ?? 'N\u00e3o informado' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
