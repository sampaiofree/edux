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
            /*.animate-spin-slow { animation: spin 2s linear infinite; }
            .shine-motion { animation: shine 1.8s linear infinite; }
            @keyframes spin { to { transform: rotate(360deg); } }
            @keyframes shine { from { transform: translateX(-100%); } to { transform: translateX(200%); } }*/
        </style>
    </head>
    <body class="min-h-screen bg-gray-50 text-edux-text">
        @php
            use Illuminate\Support\Facades\Schema;
            use App\Models\SystemSetting;

            $settings = SystemSetting::current();
            $logoUrl = $settings->assetUrl('default_logo_dark_path');

            $unreadCount = (
                Schema::hasTable('notifications') &&
                Schema::hasColumn('notifications', 'notifiable_type') &&
                Schema::hasColumn('notifications', 'notifiable_id') &&
                auth()->check()
            ) ? auth()->user()->unreadNotifications()->count() : 0;
            $duxBalance = session()->has('dux_balance')
                ? (int) session('dux_balance')
                : (auth()->check()
                    ? \App\Models\DuxWallet::firstOrCreate(['user_id' => auth()->id()], ['balance' => 0])->balance
                    : 0);
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

        <!-- HEADER MELHORADO: Limpo, branco e com ações agrupadas -->
        <header class="sticky top-0 z-40 bg-blue-600 text-white shadow-md">
            <div class="mx-auto max-w-7xl px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <!-- Logo -->
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="EduX" class="h-9 w-auto">
                        @else
                            <span class="text-2xl font-bold text-blue-600">EduX</span>
                        @endif
                    </a>

                    <!-- Ações do Usuário (Desktop) -->
                    <div class="hidden md:flex items-center gap-4">
                        <nav class="flex items-center gap-4">
                            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-gray-600 hover:text-blue-600">Início</a>
                            <a href="{{ route('dashboard', ['tab' => 'cursos']) }}" class="text-sm font-semibold text-gray-600 hover:text-blue-600">Meus Cursos</a>
                            <a href="{{ route('dashboard', ['tab' => 'vitrine']) }}" class="text-sm font-semibold text-gray-600 hover:text-blue-600">Vitrine</a>
                        </nav>
                        
                        <div class="h-6 w-px bg-gray-200"></div>

                        <div class="flex items-center gap-2">
                            <!-- Ícone DUX com Badge -->
                            <div class="relative">
                                <button type="button" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 focus:outline-none" title="Seus DUX">
                                    <span class="text-2xl">🪙</span>
                                </button>
                                <div x-text="duxBalance"
                                     :class="bump ? 'scale-125' : ''"
                                     style="transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);"
                                     class="absolute -top-1 -right-2 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-amber-400 px-1 text-xs font-bold text-white shadow">
                                </div>
                            </div>
                            
                            <!-- Ícone Notificações com Badge -->
                            <a href="{{ route('learning.notifications.index') }}" class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100" title="Notificações">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                @if ($unreadCount > 0)
                                    <span class="absolute -top-1 -right-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white shadow">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </a>
                        </div>
                        
                        <!-- Menu de Perfil (Dropdown) -->
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-600 font-bold text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                {{ $user->initials ?? '' }}
                            </button>
                            <div x-show="open" x-transition x-cloak class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
                                <a href="{{ route('account.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Minha Conta</a>
                                <div class="my-1 h-px bg-gray-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Sair</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Ações do Usuário (Mobile) -->
                    <div class="flex items-center gap-2 md:hidden">
                         <!-- Ícone DUX com Badge -->
                        <div class="relative">
                            <button type="button" class="p-2 rounded-full text-gray-500 hover:bg-gray-100">
                                <span class="text-2xl">🪙</span>
                            </button>
                            <div x-text="duxBalance" :class="bump ? 'scale-125' : ''" style="transition: transform 0.3s;" class="absolute -top-1 -right-2 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-amber-400 px-1 text-xs font-bold text-white"></div>
                        </div>
                        
                        <!-- Ícone Notificações com Badge -->
                        <a href="{{ route('learning.notifications.index') }}" class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            @if ($unreadCount > 0)
                                <span class="absolute -top-1 -right-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </div>
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
                            const balanceFromEvent = Number(event.detail?.balance ?? NaN);

                            if (!Number.isNaN(balanceFromEvent)) {
                                this.duxBalance = balanceFromEvent;
                            } else {
                                this.duxBalance += amount;
                            }

                            if (!amount && Number.isNaN(balanceFromEvent)) {
                                return;
                            }
                            this.bump = true;
                            setTimeout(() => this.bump = false, 600);
                            this.pop = true;
                            setTimeout(() => this.pop = false, 700);
                        });
                    }
                }
            }
        </script>
        @if (session()->has('dux_earned_amount'))
            <script>
                (() => {
                    const amount = Number(@json(session('dux_earned_amount')));
                    const balanceRaw = @json(session('dux_balance'));
                    const hasBalance = balanceRaw !== null && balanceRaw !== undefined;
                    const detail = hasBalance
                        ? { amount, balance: Number(balanceRaw) }
                        : { amount };

                    const emit = () => document.dispatchEvent(new CustomEvent('dux-earned', { detail }));

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', emit, { once: true });
                    } else {
                        requestAnimationFrame(emit);
                    }

                    // Livewire navigate nao dispara DOMContentLoaded; reforca o popup apos navegacao SPA
                    window.addEventListener('livewire:navigated', () => emit(), { once: true });
                })();
            </script>
        @endif
    </body>
</html>
