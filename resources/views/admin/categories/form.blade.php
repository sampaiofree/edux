@php
    use App\Models\Category;

    $category ??= null;
    $statusOld = old('status', $category?->status ?? Category::STATUS_ACTIVE);
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Nome</span>
        <input
            type="text"
            name="name"
            value="{{ old('name', $category->name ?? '') }}"
            required
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Slug</span>
        <input
            type="text"
            name="slug"
            value="{{ old('slug', $category->slug ?? '') }}"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        <p class="text-xs text-slate-400">Se deixar em branco, o sistema gera automaticamente.</p>
        @error('slug') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>

<label class="space-y-2 text-sm font-semibold text-slate-600 block">
    <span>Resumo</span>
    <textarea
        rows="4"
        name="summary"
        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
    >{{ old('summary', $category->summary ?? '') }}</textarea>
    @error('summary') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
</label>

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Status</span>
        <select
            name="status"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
            <option value="{{ Category::STATUS_ACTIVE }}" @selected($statusOld === Category::STATUS_ACTIVE)>Ativa</option>
            <option value="{{ Category::STATUS_INACTIVE }}" @selected($statusOld === Category::STATUS_INACTIVE)>Inativa</option>
        </select>
        @error('status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Ordem (menor aparece primeiro)</span>
        <input
            type="number"
            name="sort_order"
            min="0"
            value="{{ old('sort_order', $category->sort_order ?? 0) }}"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        @error('sort_order') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>

<label class="space-y-2 text-sm font-semibold text-slate-600 block">
    <span>Imagem de destaque</span>
    <input
        type="file"
        name="image"
        accept="image/*"
        class="w-full rounded-xl border border-dashed border-edux-line px-4 py-3 file:mr-3 file:rounded-lg file:border-none file:bg-edux-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
    >
    @error('image') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

    @if ($category?->imageUrl())
        <div class="flex items-center gap-3 text-xs text-slate-500 pt-3">
            <img src="{{ $category->imageUrl() }}" alt="Imagem atual" class="h-12 w-12 rounded-xl object-cover">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="remove_image" value="1" class="text-edux-primary focus:ring-edux-primary">
                <span>Remover imagem atual</span>
            </label>
        </div>
    @endif
</label>
