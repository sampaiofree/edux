@extends('layouts.app')

@section('content')
<div class="p-4 bg-[#F5F5F5] min-h-screen">

    {{-- Título da página de preview --}}
    <h1 class="text-2xl font-bold text-[#1A73E8] mb-4">
        Student UI — Componentes Visuais
    </h1>

    {{-- SEÇÃO: Cards da Home --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-3">Cards da Home</h2>

        <div class="grid grid-cols-2 gap-4">
            {{-- Card padrão --}}
            <div class="bg-white rounded-xl shadow p-4 flex flex-col items-center justify-center">
                <div class="text-4xl mb-2">🎓</div>
                <span class="text-sm font-semibold">Meus Cursos</span>
            </div>

            <div class="bg-white rounded-xl shadow p-4 flex flex-col items-center justify-center">
                <div class="text-4xl mb-2">📜</div>
                <span class="text-sm font-semibold">Certificados</span>
            </div>

            <div class="bg-white rounded-xl shadow p-4 flex flex-col items-center justify-center">
                <div class="text-4xl mb-2">🛒</div>
                <span class="text-sm font-semibold">+Cursos</span>
            </div>

            <div class="bg-white rounded-xl shadow p-4 flex flex-col items-center justify-center">
                <div class="text-4xl mb-2">🔔</div>
                <span class="text-sm font-semibold">Notificações</span>
            </div>

            <div class="bg-white rounded-xl shadow p-4 flex flex-col items-center justify-center col-span-2">
                <div class="text-4xl mb-2">💰</div>
                <span class="text-sm font-semibold">Meus Duxes</span>
            </div>
        </div>
    </div>

    {{-- SEÇÃO: Botões --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-3">Botões</h2>

        <div class="space-y-3">

            {{-- Botão principal --}}
            <button class="w-full bg-[#FBC02D] text-black font-bold py-3 rounded-xl shadow">
                👉 Continuar curso
            </button>

            {{-- Botão secundário --}}
            <button class="w-full border border-[#1A73E8] text-[#1A73E8] font-semibold py-3 rounded-xl">
                Ver mais
            </button>

            {{-- Botão outline preto --}}
            <button class="w-full border border-black text-black font-semibold py-3 rounded-xl">
                Ação extra
            </button>
        </div>
    </div>

    {{-- SEÇÃO: Lista de cursos --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-3">Lista de Cursos (cards)</h2>

        <div class="space-y-4">
            <div class="bg-white rounded-xl p-4 shadow flex gap-3">
                <div class="text-4xl">📘</div>
                <div class="flex-1">
                    <h3 class="font-bold text-lg">Auxiliar Administrativo</h3>
                    <p class="text-sm text-[#666]">12 aulas — 40% concluído</p>
                    <button class="mt-2 bg-[#FBC02D] text-black font-bold py-2 px-4 rounded-lg text-sm">
                        Continuar
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow flex gap-3">
                <div class="text-4xl">🐾</div>
                <div class="flex-1">
                    <h3 class="font-bold text-lg">Auxiliar Veterinário</h3>
                    <p class="text-sm text-[#666]">20 aulas — iniciar</p>
                    <button class="mt-2 bg-[#FBC02D] text-black font-bold py-2 px-4 rounded-lg text-sm">
                        Ver curso
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SEÇÃO: Navegação inferior --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-3">Navegação Inferior</h2>

        <div class="fixed bottom-4 left-0 right-0 mx-auto w-full max-w-sm bg-white rounded-2xl shadow flex justify-around py-3 border">
            <div class="flex flex-col items-center text-[#1A73E8]">
                <div class="text-2xl">🏠</div>
                <span class="text-xs">Home</span>
            </div>

            <div class="flex flex-col items-center text-[#666]">
                <div class="text-2xl">🎓</div>
                <span class="text-xs">Cursos</span>
            </div>

            <div class="flex flex-col items-center text-[#666]">
                <div class="text-2xl">🛒</div>
                <span class="text-xs"></span>
            </div>

            <div class="flex flex-col items-center text-[#666]">
                <div class="text-2xl">☰</div>
                <span class="text-xs">Mais</span>
            </div>
        </div>
    </div>

    <div class="h-20"></div> {{-- Espaço para não encostar no menu fixo --}}
</div>
@endsection
