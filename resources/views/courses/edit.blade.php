@extends('layouts.app')

@section('title', 'Editar curso')

@section('content')
    <div class="space-y-8">
        <section class="rounded-card bg-white p-6 shadow-card space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Editar curso</p>
                    <h1 class="font-display text-3xl text-edux-primary break-words">{{ $course->title }}</h1>
                    <p class="text-sm text-slate-600">Revise conteúdos, publique módulos e configure o teste final com segurança.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="edux-btn bg-edux-primary text-white">
                    📚 Voltar para o painel
                </a>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-edux-line/50 p-4">
                    <p class="text-xs uppercase text-slate-500">Status</p>
                    <p class="mt-1 text-xl font-semibold text-edux-primary">{{ ucfirst($course->status) }}</p>
                    <p class="text-xs text-slate-500">Duração total: {{ $course->duration_minutes ?? '—' }} min</p>
                    <p class="text-xs text-slate-500">Slug: <span class="font-mono">{{ $course->slug ?? '—' }}</span></p>
                </div>
                <div class="rounded-2xl border border-edux-line/50 p-4">
                    <p class="text-xs uppercase text-slate-500">Responsável</p>
                    <p class="mt-1 text-xl font-semibold text-edux-primary">{{ optional($course->owner)->name }}</p>
                    <p class="text-xs text-slate-500">Atualizado {{ optional($course->updated_at)->diffForHumans() }}</p>
                </div>
                <div class="rounded-2xl border border-edux-line/50 p-4">
                    <p class="text-xs uppercase text-slate-500">Conteúdo</p>
                    <p class="mt-1 text-sm text-slate-600">Gerencie módulos, aulas e avaliação final nos cards abaixo.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('courses.modules.edit', $course) }}" class="edux-btn  ">
                    📚 módulos e aulas
                </a>
                <a href="{{ route('courses.final-test.edit', $course) }}" class="edux-btn  ">
                    🧠 Gerenciar teste final
                </a>
                <a href="{{ route('courses.public.show', $course) }}" target="_blank" class="edux-btn ">
                    🌐 Página pública
                </a>
            </div>
        </section>

        <section class="space-y-4 rounded-card bg-white p-6 shadow-card">
            
            @include('courses.partials.form', [
                'course' => $course,
                'teachers' => $teachers,
                'user' => $user,
                'formClasses' => 'space-y-5'
            ])
        </section>

        <section class="space-y-4 rounded-card bg-white p-6 shadow-card">
            <livewire:admin.course-checkouts-manager :course-id="$course->id" />
        </section>
    </div>
@endsection
