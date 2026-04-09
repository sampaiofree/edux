@extends('layouts.app')

@section('title', 'Novo usuário')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Equipe e alunos</p>
                <h1 class="font-display text-3xl text-edux-primary">Cadastrar usuário</h1>
                <p class="text-slate-600 text-sm">
                    Crie contas para administradores, professores ou alunos e defina uma senha temporária.
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="edux-btn bg-white text-edux-primary">
                Ver todos
            </a>
        </header>

        <livewire:admin.user-form />
    </section>
@endsection
