@extends('layouts.auth')

@section('title', 'Entrar')

@section('content')
    @php
        $configuredSchoolName = trim((string) ($settings->escola_nome ?? ''));
        $brandName = $configuredSchoolName !== '' ? $configuredSchoolName : 'EduX';
        $brandLogoUrl = $settings->assetUrl('default_logo_path') ?: $settings->assetUrl('default_logo_dark_path');
    @endphp

    <div class="mx-auto w-full max-w-5xl space-y-5 sm:space-y-6" data-auth-login-page="1">
        <div class="flex flex-col items-center gap-3 text-center" data-auth-login-brand="1">
            @if ($brandLogoUrl)
                <img
                    src="{{ $brandLogoUrl }}"
                    alt="{{ $brandName }}"
                    class="h-10 w-auto max-w-[180px] object-contain sm:h-12"
                    data-auth-login-brand-logo="1"
                >
            @else
                <span
                    class="rounded-full border border-edux-line/70 bg-white/70 px-4 py-2 font-display text-lg tracking-[0.18em] text-edux-primary shadow-sm"
                    data-auth-login-brand-fallback="1"
                >
                    {{ $brandName }}
                </span>
            @endif

            <p class="text-xs font-semibold uppercase tracking-[0.26em] text-slate-500">
                Acesso seguro da plataforma
            </p>
        </div>

        @if (! $loginForceAppActive)
            <div
                class="mx-auto max-w-md rounded-[2rem] border border-white/70 bg-white/95 p-8 shadow-[0_24px_80px_rgba(15,23,42,0.10)] backdrop-blur"
                data-auth-login-shell="1"
            >
                @include('auth.partials.login-form')
            </div>
        @else
            <div
                class="mx-auto max-w-lg rounded-[2rem] border border-white/70 bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.08),_transparent_52%),linear-gradient(180deg,#ffffff,_#f8fafc)] p-6 shadow-[0_24px_80px_rgba(15,23,42,0.10)] backdrop-blur sm:p-7"
                data-auth-login-shell="1"
                data-login-force-app-root="1"
            >
                @include('auth.partials.force-app-loading-panel')

                @include('auth.partials.force-app-browser-panel', [
                    'forceAppTitle' => 'Baixe nosso aplicativo',
                    'forceAppDescription' => 'Para entrar na sua conta, use o aplicativo Portal JE. Baixe o app na loja do seu celular e faça login por lá.',
                    'forceAppLinks' => [
                        ['href' => route('signup.create'), 'label' => 'Ainda não tem conta? Criar conta'],
                    ],
                ])

                <section
                    class="hidden p-2 opacity-0 translate-y-3 pointer-events-none transition-all duration-300 ease-out motion-reduce:transform-none motion-reduce:transition-none sm:p-3"
                    data-login-force-app-form="1"
                    hidden
                >
                    @include('auth.partials.login-form', ['class' => 'max-w-none'])
                </section>

                <noscript>
                    <style>
                        [data-login-force-app-loading="1"],
                        [data-login-force-app-form="1"] {
                            display: none !important;
                        }

                        [data-login-force-app-browser="1"] {
                            display: block !important;
                        }
                    </style>
                </noscript>
            </div>
        @endif

        <div class="text-center text-xs font-medium text-slate-500" data-auth-login-legal="1">
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
                <a href="{{ route('legal.terms') }}" class="transition hover:text-edux-primary">Termos</a>
                <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:block"></span>
                <a href="{{ route('legal.privacy') }}" class="transition hover:text-edux-primary">Privacidade</a>
                <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:block"></span>
                <a href="{{ route('legal.support') }}" class="transition hover:text-edux-primary">Suporte</a>
            </div>
        </div>
    </div>
@endsection
