@extends('layouts.app')

@section('title', 'Nova categoria')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Organização</p>
                <h1 class="font-display text-3xl text-edux-primary">Cadastrar categoria</h1>
                <p class="text-slate-600 text-sm">Crie uma nova categoria para destacar cursos relacionados.</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="edux-btn bg-white text-edux-primary">
                Voltar para a lista
            </a>
        </header>

        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf

            @include('admin.categories.form', ['category' => null])

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Criar categoria</button>
                <a href="{{ route('admin.categories.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
