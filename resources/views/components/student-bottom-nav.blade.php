@props([
    // A aba ativa (ex: 'cursos', 'vitrine', 'conta')
    'active' => 'cursos', 
    // Um booleano para mostrar um ponto de notificação
    'hasNotifications' => false 
])

@php
    // Itens principais que sempre estarão visíveis
    $primaryItems = [
        'cursos' => [
            'route' => route('dashboard', ['tab' => 'cursos']),
            'label' => 'Cursos',
            'icon_outline' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
            'icon_solid' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>',
        ],
        'conta' => [
            'route' => route('account.edit'),
            'label' => 'Perfil',
            'icon_outline' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
            'icon_solid' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        ],
        'notificacoes' => [
            'route' => route('learning.notifications.index'),
            'label' => 'Avisos',
            'icon_outline' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
            'icon_solid' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
        ],
        /*'vitrine' => [
            'route' => route('dashboard', ['tab' => 'vitrine']),
            'label' => '+Cursos',
            'icon_outline' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'icon_solid' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>',
        ],*/
    ];

    // Itens que aparecerão dentro do menu "Mais"
    $moreItems = [
        'conta' => [
            'route' => route('account.edit'), 
            'label' => 'Minha Conta',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
        ],
        'notificacoes' => [
            'route' => route('learning.notifications.index'), 
            'label' => 'Avisos',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>'
        ],
        'suporte' => [
            'route' => route('dashboard', ['tab' => 'suporte']), 
            'label' => 'Suporte',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.546-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        ],
    ];

    // Lógica para determinar se o botão "Mais" deve estar no estado ativo
    $moreActive = array_key_exists($active, $moreItems);
    if (request()->routeIs('account.edit') || request()->routeIs('learning.notifications.index')) {
        $moreActive = true;
    }
@endphp

<nav class="fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]"
     x-data="{ moreOpen: false }"
     @click.away="moreOpen = false">
    <div class="mx-auto w-full px-2">
        <div class="flex justify-around">
            <!-- Itens Principais -->
            @foreach ($primaryItems as $key => $item)
                @php $isActive = $key === $active; @endphp
                <a href="{{ $item['route'] }}"
                   @class([
                       'flex flex-col items-center justify-center text-center w-full py-2 transition-colors duration-200',
                       'text-blue-600' => $isActive,
                       'text-gray-500 hover:text-blue-600' => !$isActive,
                   ])>
                    {!! $isActive ? $item['icon_solid'] : $item['icon_outline'] !!}
                    <span class="text-xs font-semibold mt-1">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <!-- Botão "Mais" -->
            <button type="button"
                @click="moreOpen = !moreOpen"
                @class([
                    'flex flex-col items-center justify-center text-center w-full py-2 transition-colors duration-200',
                    'text-blue-600' => $moreActive,
                    'text-gray-500 hover:text-blue-600' => !$moreActive,
                ])>
                <div class="relative">
                    @if($moreActive)
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 9a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm1 4a1 1 0 100 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                    
                    @if($hasNotifications)
                        <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                    @endif
                </div>
                <span class="text-xs font-semibold mt-1">Mais</span>
            </button>
        </div>

        <!-- Painel "Mais" -->
        <div x-show="moreOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             x-cloak 
             class="absolute bottom-full left-2 right-2 mb-2 space-y-1 bg-white p-2 rounded-xl shadow-lg ring-1 ring-gray-200">
            @foreach ($moreItems as $key => $item)
                @php $isActive = $active === $key || (in_array($key, ['conta', 'notificacoes']) && request()->routeIs(str_replace('_', '.', $key).'*')); @endphp
                <a href="{{ $item['route'] }}"
                    @class([
                        'flex w-full items-center gap-3 px-3 py-2 text-sm font-semibold transition-colors rounded-lg',
                        'text-blue-700 bg-blue-50' => $isActive,
                        'text-gray-700 hover:bg-gray-50' => !$isActive,
                    ])>
                    {!! $item['icon'] !!}
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 px-3 py-2 text-sm font-semibold transition-colors rounded-lg text-red-600 hover:bg-red-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 11-6 0V7a3 3 0 016 0v1"/></svg>
                    <span>Sair</span>
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="pb-16"></div>
