<!DOCTYPE html>
<html lang="pt-BR" x-data="{ mobileMenu: false }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'EduX')</title>
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
        </style>
    </head>
    <body class="min-h-screen bg-edux-background text-edux-text">
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
        <main class="mx-auto max-w-6xl space-y-6 px-4 py-10">
            <x-toast :status="session('status')" :errors="$errors->all()" />

            @yield('content')
        </main>

    <footer class="bg-edux-primary text-white">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-6 text-center md:flex-row md:text-left">
            <p class="font-semibold">�� {{ now()->year }} EduX �� Aprender Ǹ simples.</p>
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

        <!-- Service Worker para cache -->
        @push('scripts')
        <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
        </script>
        @endpush
    </body>
</html> 
 
