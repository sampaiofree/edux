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
        <header class="bg-edux-primary text-white shadow-lg">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-white/20 px-4 py-2 font-display text-xl tracking-wide">EduX</span>
                    @auth
                        <span class="text-sm opacity-80">
                            {{ auth()->user()->preferredName() }} · {{ auth()->user()->role->label() }}
                        </span>
                    @endauth
                </div>
                <nav class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ route('account.edit') }}" class="edux-btn bg-white text-edux-primary">
                            ⚙️ Minha conta
                        </a>
                        <a href="{{ route('dashboard') }}" class="edux-btn bg-white text-edux-primary hover:-translate-y-0">
                            🏠 Dashboard
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.users.index') }}" class="edux-btn bg-white text-edux-primary">
                             👥 Usuarios
                            </a>
                            <a href="{{ route('admin.identity') }}" class="edux-btn bg-white text-edux-primary">
                              🎨 Identidade
                            </a>
                        @endif
                        @if (auth()->user()->isStudent())
                            <a href="{{ route('learning.notifications.index') }}" class="edux-btn bg-white text-edux-primary">
                                Notificacoes
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="edux-btn bg-red-500 text-white hover:shadow-lg">
                                Sair
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="edux-btn bg-edux-cta text-edux-text">
                            Entrar
                        </a>
                    @endauth
                </nav>
                <button class="md:hidden" @click="mobileMenu = !mobileMenu" aria-label="Abrir menu">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
            <div class="md:hidden" x-show="mobileMenu" x-collapse>
                <nav class="space-y-3 bg-edux-primary/95 px-4 pb-4 text-white">
                    @auth
                        <a href="{{ route('account.edit') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">⚙️ Minha conta</a>
                        <a href="{{ route('dashboard') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">📚 Dashboard</a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.users.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Usuarios</a>
                            <a href="{{ route('admin.identity') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Identidade visual</a>
                        @endif
                        @if (auth()->user()->isStudent())
                            <a href="{{ route('learning.notifications.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Notificacoes</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-red-500 px-4 py-3 font-semibold">Sair</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block rounded-xl bg-edux-cta px-4 py-3 text-center font-semibold text-edux-text">Entrar</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-6 px-4 py-10">
            @if (session('status'))
                <div class="rounded-2xl border-l-4 border-emerald-500 bg-emerald-50 p-5 text-emerald-900 shadow-card">
                    ✅ {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border-l-4 border-red-500 bg-red-50 p-5 text-red-900 shadow-card">
                    <strong class="font-semibold">⚠️ Atenção</strong>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

    <footer class="bg-edux-primary text-white">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-6 text-center md:flex-row md:text-left">
            <p class="font-semibold">© {{ now()->year }} EduX · Aprender é simples.</p>
            <div class="flex gap-4 text-sm opacity-80">
                <a href="#">Políticas</a>
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
    </body>
</html>
