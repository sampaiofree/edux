<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kavoo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class KavooController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');

        $kavoos = Kavoo::query()
            ->withCustomerRelations()
            ->when($search, fn ($query) => $query
                ->where(function ($sub) use ($search) {
                    $sub->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('item_product_name', 'like', "%{$search}%")
                        ->orWhere('status_code', 'like', "%{$search}%");
                }))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.kavoo.index', compact('kavoos', 'search'));
    }

    public function create(): View
    {
        return view('admin.kavoo.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($request));

        Kavoo::create($validated);

        return redirect()
            ->route('admin.kavoo.index')
            ->with('status', 'Registro Kavoo criado.');
    }

    public function edit(Kavoo $kavoo): View
    {
        return view('admin.kavoo.edit', compact('kavoo'));
    }

    public function update(Request $request, Kavoo $kavoo): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($request, $kavoo));

        $kavoo->update($validated);

        return redirect()
            ->route('admin.kavoo.index')
            ->with('status', 'Registro Kavoo atualizado.');
    }

    public function destroy(Kavoo $kavoo): RedirectResponse
    {
        $kavoo->delete();

        return redirect()
            ->route('admin.kavoo.index')
            ->with('status', 'Registro Kavoo removido.');
    }

    private function validationRules(Request $request, ?Kavoo $kavoo = null): array
    {
        $itemProductId = $request->input('item_product_id');
        $transactionCode = $request->input('transaction_code');

        $transactionRules = ['nullable', 'string', 'max:255'];
        if ($transactionCode && $itemProductId !== null && $itemProductId !== '') {
            $transactionRules[] = Rule::unique('kavoo', 'transaction_code')
                ->where('item_product_id', $itemProductId)
                ->ignore($kavoo?->id);
        }

        return [
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_first_name' => ['nullable', 'string', 'max:255'],
            'customer_last_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'item_product_id' => ['nullable', 'integer', 'min:0'],
            'item_product_name' => ['nullable', 'string', 'max:255'],
            'transaction_code' => $transactionRules,
            'status_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
