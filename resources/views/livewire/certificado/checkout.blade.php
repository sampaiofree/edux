<section x-data="{ modalOpen: false }" class="space-y-6" x-cloak>
    <header class="rounded-card bg-white p-6 shadow-card">
        <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
        <h1 class="font-display text-3xl text-edux-primary">Meus certificados</h1>
        <p class="text-slate-600 text-sm">Baixe seu certificado ou clique em Gerar certificado.</p>
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
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="uppercase tracking-wide text-edux-primary">Certificados gerados</p>
                <p class="text-slate-500 text-sm">Lista de certificados ja emitidos com seus links publicos.</p>
            </div>
            <span class="text-xs uppercase tracking-wide text-slate-400">{{ $certificates->count() }} certificados</span>
        </div>

        @if ($certificates->isEmpty())
            <p class="text-sm text-slate-500">
                Nenhum certificado gerado ainda. Gere o primeiro clicando no botao abaixo.
            </p>
        @else
            <div class="space-y-4">
                @foreach ($certificates as $certificate)
                    @php
                        $downloadRoute = $certificate->course
                            ? route('learning.courses.certificate.download', [$certificate->course->slug, $certificate])
                            : null;
                        $publicUrl = $certificate->public_token
                            ? route('certificates.verify', $certificate->public_token)
                            : null;
                    @endphp
                    <div class="rounded-2xl border border-edux-line px-4 py-4 md:px-6 md:py-5 md:flex md:items-center md:justify-between">
                        <div class="flex items-center gap-4 md:gap-6">
                            <div class="h-16 w-16 overflow-hidden rounded-2xl bg-slate-100">
                                @if ($certificate->course?->coverImageUrl())
                                    <img
                                        src="{{ $certificate->course->coverImageUrl() }}"
                                        alt="{{ $certificate->course->title }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold uppercase text-slate-400">
                                        Sem imagem
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p class="text-2xl font-semibold text-slate-800">
                                    {{ $certificate->course?->title ?? 'Curso excluido' }}
                                </p>
                                <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                                    <span>Emitido em {{ $certificate->issued_at?->format('d/m/Y') ?? '—' }}</span>
                                    <span>Numero {{ $certificate->number ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 md:mt-0 md:ml-8">
                            @if ($downloadRoute)
                                <a
                                    href="{{ $downloadRoute }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="edux-btn text-white px-4 py-2 text-sm font-semibold text-edux-primary shadow-sm"
                                >
                                    Baixar PDF
                                </a>
                            @endif
                            @if ($publicUrl)
                                <a
                                    href="{{ $publicUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="edux-btn bg-edux-primary px-4 py-2 text-sm font-semibold text-white hover:bg-edux-primary/80"
                                >
                                    Ver link publico
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex justify-end">
        <button
            type="button"
            class="edux-btn"
            @click="modalOpen = true"
        >
            Gerar certificado
        </button>
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
                    <p class="text-xs text-slate-500">Selecione o curso, confirme os dados e gere seu PDF.</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-500 hover:text-slate-800">
                    <span class="sr-only">Fechar</span>
                    &times;
                </button>
            </div>

            <div class="grid gap-6 mt-6">
                <div class="rounded-card bg-white p-6 shadow-card space-y-4">
                    @if ($enrollments->isEmpty())
                        <p class="text-sm text-slate-500">
                            Voce ainda nao possui matriculas. Entre em um curso para gerar certificados.
                        </p>
                    @else
                        <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                            <span>Curso matriculado</span>
                            <select
                                name="course_id"
                                wire:model.live="courseId"
                                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            >
                                <option value="">Selecione um curso</option>
                                @foreach ($enrollments as $enrollment)
                                    <option value="{{ $enrollment->course_id }}">
                                        {{ $enrollment->course?->title ?? 'Curso sem titulo' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('courseId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                            <span>Data de conclusao</span>
                            <input
                                type="date"
                                wire:model.live="completionDate"
                                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            >
                            @error('completionDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500">
                                Se nao informar, usaremos a data registrada na matricula.
                            </p>
                        </label>

                        <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                            <span>CPF (opcional)</span>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="cpf"
                                placeholder="Somente numeros"
                                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            >
                            @error('cpf') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-2 text-sm font-semibold text-slate-600 block">
                            <span>Concluiu todas as aulas?</span>
                            <select
                                name="completion_confirmed"
                                wire:model.live="completionConfirmed"
                                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            >
                                <option value="">Selecione</option>
                                <option value="yes">Sim, conclui todas as aulas</option>
                                <option value="no">Ainda nao</option>
                            </select>
                            @error('completionConfirmed') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </label>

                        @php
                            $disableGenerate = $enrollments->isEmpty() || ! $courseId || $completionConfirmed !== 'yes';
                        @endphp

                        <button
                            type="button"
                            wire:click="generateCertificate"
                            class="edux-btn w-full"
                            @disabled($disableGenerate)
                        >
                            Gerar certificado
                        </button>
                    @endif
                </div>

                <div class="rounded-card bg-white p-6 shadow-card">
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Resumo</p>
                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex items-center justify-between gap-3">
                            <span>Aluno</span>
                            <span class="font-semibold text-slate-800">{{ $studentName }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Curso</span>
                            <span class="font-semibold text-slate-800">{{ $courseName ?? 'Selecione um curso' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Conclusao</span>
                            <span class="font-semibold text-slate-800">{{ $formattedCompletionDate ?? 'Nao definida' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>CPF</span>
                            <span class="font-semibold text-slate-800">{{ $formattedCpf ?? 'Nao informado' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Confirmacao</span>
                            <span class="font-semibold text-slate-800">
                                {{ $completionConfirmed === 'yes' ? 'Concluido' : 'Pendente' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
