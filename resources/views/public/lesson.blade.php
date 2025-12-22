@extends('layouts.public-lesson')

@section('title', $course->title)

@section('content')
    <div class="space-y-4" x-data="lessonPublic({{ $isAuthenticated ? 'true' : 'false' }})" x-init="init()">
        <header class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">{{ $course->title }}</p>
                <h1 class="text-lg font-bold text-gray-900 truncate">{{ $lesson?->title ?? 'Aulas' }}</h1>
            </div>
            @if ($lesson && $lesson->id === $firstLessonId)
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                    🟢 Aula gratuita
                </span>
            @endif
        </header>

        @if (! $lesson)
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-600">
                Conteúdo em preparação. Volte em breve.
            </div>
        @else
            @if ($showContinueMessage)
                <p class="text-xs font-semibold text-gray-500">Continuando de onde você parou</p>
            @endif

            <div class="relative overflow-hidden rounded-xl bg-black shadow-lg">
                @if ($youtubeId)
                    <div class="aspect-video">
                        <div class="plyr__video-embed" id="lesson-player">
                            <iframe
                                src="https://www.youtube.com/embed/{{ $youtubeId }}?modestbranding=1&rel=0&enablejsapi=1"
                                allowfullscreen
                                allow="autoplay; encrypted-media">
                            </iframe>
                        </div>
                    </div>
                @else
                    <div class="aspect-video flex items-center justify-center bg-gray-100 text-gray-600">
                        <div class="text-center p-6">
                            <p class="font-medium">Vídeo indisponível</p>
                            <p class="text-sm mt-1">Confira se o link do YouTube está correto.</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span>📝 Aula {{ $lesson->position }}</span>
                    <span>📚 Módulo {{ $lesson->module->position }}</span>
                </div>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ $lesson->title }}</p>
            </div>

            <form method="POST" action="{{ route('public.lessons.complete', [$course, $lesson]) }}" data-requires-auth="true" class="w-full">
                @csrf
                @unless ($isCompleted)
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-green-500 px-6 py-4 font-bold text-white shadow-md transition-all hover:bg-green-600">
                        Marcar aula como concluída
                    </button>
                @else
                    <div class="rounded-xl border-2 border-green-200 bg-green-50 p-4 text-center text-green-700">
                        <span class="font-bold">Aula concluída!</span>
                    </div>
                @endunless
            </form>

            <div class="grid grid-cols-2 gap-3">
                @if ($previousLesson)
                    <a
                        href="{{ route('public.lessons.show', $course) }}?lesson={{ $previousLesson->id }}"
                        class="flex items-center justify-center gap-2 rounded-xl border-2 border-gray-200 bg-white py-3 px-4 font-semibold text-gray-700 transition-all hover:bg-gray-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Anterior</span>
                    </a>
                @else
                    <div></div>
                @endif

                @if ($nextLesson)
                    <a
                        href="{{ route('public.lessons.show', $course) }}?lesson={{ $nextLesson->id }}"
                        data-requires-auth="true"
                        class="flex items-center justify-center gap-2 rounded-xl bg-blue-500 py-3 px-4 font-semibold text-white shadow-md transition-all hover:bg-blue-600">
                        <span>Próxima</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @else
                    <div></div>
                @endif
            </div>
        @endif

        @if ($lesson)
            <section class="space-y-3">
                @foreach ($course->modules as $module)
                    <div class="overflow-hidden rounded-xl border-2 border-gray-200 bg-white">
                        <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-sm font-bold text-blue-600">
                                {{ $module->position }}
                            </span>
                            <span class="font-semibold text-gray-800">{{ $module->title }}</span>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($module->lessons as $moduleLesson)
                                @php
                                    $isActive = $lesson->id === $moduleLesson->id;
                                    $isFree = $moduleLesson->id === $firstLessonId;
                                    $isCompletedLesson = in_array($moduleLesson->id, $completedLessonIds, true);
                                @endphp
                                <li>
                                    <a
                                        href="{{ route('public.lessons.show', $course) }}?lesson={{ $moduleLesson->id }}"
                                        @if (! $isFree)
                                            data-requires-auth="true"
                                        @endif
                                        @class([
                                            'flex items-center justify-between px-4 py-3 transition-all',
                                            'bg-blue-50' => $isActive,
                                            'hover:bg-gray-50' => ! $isActive,
                                        ])>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-xs font-semibold text-gray-500">{{ $moduleLesson->position }}</span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold {{ $isActive ? 'text-blue-600' : 'text-gray-800' }}">
                                                    {{ $moduleLesson->title }}
                                                </p>
                                                @if ($isFree)
                                                    <p class="text-xs text-green-600 font-semibold">🟢 Aula gratuita</p>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($isCompletedLesson)
                                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @elseif ($isActive)
                                            <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                        @elseif (! $isFree && ! $isAuthenticated)
                                            <span class="text-xs font-semibold text-gray-400">🔒</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">🎓</span>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-yellow-800">Certificado disponível para este curso</p>
                            <p class="text-xs text-yellow-700">Garanta seu certificado ao concluir todas as aulas.</p>
                        </div>
                        <a href="#" class="text-sm font-semibold text-yellow-900 underline">Saiba mais</a>
                    </div>
                </div>
            </section>
        @endif

        <div x-cloak x-show="auth.open" class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-0 sm:items-center sm:p-6">
            <div
                @click.outside="auth.close()"
                class="w-full rounded-t-3xl bg-white shadow-2xl sm:max-w-md sm:rounded-2xl">
                <div class="border-b border-gray-100 p-4">
                    <h2 class="text-lg font-bold text-gray-900">Entrar com WhatsApp</h2>
                </div>

                <div class="space-y-4 p-5">
                    <template x-if="auth.step === 1">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700">WhatsApp</label>
                                <div class="mt-2 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                                    <span class="text-sm font-semibold text-gray-600">+55</span>
                                    <input
                                        x-model="auth.whatsapp"
                                        type="tel"
                                        inputmode="numeric"
                                        placeholder="DDD + número"
                                        class="flex-1 bg-transparent text-sm text-gray-800 outline-none"
                                        @input="auth.whatsapp = auth.whatsapp.replace(/\D/g, '')"
                                    />
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="auth.send()"
                                class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                Receber código
                            </button>

                            <p class="text-xs text-gray-500">
                                Ao continuar, você concorda com nossos
                                <a href="{{ route('legal.terms') }}" class="font-semibold text-blue-600 underline">termos</a>
                                e
                                <a href="{{ route('legal.privacy') }}" class="font-semibold text-blue-600 underline">política de privacidade</a>.
                            </p>
                        </div>
                    </template>

                    <template x-if="auth.step === 2">
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600">Enviamos um código para seu WhatsApp</p>
                            <input
                                x-model="auth.code"
                                type="tel"
                                inputmode="numeric"
                                maxlength="4"
                                placeholder="0000"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center text-lg font-bold tracking-[0.4em] text-gray-800"
                                @input="auth.code = auth.code.replace(/\D/g, '').slice(0, 4)"
                            />

                            <button
                                type="button"
                                @click="auth.verify()"
                                class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                Confirmar e continuar
                            </button>

                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <button type="button" @click="auth.reset()" class="font-semibold text-blue-600">Editar número</button>
                                <button
                                    type="button"
                                    @click="auth.resend()"
                                    :disabled="!auth.canResend"
                                    class="font-semibold text-blue-600 disabled:text-gray-400">
                                    Reenviar código <span x-show="!auth.canResend" x-text="`(${auth.cooldown}s)`"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="auth.message">
                        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700" x-text="auth.message"></p>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @if ($youtubeId)
        <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    @endif
