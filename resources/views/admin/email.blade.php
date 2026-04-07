@extends('layouts.app')

@section('title', 'E-mail para alunos')

@section('content')
    <section class="space-y-6" x-data="{ audience: @js(old('audience', 'all')) }">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Comunicação</p>
                <h1 class="font-display text-3xl text-edux-primary">Enviar e-mail para alunos</h1>
                <p class="text-sm text-slate-600">Crie uma mensagem com texto livre e, se quiser, inclua um botão com link para direcionar os alunos.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="edux-btn bg-white text-edux-primary">
                Voltar ao dashboard
            </a>
        </header>

        <form method="POST" action="{{ route('admin.email.store') }}" class="rounded-card bg-white p-6 shadow-card space-y-6">
            @csrf

            <div class="grid gap-5 lg:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-700 lg:col-span-2">
                    <span>Assunto</span>
                    <input
                        type="text"
                        name="subject"
                        value="{{ old('subject') }}"
                        maxlength="160"
                        required
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>

                <div class="space-y-3">
                    <p class="text-sm font-semibold text-slate-700">Segmentação</p>

                    <label class="flex items-start gap-3 rounded-xl border border-edux-line px-4 py-3">
                        <input
                            type="radio"
                            name="audience"
                            value="all"
                            x-model="audience"
                            @checked(old('audience', 'all') === 'all')
                            class="mt-1 border-edux-line text-edux-primary focus:ring-edux-primary/30"
                        >
                        <span>
                            <span class="block font-semibold text-slate-800">Todos os alunos</span>
                            <span class="block text-sm text-slate-500">Envia para todos os alunos do tenant atual com e-mail cadastrado.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-xl border border-edux-line px-4 py-3">
                        <input
                            type="radio"
                            name="audience"
                            value="course"
                            x-model="audience"
                            @checked(old('audience') === 'course')
                            class="mt-1 border-edux-line text-edux-primary focus:ring-edux-primary/30"
                        >
                        <span>
                            <span class="block font-semibold text-slate-800">Filtrar por curso</span>
                            <span class="block text-sm text-slate-500">Envia apenas para alunos matriculados no curso selecionado.</span>
                        </span>
                    </label>
                </div>

                <label class="space-y-2 text-sm font-semibold text-slate-700" x-bind:class="{ 'opacity-60': audience !== 'course' }">
                    <span>Curso</span>
                    <select
                        name="course_id"
                        x-bind:disabled="audience !== 'course'"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30 disabled:bg-slate-100"
                    >
                        <option value="">Selecione um curso</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-700 lg:col-span-2">
                    <span>Texto do e-mail</span>
                    <textarea
                        name="body"
                        rows="10"
                        required
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >{{ old('body') }}</textarea>
                    <span class="block text-xs font-normal text-slate-500">As quebras de linha serão preservadas no e-mail enviado.</span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-700">
                    <span>Texto do botão</span>
                    <input
                        type="text"
                        name="button_text"
                        value="{{ old('button_text') }}"
                        maxlength="80"
                        placeholder="Ex.: Acessar plataforma"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-700">
                    <span>Link do botão</span>
                    <input
                        type="url"
                        name="button_url"
                        value="{{ old('button_url') }}"
                        placeholder="https://exemplo.com"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
            </div>

            @if ($errors->has('audience') || $errors->has('course_id') || $errors->has('button_text') || $errors->has('button_url'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach (['audience', 'course_id', 'button_text', 'button_url'] as $field)
                        @error($field)
                            <p>{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Enviar e-mail</button>
                <a href="{{ route('admin.dashboard') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
