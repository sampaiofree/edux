@extends('layouts.app')

@section('title', 'Gerenciar modulos e aulas')

@section('content')
    <div class="space-y-8">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-1">
                <p class="text-xs uppercase tracking-[0.2em] text-edux-primary">Curso</p>
                <h1 class="font-display text-3xl text-edux-primary break-words">{{ $course->title }}</h1>
                <p class="text-sm text-slate-600 max-w-3xl">
                    Organize modulos, conecte aulas e defina claramente a jornada antes de publicar o curso.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('courses.edit', $course) }}" class="edux-btn bg-white text-edux-primary">
                    Resumo do curso
                </a>
                @if ($user->hasAdminPrivileges())
                    <a href="{{ route('courses.final-test.edit', $course) }}" class="edux-btn bg-white text-edux-primary">
                        Teste final
                    </a>
                @endif
                <a href="{{ route('courses.public.show', $course) }}" target="_blank" class="edux-btn">
                    Pagina publica
                </a>
            </div>
        </header>

        <section class="rounded-card bg-white p-6 shadow-card">
            <livewire:admin.modules-manager :course-id="$course->id" />
        </section>
    </div>
@endsection
