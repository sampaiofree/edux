<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $settings = \App\Models\SystemSetting::current();
        $faviconUrl = $settings->assetUrl('favicon_path');
        $schoolName = trim((string) ($settings->escola_nome ?? '')) ?: 'EduX';
    @endphp
    <title>@yield('title', $schoolName)</title>
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/home-w3.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body>
    <main>
        @yield('content')
    </main>

    @livewireScripts
    @include('partials.tracking.first-party')
    @stack('scripts')
</body>
</html>
