<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $settings = \App\Models\SystemSetting::current();
            $faviconUrl = $settings->assetUrl('favicon_path');
            $footerSchoolName = trim((string) ($settings->escola_nome ?? '')) ?: 'EduX';
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
            .student-shell-content {
                animation: student-screen-enter 180ms ease-out;
                will-change: opacity, transform;
            }
            [data-student-navigation-overlay] {
                opacity: 0;
                visibility: hidden;
                transition: opacity 160ms ease, visibility 160ms ease;
            }
            html[data-student-navigating='1'] [data-student-navigation-overlay] {
                opacity: 1;
                visibility: visible;
            }
            @keyframes student-screen-enter {
                from {
                    opacity: 0;
                    transform: translateY(6px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @media (prefers-reduced-motion: reduce) {
                .student-shell-content {
                    animation: none;
                }
                [data-student-navigation-overlay] {
                    transition: none;
                }
            }
        </style>
    </head>
    <body data-student-shell="1" class="min-h-screen bg-edux-background text-edux-text">
        @php
            use Illuminate\Support\Facades\Schema;
            $unreadCount = $unreadCount
                ?? (
                    Schema::hasTable('notifications')
                    && Schema::hasColumn('notifications', 'notifiable_type')
                    && Schema::hasColumn('notifications', 'notifiable_id')
                    && auth()->check()
                        ? auth()->user()->unreadNotifications()->count()
                        : 0
                );
            $navActive = 'cursos';
        @endphp
        <div class="student-shell-content">
            <main class="mx-auto max-w-6xl space-y-6 px-4 py-10">
                <x-toast :status="session('status')" :errors="$errors->all()" />

                @yield('content')
            </main>
        </div>

    <footer class="bg-edux-primary text-white">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-6 text-center md:flex-row md:text-left">
            <p class="font-semibold">�� {{ now()->year }} {{ $footerSchoolName }} �� Aprender Ǹ simples.</p>
            <div class="flex gap-4 text-sm opacity-80">
                <a href="#">Pol��ticas</a>
                <a href="#">Suporte</a>
                <a href="#">Status</a>
            </div>
        </div>
    </footer>
        @auth
            @if (auth()->user()->isStudent())
                <livewire:student.notification-modal />
            @endif
        @endauth
        @livewireScripts
        @stack('scripts')
        <!-- Adicione preload de fontes cr��ticas -->
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" as="style">

        <!-- Lazy load de componentes pesados -->
        <div x-data="{ loaded: false }" x-intersect="loaded = true">
            <template x-if="loaded">
                <livewire:student.catalog />
            </template>
        </div>

        <div
            data-student-navigation-overlay="1"
            class="pointer-events-none fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/10 backdrop-blur-[1px]"
            aria-hidden="true"
        >
            <div class="inline-flex items-center gap-3 rounded-2xl bg-white/95 px-4 py-3 shadow-xl ring-1 ring-slate-200/80">
                <span class="relative flex h-3.5 w-3.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-edux-primary/40"></span>
                    <span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-edux-primary"></span>
                </span>
                <span class="text-sm font-semibold text-slate-700">Carregando</span>
            </div>
        </div>
    </body>
</html> 
 
