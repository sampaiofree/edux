@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', 'Categorias')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Organização</p>
                <h1 class="font-display text-3xl text-edux-primary">Categorias de cursos</h1>
                <p class="text-slate-600 text-sm">Liste, edite ou remova as categorias visíveis no catálogo público.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="edux-btn">
                Nova categoria
            </a>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="flex flex-col gap-3 md:flex-row">
                <label class="flex-1 text-sm font-semibold text-slate-600">
                    <span class="sr-only">Buscar</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Buscar por nome"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <button type="submit" class="edux-btn w-full md:w-auto">Buscar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wide">
                            <th class="pb-2">Imagem</th>
                            <th class="pb-2">Nome</th>
                            <th class="pb-2">Slug</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Ordenação</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="py-3">
                                    @if ($category->imageUrl())
                                        <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="h-10 w-10 rounded-xl object-cover">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-edux-background text-xs text-slate-400">
                                            ---
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $category->name }}</div>
                                    <p class="text-xs text-slate-500">{{ $category->summary ? Str::limit($category->summary, 60) : 'Sem descrição' }}</p>
                                </td>
                                <td class="py-3">{{ $category->slug }}</td>
                                <td class="py-3">
                                    <span @class([
                                            'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold',
                                            'border-emerald-200 bg-emerald-50 text-emerald-700' => $category->status === 'active',
                                            'border-slate-200 bg-slate-50 text-slate-600' => $category->status !== 'active',
                                        ])>
                                        {{ $category->status === 'active' ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="py-3">{{ $category->sort_order }}</td>
                                <td class="py-3 text-right space-x-3">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Editar</a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-500">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-slate-500">
                                    Nenhuma categoria encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $categories->links() }}
        </div>
    </section>
@endsection
