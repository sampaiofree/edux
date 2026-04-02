@extends('layouts.sa')

@section('title', 'Super Admin')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Visão global</p>
                    <h1 class="font-display text-3xl text-edux-primary">Dashboard do super admin</h1>
                    <p class="mt-2 text-sm text-slate-600">Monitore usuários, alunos, cursos e matrículas de todas as escolas em uma única área.</p>
                </div>
                <a href="{{ route('sa.logs.index') }}" class="edux-btn bg-white text-edux-primary">
                    Baixar logs
                </a>
            </div>
        </header>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-card bg-white p-6 shadow-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Escolas</p>
                <p class="mt-3 text-4xl font-display text-edux-primary">{{ $stats['tenants'] }}</p>
                <a href="{{ route('sa.tenants.index') }}" class="mt-4 inline-flex text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Ver escolas</a>
            </article>
            <article class="rounded-card bg-white p-6 shadow-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Usuários</p>
                <p class="mt-3 text-4xl font-display text-edux-primary">{{ $stats['users'] }}</p>
                <a href="{{ route('sa.users.index') }}" class="mt-4 inline-flex text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Ver lista global</a>
            </article>
            <article class="rounded-card bg-white p-6 shadow-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Alunos</p>
                <p class="mt-3 text-4xl font-display text-edux-primary">{{ $stats['students'] }}</p>
                <a href="{{ route('sa.users.index', ['role' => 'student']) }}" class="mt-4 inline-flex text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Filtrar alunos</a>
            </article>
            <article class="rounded-card bg-white p-6 shadow-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Cursos</p>
                <p class="mt-3 text-4xl font-display text-edux-primary">{{ $stats['courses'] }}</p>
                <a href="{{ route('sa.courses.index') }}" class="mt-4 inline-flex text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Abrir cursos</a>
            </article>
            <article class="rounded-card bg-white p-6 shadow-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Matrículas</p>
                <p class="mt-3 text-4xl font-display text-edux-primary">{{ $stats['enrollments'] }}</p>
                <a href="{{ route('sa.enrollments.index') }}" class="mt-4 inline-flex text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Abrir matrículas</a>
            </article>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-card bg-white p-6 shadow-card space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Usuários recentes</p>
                        <h2 class="font-display text-2xl text-edux-primary">Últimos cadastros</h2>
                    </div>
                    <a href="{{ route('sa.users.index') }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Ver todos</a>
                </div>

                <div class="space-y-3">
                    @forelse ($recentUsers as $user)
                        <article class="rounded-2xl border border-edux-line/60 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-edux-primary">{{ $user->preferredName() }}</p>
                                    <p class="text-sm text-slate-600">{{ $user->email }}</p>
                                    <p class="text-xs text-slate-500">{{ $tenantLabel($user->systemSetting) }}</p>
                                </div>
                                <a href="{{ route('sa.users.edit', $user->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Editar</a>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">Nenhum usuário encontrado.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-card bg-white p-6 shadow-card space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Cursos recentes</p>
                        <h2 class="font-display text-2xl text-edux-primary">Últimos cursos</h2>
                    </div>
                    <a href="{{ route('sa.courses.index') }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Ver todos</a>
                </div>

                <div class="space-y-3">
                    @forelse ($recentCourses as $course)
                        <article class="rounded-2xl border border-edux-line/60 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-edux-primary">{{ $course->title }}</p>
                                    <p class="text-sm text-slate-600">{{ $tenantLabel($course->systemSetting) }}</p>
                                    <p class="text-xs text-slate-500">Responsável: {{ $course->owner?->name ?? '—' }}</p>
                                </div>
                                <a href="{{ route('sa.courses.edit', $course->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Editar</a>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">Nenhum curso encontrado.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-card bg-white p-6 shadow-card space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Matrículas recentes</p>
                        <h2 class="font-display text-2xl text-edux-primary">Últimas matrículas</h2>
                    </div>
                    <a href="{{ route('sa.enrollments.index') }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Ver todas</a>
                </div>

                <div class="space-y-3">
                    @forelse ($recentEnrollments as $enrollment)
                        <article class="rounded-2xl border border-edux-line/60 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-edux-primary">{{ $enrollment->course?->title ?? 'Curso removido' }}</p>
                                    <p class="text-sm text-slate-600">{{ $enrollment->user?->preferredName() ?? 'Usuário removido' }}</p>
                                    <p class="text-xs text-slate-500">{{ $tenantLabel($enrollment->systemSetting) }}</p>
                                </div>
                                <a href="{{ route('sa.enrollments.edit', $enrollment->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Editar</a>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">Nenhuma matrícula encontrada.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