@endpush

@push('scripts')
    @if ($youtubeId)
        <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    @endif
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const lessonPublic = (isAuthenticated) => ({
            auth: {
                open: false,
                step: 1,
                whatsapp: '',
                code: '',
                message: '',
                cooldown: 0,
                canResend: true,
                timer: null,
                openModal() {
                    this.open = true;
                },
                close() {
                    this.open = false;
                },
                reset() {
                    this.step = 1;
                    this.code = '';
                    this.message = '';
                },
                startCooldown(seconds) {
                    this.cooldown = seconds;
                    this.canResend = false;
                    clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        this.cooldown -= 1;
                        if (this.cooldown <= 0) {
                            this.canResend = true;
                            clearInterval(this.timer);
                        }
                    }, 1000);
                },
                async send() {
                    if (!this.whatsapp) {
                        this.message = 'Informe seu WhatsApp.';
                        return;
                    }

                    const response = await fetch("{{ route('public.lessons.otp.send') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ whatsapp: this.whatsapp }),
                    });

                    const data = await response.json();
                    this.message = data.message || '';

                    if (data.status === 'sent') {
                        this.step = 2;
                        this.startCooldown(data.retry_in ?? 30);
                    }

                    if (data.status === 'locked') {
                        this.step = 1;
                    }
                },
                async verify() {
                    if (this.code.length !== 4) {
                        this.message = 'Digite o código de 4 dígitos.';
                        return;
                    }

                    const response = await fetch("{{ route('public.lessons.otp.verify') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ whatsapp: this.whatsapp, code: this.code }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.message = data.message || 'Código inválido.';
                        if (data.status === 'locked') {
                            this.step = 1;
                        }
                        return;
                    }

                    this.message = '';
                    window.location.reload();
                },
                resend() {
                    if (this.canResend) {
                        this.send();
                    }
                },
            },
            init() {
                if (window.Plyr && document.getElementById('lesson-player')) {
                    new Plyr('#lesson-player', {
                        youtube: {
                            rel: 0,
                            modestbranding: 1,
                        },
                    });
                }

                document.querySelectorAll('[data-requires-auth]').forEach((element) => {
                    element.addEventListener('click', (event) => {
                        if (!isAuthenticated) {
                            event.preventDefault();
                            event.stopPropagation();
                            this.auth.openModal();
                        }
                    });
                });

                if (!isAuthenticated) {
                    document.querySelectorAll('form[data-requires-auth]').forEach((form) => {
                        form.addEventListener('submit', (event) => {
                            event.preventDefault();
                            this.auth.openModal();
                        });
                    });
                }
            },
        });
    </script>
@endpush
