<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $settings = \App\Models\SystemSetting::resolveCurrent() ?? \App\Models\SystemSetting::forUser(auth()->user());
        $faviconUrl = $settings?->assetUrl('favicon_path');
        $logoUrl = $settings?->assetUrl('default_logo_dark_path');
        $contextName = trim((string) ($settings?->escola_nome ?? '')) ?: trim((string) ($settings?->domain ?? 'Sem tenant'));
    @endphp
    <title>@yield('title', 'Super Admin | EduX')</title>
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
    @endphp

    <header class="bg-edux-primary text-white shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4">
            <div class="flex items-center gap-3">
                <span class="flex items-center rounded-full bg-white/20 px-4 py-2 font-display text-xl tracking-wide">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="EduX" class="h-8 w-auto">
                    @else
                        EduX
                    @endif
                </span>
                <div class="space-y-0.5">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70">Super Admin</p>
                    <p class="text-sm font-semibold">{{ $contextName }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @auth
                    <span class="hidden text-sm opacity-80 md:inline">{{ $user?->preferredName() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="edux-btn bg-red-500 text-white hover:shadow-lg">Sair</button>
                    </form>
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
                <a href="{{ route('sa.dashboard') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Dashboard</a>
                <a href="{{ route('sa.tenants.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Escolas</a>
                <a href="{{ route('sa.users.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Usuários</a>
                <a href="{{ route('sa.courses.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Cursos</a>
                <a href="{{ route('sa.enrollments.index') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Matrículas</a>
                <a href="{{ route('admin.dashboard') }}" class="block rounded-xl border border-white/20 px-4 py-3 text-center">Área Admin Atual</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 pb-28">
        <x-toast :status="session('status')" :errors="$errors->all()" />

        <div class="flex gap-6">
            <aside class="sticky top-20 hidden h-fit min-w-[240px] rounded-2xl bg-white p-4 shadow-card md:block">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Menu Super Admin</p>
                <ul class="space-y-2 text-sm font-semibold text-slate-700">
                    <li><a href="{{ route('sa.dashboard') }}" @class(['block rounded-lg px-3 py-2 hover:bg-edux-background', 'bg-edux-background text-edux-primary' => request()->routeIs('sa.dashboard')])>Dashboard</a></li>
                    <li><a href="{{ route('sa.tenants.index') }}" @class(['block rounded-lg px-3 py-2 hover:bg-edux-background', 'bg-edux-background text-edux-primary' => request()->routeIs('sa.tenants.*')])>Escolas</a></li>
                    <li><a href="{{ route('sa.users.index') }}" @class(['block rounded-lg px-3 py-2 hover:bg-edux-background', 'bg-edux-background text-edux-primary' => request()->routeIs('sa.users.*')])>Usuários</a></li>
                    <li><a href="{{ route('sa.courses.index') }}" @class(['block rounded-lg px-3 py-2 hover:bg-edux-background', 'bg-edux-background text-edux-primary' => request()->routeIs('sa.courses.*')])>Cursos</a></li>
                    <li><a href="{{ route('sa.enrollments.index') }}" @class(['block rounded-lg px-3 py-2 hover:bg-edux-background', 'bg-edux-background text-edux-primary' => request()->routeIs('sa.enrollments.*')])>Matrículas</a></li>
                </ul>

                <div class="mt-6 rounded-2xl border border-edux-line/60 bg-edux-background/70 p-4 text-sm text-slate-600">
                    <p class="font-semibold text-edux-primary">Escopo global</p>
                    <p class="mt-2">Esta área ignora o escopo de tenant e opera sobre todas as escolas.</p>
                    <a href="{{ route('admin.dashboard') }}" class="mt-4 inline-flex text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">
                        Abrir admin do tenant atual
                    </a>
                </div>
            </aside>

            <div class="min-w-0 flex-1 space-y-6">
                @yield('content')
            </div>
        </div>
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
