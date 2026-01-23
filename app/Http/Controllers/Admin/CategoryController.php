<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');

        $categories = Category::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'summary' => ['nullable', 'string'],
            'status' => ['required', Rule::in([Category::STATUS_ACTIVE, Category::STATUS_INACTIVE])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $this->slugFromRequest($request),
            'summary' => $validated['summary'] ?? null,
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'image_path' => $this->storeImage($request),
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoria adicionada.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'summary' => ['nullable', 'string'],
            'status' => ['required', Rule::in([Category::STATUS_ACTIVE, Category::STATUS_INACTIVE])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $category->name = $validated['name'];
        $category->summary = $validated['summary'] ?? null;
        $category->status = $validated['status'];
        $category->sort_order = $validated['sort_order'] ?? 0;
        $category->slug = $this->slugFromRequest($request, $category->id);

        if ($request->boolean('remove_image') && $category->image_path) {
            Storage::disk('public')->delete($category->image_path);
            $category->image_path = null;
        }

        $imagePath = $this->storeImage($request, $category);

        if ($imagePath) {
            $category->image_path = $imagePath;
        }

        $category->save();

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('status', 'Categoria atualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoria removida.');
    }

    private function slugFromRequest(Request $request, ?int $ignoreId = null): string
    {
        $raw = $request->input('slug') ?: $request->input('name', '');
        $slug = Str::slug($raw);

        if ($slug === '') {
            $slug = (string) time();
        }

        return $this->ensureUniqueSlug($slug, $ignoreId);
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function storeImage(Request $request, ?Category $category = null): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        if ($category?->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        return $request->file('image')->store('category-images', 'public');
    }
}
