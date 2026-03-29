<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'tenants' => SystemSetting::query()->count(),
            'users' => User::withoutGlobalScopes()->count(),
            'students' => User::withoutGlobalScopes()->where('role', UserRole::STUDENT->value)->count(),
            'courses' => Course::withoutGlobalScopes()->count(),
            'enrollments' => Enrollment::withoutGlobalScopes()->count(),
        ];

        $recentUsers = User::withoutGlobalScopes()
            ->with('systemSetting')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentCourses = Course::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentEnrollments = Enrollment::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'course' => fn ($query) => $query->withoutGlobalScopes(),
                'user' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('sa.dashboard', compact('stats', 'recentUsers', 'recentCourses', 'recentEnrollments'));
    }
}
