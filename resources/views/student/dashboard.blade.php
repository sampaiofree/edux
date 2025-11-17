<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard do aluno</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F5F5] min-h-screen font-sans">
    <main class="mx-auto max-w-md p-4 pb-28">
        <section class="grid grid-cols-2 min-[380px]:grid-cols-3 gap-4">
            <a href="{{ route('design.student.courses') }}" class="group">
                <div class="bg-white rounded-2xl shadow p-4 flex flex-col items-center justify-center aspect-square min-h-[118px]">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-[#E8F1FD] text-3xl">
                        🎓
                    </div>
                    <span class="mt-3 font-bold text-sm text-[#333] text-center">Meus cursos</span>
                </div>
            </a>

            <a href="#" class="group">
                <div class="bg-white rounded-2xl shadow p-4 flex flex-col items-center justify-center aspect-square min-h-[118px]">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-[#FFF6D6] text-3xl">
                        📜
                    </div>
                    <span class="mt-3 font-bold text-sm text-[#333] text-center">Certificados</span>
                </div>
            </a>

            <a href="#" class="group">
                <div class="bg-white rounded-2xl shadow p-4 flex flex-col items-center justify-center aspect-square min-h-[118px]">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-[#E9F9EC] text-3xl">
                        💰
                    </div>
                    <span class="mt-3 font-bold text-sm text-[#333] text-center">Duxes</span>
                </div>
            </a>

            <a href="#" class="group">
                <div class="bg-white rounded-2xl shadow p-4 flex flex-col items-center justify-center aspect-square min-h-[118px]">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-[#F3E9FD] text-3xl">
                        🔔
                    </div>
                    <span class="mt-3 font-bold text-sm text-[#333] text-center">Notificações</span>
                </div>
            </a>

            <a href="#" class="group">
                <div class="bg-white rounded-2xl shadow p-4 flex flex-col items-center justify-center aspect-square min-h-[118px]">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-[#F0F0F0] text-3xl">
                        🛒
                    </div>
                    <span class="mt-3 font-bold text-sm text-[#333] text-center">+Cursos</span>
                </div>
            </a>
        </section>
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-50 pb-4">
        <div class="mx-auto max-w-md px-4">
            <div class="bg-white rounded-2xl shadow border flex items-center justify-between px-6 py-3">
                <a href="{{ route('design.student.dashboard') }}" class="flex flex-col items-center gap-1 text-[#1A73E8]">
                    <span class="text-2xl">🏠</span>
                    <span class="text-xs font-semibold">Home</span>
                </a>
                <a href="{{ route('design.student.courses') }}" class="flex flex-col items-center gap-1 text-[#555]">
                    <span class="text-2xl">🎓</span>
                    <span class="text-xs font-semibold">Cursos</span>
                </a>
                <button type="button" class="flex flex-col items-center gap-1 text-[#555]">
                    <span class="text-2xl">💰</span>
                    <span class="text-xs font-semibold">Duxes</span>
                </button>
                <button type="button" class="flex flex-col items-center gap-1 text-[#555]">
                    <span class="text-2xl">☰</span>
                    <span class="text-xs font-semibold">Mais</span>
                </button>
            </div>
        </div>
    </nav>
</body>
</html>
