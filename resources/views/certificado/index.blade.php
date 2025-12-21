<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar certificado</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Poppins:wght@700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/edux-base.css'])
    @livewireStyles
</head>
<body class="bg-[#F5F5F5]">
    <div class="min-h-screen flex justify-center items-start py-6">
        <livewire:certificado.checkout :course="$course" />
    </div>

    @livewireScripts
</body>
</html>
