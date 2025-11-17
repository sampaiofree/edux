<!DOCTYPE html>
<html lang="pt-BR" x-data="studentLayout()" x-init="init()">
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
            .animate-spin-slow { animation: spin 2s linear infinite; }
            .shine-motion { animation: shine 1.8s linear infinite; }
            @keyframes spin { to { transform: rotate(360deg); } }
            @keyframes shine { from { transform: translateX(-100%); } to { transform: translateX(200%); } }
        </style>
    </head>
    <body class="min-h-screen bg-gray-50 text-edux-text">
        @php
            use Illuminate\Support\Facades\Schema;
            $unreadCount = (
                Schema::hasTable('notifications') &&
                Schema::hasColumn('notifications', 'notifiable_type') &&
                Schema::hasColumn('notifications', 'notifiable_id') &&
                auth()->check()
            ) ? auth()->user()->unreadNotifications()->count() : 0;
            $duxBalance = auth()->check()
                ? \App\Models\DuxWallet::firstOrCreate(['user_id' => auth()->id()], ['balance' => 0])->balance
                : 0;
            $routeName = request()->route()?->getName();
            $navActive = match (true) {
                str_starts_with($routeName ?? '', 'learning.courses.') => 'cursos',
                ($routeName === 'dashboard' && request('tab') === 'vitrine') => 'vitrine',
                ($routeName === 'dashboard' && request('tab') === 'notificacoes') => 'notificacoes',
                ($routeName === 'dashboard' && request('tab') === 'suporte') => 'suporte',
                ($routeName === 'dashboard' && request('tab') === 'conta') => 'conta',
                default => 'painel',
            };
        @endphp

        <header class="sticky top-0 z-40 bg-white text-gray-800 shadow-sm">
            <div class="mx-auto max-w-6xl px-4 py-3">
                <div class="flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-blue-600">EduX</a>
                    <div class="flex items-center gap-3">
                        <div class="relative flex items-center gap-2 px-4 py-2 rounded-2xl bg-gradient-to-br from-yellow-400 to-amber-500 shadow-[0_0_20px_rgba(255,200,0,0.5)] border-2 border-yellow-300 overflow-hidden select-none">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent shine-motion" style="animation: shine 1.8s linear infinite;"></div>
                            <span class="text-3xl relative z-10 animate-spin-slow">&#x1FA99;</span>
                            <div class="flex flex-col leading-tight relative z-10">
                                <span class="text-[10px] font-black text-white drop-shadow tracking-wider">DUXES</span>
                                <span class="text-2xl font-black text-white drop-shadow-lg"
                                      x-text="duxBalance"
                                      :class="bump ? 'animate-bounce scale-110' : 'transition-transform'">
                                </span>
                            </div>
                            <div x-show="pop"
                                 x-transition
                                 class="absolute -top-3 right-0 text-white text-sm font-black drop-shadow-lg animate-pulse">
                                +1 DUX
                            </div>
                            <div x-show="pop" class="absolute pointer-events-none -top-2 right-2 animate-bounce text-yellow-200">&#10024;</div>
                            <div x-show="pop" class="absolute top-0 right-5 animate-ping text-yellow-300">&#10024;</div>
                            <div x-show="pop" class="absolute top-2 right-3 animate-bounce text-yellow-100">&#10024;</div>
                        </div>
                    </div>
                    <nav class="hidden items-center gap-4 md:flex">
                        <a href="{{ route('dashboard') }}" class="font-semibold text-gray-700 hover:text-blue-600">Inicio</a>
                        <a href="{{ route('dashboard', ['tab' => 'cursos']) }}" class="font-semibold text-gray-700 hover:text-blue-600">Meus Cursos</a>
                        <a href="{{ route('dashboard', ['tab' => 'vitrine']) }}" class="font-semibold text-gray-700 hover:text-blue-600">Vitrine</a>
                        <a href="{{ route('account.edit') }}" class="font-semibold text-gray-700 hover:text-blue-600">Minha Conta</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="font-semibold text-gray-700 hover:text-blue-600">Sair</button>
                        </form>
                    </nav>
                    <a href="{{ route('learning.notifications.index') }}" class="relative p-2 rounded-full hover:bg-gray-100 md:hidden">
                        @if ($unreadCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                                {{ $unreadCount }}
                            </span>
                        @endif
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-6 px-4 py-8">
            @if (session('status'))
                <div class="rounded-lg border-l-4 border-emerald-500 bg-emerald-50 p-4 text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-red-900">
                    <strong class="font-semibold">Atencao</strong>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="hidden bg-gray-100 text-gray-600 md:block">
            <div class="mx-auto max-w-6xl px-4 py-6 text-center">
                <p class="font-semibold">(c) {{ now()->year }} EduX - Aprender e simples.</p>
            </div>
        </footer>

        @auth
            @if (auth()->user()->isStudent())
                <livewire:student.notification-modal />
            @endif
        @endauth

        @livewireScripts
        @stack('scripts')
        <x-student-bottom-nav :active="$navActive" />
        <script>
            function studentLayout() {
                return {
                    mobileMenu: false,
                    duxBalance: {{ $duxBalance }},
                    bump: false,
                    pop: false,
                    init() {
                        document.addEventListener('dux-earned', (event) => {
                            const amount = Number(event.detail?.amount ?? 0);
                            this.duxBalance += amount;
                            this.bump = true;
                            setTimeout(() => this.bump = false, 600);
                            this.pop = true;
                            setTimeout(() => this.pop = false, 700);
                        });
                    }
                }
            }
        </script>
    </body>
</html>
