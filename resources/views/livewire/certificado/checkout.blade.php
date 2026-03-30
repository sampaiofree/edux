<section class="space-y-6">
    <header class="rounded-card bg-white p-6 shadow-card">
        <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
        <h1 class="font-display text-3xl text-edux-primary">Gerar certificado</h1>
        <p class="text-slate-600 text-sm">Selecione o curso, confirme os dados e gere seu certificado.</p>
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

    <div class="flex items-center justify-between gap-3">
        <div class="space-y-1">
            <p class="text-sm font-semibold uppercase tracking-wide text-edux-primary">Formulário</p>
            <p class="text-sm text-slate-500">Preencha os dados abaixo para emitir seu certificado.</p>
        </div>
        <a href="{{ route('certificado.index') }}" wire:navigate class="edux-btn bg-white text-edux-primary">
            Voltar
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(280px,0.85fr)]">
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
</section>
