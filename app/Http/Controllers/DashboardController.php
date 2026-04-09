<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->hasBackofficeAccess()) {
            return redirect()->route('admin.dashboard');
        }

        $availableTabs = ['painel', 'cursos', 'vitrine', 'notificacoes', 'suporte', 'conta'];
        $requestedTab = $request->query('tab');
        $initialTab = in_array($requestedTab, $availableTabs, true) ? $requestedTab : 'cursos';
        $unreadCount = 0;

        if (
            \Illuminate\Support\Facades\Schema::hasTable('notifications') &&
            \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'notifiable_type') &&
            \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'notifiable_id')
        ) {
            $unreadCount = $user->unreadNotifications()->count();
        }

        return view('dashboard.student', [
            'user' => $user,
            'initialTab' => $initialTab,
            'availableTabs' => $availableTabs,
            'unreadCount' => $unreadCount,
        ]);
    }
}
