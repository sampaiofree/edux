<!DOCTYPE html>
<html lang="pt-BR" x-data="studentLayout()" x-init="init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            use App\Models\SystemSetting;

            $settings = SystemSetting::current();
            $faviconUrl = $settings->assetUrl('favicon_path');
        @endphp
        <title>@yield('title', 'EduX')</title>
        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @endif
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
        <style>
            .plyr__video-wrapper iframe{
                width: 1000% !important;
                margin-left: -450% !important;
            }
            [x-cloak] { display: none !important; }
            /*.animate-spin-slow { animation: spin 2s linear infinite; }
            .shine-motion { animation: shine 1.8s linear infinite; }
            @keyframes spin { to { transform: rotate(360deg); } }
            @keyframes shine { from { transform: translateX(-100%); } to { transform: translateX(200%); } }*/
        </style>
    </head>
    <body class="min-h-screen bg-gray-50 text-edux-text">
        @php
            use Illuminate\Support\Facades\Schema;

            $logoUrl = $settings->assetUrl('default_logo_dark_path');
            $footerSchoolName = trim((string) ($settings->escola_nome ?? '')) ?: 'EduX';

            $unreadCount = (
                Schema::hasTable('notifications') &&
                Schema::hasColumn('notifications', 'notifiable_type') &&
                Schema::hasColumn('notifications', 'notifiable_id') &&
                auth()->check()
            ) ? auth()->user()->unreadNotifications()->count() : 0;
            $routeName = request()->route()?->getName();
            $user = auth()->user();
            $dashboardTabs = ['painel', 'cursos', 'vitrine', 'notificacoes', 'suporte', 'conta'];
            $dashboardTab = $routeName === 'dashboard'
                ? (in_array(request('tab'), $dashboardTabs, true) ? request('tab') : 'cursos')
                : null;
            $navActive = match (true) {
                str_starts_with($routeName ?? '', 'learning.courses.') => 'cursos',
                ($routeName === 'dashboard' && $dashboardTab === 'cursos') => 'cursos',
                ($routeName === 'dashboard' && $dashboardTab === 'vitrine') => 'vitrine',
                ($routeName === 'dashboard' && $dashboardTab === 'notificacoes') => 'notificacoes',
                ($routeName === 'dashboard' && $dashboardTab === 'suporte') => 'suporte',
                ($routeName === 'dashboard' && $dashboardTab === 'conta') => 'conta',
                default => $dashboardTab ?? 'painel',
            };
            $hideHeader = trim((string) $__env->yieldContent('hide_student_header', '0')) === '1';
            $hideFooter = trim((string) $__env->yieldContent('hide_student_footer', '0')) === '1';
            $hideBottomNav = trim((string) $__env->yieldContent('hide_student_bottom_nav', '0')) === '1';
            $mainClasses = trim((string) $__env->yieldContent('student_main_classes', 'mx-auto max-w-6xl space-y-6 px-2 py-8'));
            if ($mainClasses === '') {
                $mainClasses = 'mx-auto max-w-6xl space-y-6 px-2 py-8';
            }
        @endphp

        @unless ($hideHeader)
        <header class="sticky top-0 z-40 bg-blue-600 text-white shadow-md">
            <div class="mx-auto max-w-7xl px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <!-- Logo -->
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="EduX" class="h-9 w-auto">
                        @else
                            <span class="text-2xl font-bold text-gray-900">EduX</span>
                        @endif
                    </a>

                    <!-- A├º├Áes do Usu├írio -->
                    <div class="flex items-center gap-2">
                        <!-- ├ìcone Notifica├º├Áes com Badge -->
                        <a
                            href="{{ route('learning.notifications.index') }}"
                            class="relative flex h-10 w-10 items-center justify-center rounded-md  bg-white/10 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60 focus:ring-offset-2 focus:ring-offset-blue-600"
                            aria-label="Notifica├º├Áes"
                        >
                            <span class="sr-only">Notificações</span>
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            @if ($unreadCount > 0)
                                <span class="absolute -top-1 -right-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </header>
        @endunless

        <main class="{{ $mainClasses }}">
            <x-toast :status="session('status')" :errors="$errors->all()" :error="session('error')" />
            @yield('content')
        </main>

        @unless ($hideFooter)
        <footer class="hidden bg-gray-100 text-gray-600 md:block">
            <div class="mx-auto max-w-6xl px-4 py-6 text-center">
                <p class="font-semibold">
                    {{ now()->year }} {{ $footerSchoolName }}
                    <span title="Marca registada" class="ml-1 inline-block align-super text-xs text-gray-500" aria-hidden="true">®</span>
                    - Aprender é simples.
                </p>
            </div>
        </footer>
        @endunless

        @auth
            @if (auth()->user()->isStudent())
                <livewire:student.notification-modal />
            @endif
        @endauth

        @livewireScripts
        @include('partials.tracking.first-party')
        @stack('scripts')
        @unless ($hideBottomNav)
            <x-student-bottom-nav :active="$navActive" />
        @endunless
        <script>
            function studentLayout() {
                return {
                    mobileMenu: false,
                    init() {}
                }
            }
        </script>
    </body>
</html>
