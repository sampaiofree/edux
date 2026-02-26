<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportWhatsappNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportWhatsappNumberController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $numbers = SupportWhatsappNumber::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('label', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.support-whatsapp-numbers.index', compact('numbers', 'search'));
    }

    public function create(): View
    {
        return view('admin.support-whatsapp-numbers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        SupportWhatsappNumber::create($validated);

        return redirect()
            ->route('admin.support-whatsapp.index')
            ->with('status', 'Número de WhatsApp cadastrado.');
    }

    public function edit(SupportWhatsappNumber $supportWhatsappNumber): View
    {
        return view('admin.support-whatsapp-numbers.edit', compact('supportWhatsappNumber'));
    }

    public function update(Request $request, SupportWhatsappNumber $supportWhatsappNumber): RedirectResponse
    {
        $validated = $this->validated($request);

        $supportWhatsappNumber->update($validated);

        return redirect()
            ->route('admin.support-whatsapp.index')
            ->with('status', 'Número de WhatsApp atualizado.');
    }

    public function destroy(SupportWhatsappNumber $supportWhatsappNumber): RedirectResponse
    {
        $supportWhatsappNumber->delete();

        return redirect()
            ->route('admin.support-whatsapp.index')
            ->with('status', 'Número de WhatsApp removido.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'position' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['label'] = trim((string) $validated['label']);
        $validated['whatsapp'] = trim((string) $validated['whatsapp']);
        $validated['description'] = isset($validated['description'])
            ? trim((string) $validated['description'])
            : null;
        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['description'] === '') {
            $validated['description'] = null;
        }

        return $validated;
    }
}

