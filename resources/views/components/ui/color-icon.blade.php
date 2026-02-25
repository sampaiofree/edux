@props([
    'name',
    'tone' => 'blue',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'h-8 w-8',
        'lg' => 'h-11 w-11',
        default => 'h-10 w-10',
    };

    [$wrapperTone, $iconTone] = match ($tone) {
        'green' => ['bg-emerald-100 ring-1 ring-emerald-200', 'text-emerald-700'],
        'amber' => ['bg-amber-100 ring-1 ring-amber-200', 'text-amber-700'],
        'indigo' => ['bg-indigo-100 ring-1 ring-indigo-200', 'text-indigo-700'],
        'cyan' => ['bg-cyan-100 ring-1 ring-cyan-200', 'text-cyan-700'],
        'slate' => ['bg-slate-100 ring-1 ring-slate-200', 'text-slate-700'],
        default => ['bg-blue-100 ring-1 ring-blue-200', 'text-blue-700'],
    };
@endphp

<span
    data-lp-icon="{{ $name }}"
    {{ $attributes->class("inline-flex shrink-0 items-center justify-center rounded-xl {$sizeClasses} {$wrapperTone}") }}
    aria-hidden="true"
>
    <svg class="h-5 w-5 {{ $iconTone }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        @switch($name)
            @case('clock')
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 7v5l3 2"></path>
                @break

            @case('play-circle')
                <circle cx="12" cy="12" r="9"></circle>
                <path d="m10 9 5 3-5 3V9Z"></path>
                @break

            @case('badge-check')
                <path d="M12 3.5 14.4 5l3-.2 1.3 2.7 2.4 1.7-.8 3 .8 3-2.4 1.7-1.3 2.7-3-.2-2.4 1.5-2.4-1.5-3 .2-1.3-2.7-2.4-1.7.8-3-.8-3L5.6 7.5 6.9 4.8l3 .2L12 3.5Z"></path>
                <path d="m9.4 12.2 1.8 1.8 3.4-3.5"></path>
                @break

            @case('file-text')
                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path>
                <path d="M14 3v5h5"></path>
                <path d="M9 13h6"></path>
                <path d="M9 17h4"></path>
                @break

            @case('sparkles')
                <path d="m12 3 1.2 3.3L16.5 7.5l-3.3 1.2L12 12l-1.2-3.3L7.5 7.5l3.3-1.2L12 3Z"></path>
                <path d="m18.5 13 0.7 1.8 1.8 0.7-1.8 0.7-0.7 1.8-0.7-1.8-1.8-0.7 1.8-0.7 0.7-1.8Z"></path>
                <path d="m6 14 1 2.6L9.6 18 7 19l-1 2.6L5 19l-2.6-1L5 16.6 6 14Z"></path>
                @break

            @case('briefcase')
                <rect x="3" y="7" width="18" height="12" rx="2"></rect>
                <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                <path d="M3 12h18"></path>
                @break

            @case('smartphone')
                <rect x="7" y="2.5" width="10" height="19" rx="2.2"></rect>
                <path d="M10 5.5h4"></path>
                <path d="M12 18.5h.01"></path>
                @break

            @case('wallet')
                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18a2 2 0 0 1 2 2v1H8a2 2 0 0 0 0 4h12v5a2 2 0 0 1-2 2H6.5A2.5 2.5 0 0 1 4 16.5Z"></path>
                <path d="M20 8.5h-12a1.5 1.5 0 0 0 0 3h12Z"></path>
                <circle cx="16.5" cy="10" r=".7" fill="currentColor" stroke="none"></circle>
                @break

            @case('list-check')
                <path d="M9 6h11"></path>
                <path d="M9 12h11"></path>
                <path d="M9 18h11"></path>
                <path d="m3.5 6.3 1.2 1.2 2.1-2.1"></path>
                <path d="m3.5 12.3 1.2 1.2 2.1-2.1"></path>
                <path d="m3.5 18.3 1.2 1.2 2.1-2.1"></path>
                @break

            @case('shield-check')
                <path d="M12 3 5 6v5c0 5 3.2 8.4 7 10 3.8-1.6 7-5 7-10V6l-7-3Z"></path>
                <path d="m9.4 11.9 1.7 1.7 3.6-3.7"></path>
                @break

            @case('book-open')
                <path d="M12 7c-1.7-1.4-4.4-2-7-2v13c2.6 0 5.3.6 7 2"></path>
                <path d="M12 7c1.7-1.4 4.4-2 7-2v13c-2.6 0-5.3.6-7 2"></path>
                <path d="M12 7v13"></path>
                @break

            @case('map-pin')
                <path d="M12 21s-6-5.4-6-11a6 6 0 1 1 12 0c0 5.6-6 11-6 11Z"></path>
                <circle cx="12" cy="10" r="2.2"></circle>
                @break

            @case('megaphone')
                <path d="M3 11v2"></path>
                <path d="M5 10h2l8-4v12l-8-4H5Z"></path>
                <path d="m7 14 1.4 5"></path>
                <path d="M18 9a4 4 0 0 1 0 6"></path>
                <path d="M20 7a7 7 0 0 1 0 10"></path>
                @break

            @case('users')
                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                <circle cx="9.5" cy="8" r="3"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.9"></path>
                <path d="M16.5 5.2a3 3 0 1 1 0 5.6"></path>
                @break

            @case('building')
                <path d="M4 21V5a2 2 0 0 1 2-2h8v18"></path>
                <path d="M14 21V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v12"></path>
                <path d="M8 7h2"></path>
                <path d="M8 11h2"></path>
                <path d="M8 15h2"></path>
                <path d="M17 12h1"></path>
                <path d="M17 16h1"></path>
                @break

            @case('hourglass')
                <path d="M7 3h10"></path>
                <path d="M7 21h10"></path>
                <path d="M8 3c0 4 4 4.5 4 9s-4 5-4 9"></path>
                <path d="M16 3c0 4-4 4.5-4 9s4 5 4 9"></path>
                @break

            @case('check-circle')
                <circle cx="12" cy="12" r="9"></circle>
                <path d="m8.8 12.2 2.2 2.2 4.3-4.4"></path>
                @break

            @case('info')
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 10v5"></path>
                <path d="M12 7h.01"></path>
                @break

            @default
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 8v4"></path>
                <path d="M12 16h.01"></path>
        @endswitch
    </svg>
</span>
