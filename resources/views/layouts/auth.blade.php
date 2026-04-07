<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $layoutSettings = $settings ?? \App\Models\SystemSetting::current();
        $faviconUrl = $layoutSettings->assetUrl('favicon_path');
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
<body class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.08),_transparent_38%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_45%,_#f8fafc_100%)] text-edux-text">
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute left-1/2 top-[-8rem] h-56 w-56 -translate-x-1/2 rounded-full bg-edux-primary/10 blur-3xl"></div>
        <div class="absolute bottom-[-6rem] right-[-4rem] h-48 w-48 rounded-full bg-sky-200/30 blur-3xl"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <x-toast :status="session('status')" :errors="$errors->all()" />
        @yield('content')
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
