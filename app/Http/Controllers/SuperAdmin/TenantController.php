<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $tenants = SystemSetting::query()
            ->with([
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->withCount([
                'users as users_count' => fn ($query) => $query->withoutGlobalScopes(),
                'users as admins_count' => fn ($query) => $query->withoutGlobalScopes()->where('role', UserRole::ADMIN->value),
                'users as students_count' => fn ($query) => $query->withoutGlobalScopes()->where('role', UserRole::STUDENT->value),
                'courses as courses_count' => fn ($query) => $query->withoutGlobalScopes(),
                'enrollments as enrollments_count' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $isNumericSearch = is_numeric($search);

                $query->where(function ($subQuery) use ($search, $isNumericSearch): void {
                    $subQuery->where('escola_nome', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%")
                        ->orWhereHas('owner', function ($ownerQuery) use ($search): void {
                            $ownerQuery->withoutGlobalScopes()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });

                    if ($isNumericSearch) {
                        $subQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderByRaw('COALESCE(escola_nome, domain, id)')
            ->paginate(20)
            ->withQueryString();

        return view('sa.tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
        ]);
    }

    public function edit(int $id): View
    {
        $tenant = SystemSetting::query()
            ->with([
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->findOrFail($id);

        return view('sa.tenants.edit', [
            'tenant' => $tenant,
        ]);
    }
}
