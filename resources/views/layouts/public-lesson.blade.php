<!DOCTYPE html>
<html lang="pt-BR" x-data>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduX')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <main class="mx-auto max-w-4xl px-4 py-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
