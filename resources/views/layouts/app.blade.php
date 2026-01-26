<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $settings = \App\Models\SystemSetting::current();
        $faviconUrl = $settings->assetUrl('favicon_path');
        $logoUrl = $settings->assetUrl('default_logo_dark_path');
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
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body class="min-h-screen bg-edux-background text-edux-text" x-data="{ mobileMenu: false }">
    @php
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();
    @endphp

    <header class="bg-edux-primary text-white shadow-lg">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
            <div class="flex items-center gap-3">
                <span class="flex items-center rounded-full bg-white/20 px-4 py-2 font-display text-xl tracking-wide">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="EduX" class="h-8 w-auto">
                    @else
                        EduX
                    @endif
                </span>
                @auth
                    <span class="text-sm opacity-80">{{ $user->preferredName() }} — {{ $user->role->label() }}</span>
                @endauth
            </div>
            <div class="flex items-center gap-2">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="edux-btn bg-red-500 text-white hover:shadow-lg">Sair</button>
                    </form>
                @else
                    <!--<a href="{{ route('login') }}" class="edux-btn bg-edux-cta text-edux-text">Entrar</a>-->
                @endauth
                <button class="md:hidden" @click="mobileMenu = !mobileMenu" aria-label="Abrir menu">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="md:hidden" x-show="mobileMenu" x-collapse>
            <nav class="space-y-2 border-t border-white/10 bg-edux-primary/95 px-4 pb-4 text-white">
                @if ($isAdmin)
                    <a href="{{ route('admin.users.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Usuarios</a>
                    <a href="{{ route('admin.identity') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Identidade</a>
                    <a href="{{ route('certificates.branding.edit') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Certificados</a>
                    <a href="{{ route('admin.certificates.generated.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Certificados gerados</a>
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Cursos cadastrados</a>
                    <a href="{{ route('admin.categories.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Categorias</a>
                    <!--<a href="{{ route('admin.certificates.payments') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Pagamentos certificados</a>-->
                    <a href="{{ route('admin.notifications.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Notificacoes</a>
                    <a href="{{ route('admin.kavoo.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Kavoo</a>
                    <a href="{{ route('admin.enroll.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Matriculas</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10 pb-28">
        <x-toast :status="session('status')" :errors="$errors->all()" />

        <div class="flex gap-6">
            @if ($isAdmin)
                <aside class="sticky top-20 hidden h-fit min-w-[220px] rounded-2xl bg-white p-4 shadow-card md:block">
                    <p class="mb-3 text-xs font-semibold uppercase text-slate-500">Menu Admin</p>
                    <ul class="space-y-2 text-sm font-semibold text-slate-700">
                        <li><a href="{{ route('admin.users.index') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Usuarios</a></li>
                        <li><a href="{{ route('admin.identity') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Identidade</a></li>
                        <li><a href="{{ route('certificates.branding.edit') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Certificados</a></li>
                        <li><a href="{{ route('admin.certificates.generated.index') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Certificados gerados</a></li>
                        <li><a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Cursos cadastrados</a></li>
                        <li><a href="{{ route('admin.categories.index') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Categorias</a></li>
                        <!--<li><a href="{{ route('admin.certificates.payments') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Pagamentos certificados</a></li>-->
                        <li><a href="{{ route('admin.notifications.index') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Notificacoes</a></li>
                        <li><a href="{{ route('admin.kavoo.index') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Kavoo</a></li>
                        <li><a href="{{ route('admin.enroll.index') }}" class="block rounded-lg px-3 py-2 hover:bg-edux-background">Matriculas</a></li>
                        
                    </ul>
                </aside>
            @endif

            <div class="flex-1 space-y-6">
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="fixed inset-x-0 bottom-0 bg-edux-primary text-white">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-6 text-center md:flex-row md:text-left">
            <p class="font-semibold">© {{ now()->year }} EduX — Aprender e simples.</p>
            <div class="flex gap-4 text-sm opacity-80">
                <a href="#">Politicas</a>
                <a href="#">Suporte</a>
                <a href="#">Status</a>
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>
