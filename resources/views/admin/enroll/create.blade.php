@extends('layouts.app')

@section('title', 'Nova matricula')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Matriculas</p>
                <h1 class="font-display text-3xl text-edux-primary">Nova matricula</h1>
                <p class="text-slate-600 text-sm">Associe um aluno a um curso e defina o progresso inicial.</p>
            </div>
            <a href="{{ route('admin.enroll.index') }}" class="edux-btn bg-white text-edux-primary">
                Voltar para a lista
            </a>
        </header>

        <!--<form method="GET" action="{{ route('admin.enroll.create') }}" class="rounded-card bg-white p-6 shadow-card space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Buscar curso</span>
                    <input
                        type="search"
                        name="course_search"
                        value="{{ $courseSearch ?? '' }}"
                        placeholder="Titulo, slug ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Buscar usuario</span>
                    <input
                        type="search"
                        name="user_search"
                        value="{{ $userSearch ?? '' }}"
                        placeholder="Nome, e-mail, WhatsApp ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn w-full md:w-auto">Buscar</button>
                <a href="{{ route('admin.enroll.create') }}" class="edux-btn bg-white text-edux-primary">Limpar filtros</a>
            </div>
            <p class="text-xs text-slate-500">
                Use os filtros para reduzir as listas de cursos e usuarios (ate 50 resultados).
            </p>
        </form>-->

        <form method="POST" action="{{ route('admin.enroll.store') }}" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf

            @include('admin.enroll.form-fields', [
                'enrollment' => null,
                'courses' => $courses,
                'users' => $users,
            ])

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Salvar matricula</button>
                <a href="{{ route('admin.enroll.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
