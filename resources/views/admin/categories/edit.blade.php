@extends('layouts.app')

@section('title', 'Editar categoria')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Organização</p>
                <h1 class="font-display text-3xl text-edux-primary">{{ $category->name }}</h1>
                <p class="text-slate-600 text-sm">Atualize os dados e a imagem exibida na landing page.</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="edux-btn bg-white text-edux-primary">
                Voltar para a lista
            </a>
        </header>

        <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf
            @method('PUT')

            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </p>
            @endif

            @include('admin.categories.form', ['category' => $category])

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Salvar alterações</button>
                <a href="{{ route('admin.categories.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
