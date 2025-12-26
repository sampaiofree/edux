@extends('layouts.public-lesson-screen')

@section('title', $lesson?->title ?? $course->title)

@section('content')
    @php
        $leadWhatsapp = $lead?->whatsapp;
    @endphp
    @once
        <script>
            window.publicLessonPage = function (isAuthenticated = false, initialWhatsapp = '') {
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                return {
                    showLessons: false,
                    showEnrollModal: false,
                    showLoginModal: false,
                    login: {
                        step: 1,
                        whatsapp: initialWhatsapp ?? '',
                        code: '',
                        message: '',
                        cooldown: 0,
                        timer: null,
                        canResend: true,
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
                            const digits = this.whatsapp.replace(/\D/g, '');
                            if (digits.length < 10 || digits.length > 11) {
                                this.message = 'Informe um WhatsApp válido.';
                                return;
                            }

                            this.whatsapp = digits;

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
                                body: JSON.stringify({
                                    whatsapp: this.whatsapp,
                                    code: this.code,
                                }),
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
                    openLogin() {
                        this.login.reset();
                        this.showLoginModal = true;
                    },
                    closeLogin() {
                        this.login.reset();
                        this.showLoginModal = false;
                    },
                };
            };
        </script>
    @endonce
    <div x-data="window.publicLessonPage(@json($isLeadAuthenticated), @json($leadWhatsapp))" class="min-h-screen bg-gray-50 pb-6">

        <div x-data="{ show:false, amount:0, timeout:null }"
             x-show="show"
             x-transition.opacity
             x-transition.duration.300ms
             @dux-earned.window="
                amount = $event.detail?.amount || 0;
                show = true;
                clearTimeout(timeout);
                timeout = setTimeout(() => show = false, 2500);
             "
             class="fixed right-4 top-4 z-50">
            <div class="flex items-center gap-3 rounded-xl bg-amber-100 px-4 py-3 shadow-lg border border-amber-200">
                <svg class="h-6 w-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2l2.39 4.84 5.34.78-3.86 3.76.91 5.32L10 14.77l-4.78 2.51.91-5.32L2.27 7.62l5.34-.78L10 2z"/>
                </svg>
                <div>
                    <p class="text-sm font-bold text-amber-800">🪙 Você ganhou <span x-text="amount"></span> DUX!</p>
                    <p class="text-xs text-amber-700">Parabéns por concluir a aula</p>
                </div>
            </div>
        </div>

        @unless ($certificateStage)
            <header class="sticky top-0 z-30 bg-white shadow-sm border-b">
            <div class="max-w-[420px] mx-auto w-full px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <button type="button"
                            @click="openLogin()"
                            class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700">
                        <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a3 3 0 11-6 0 3 3 0 016 0zM8 21v-1a4 4 0 014-4h0a4 4 0 014 4v1"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a4 4 0 00-4 4v2h8v-2a4 4 0 00-4-4z"/>
                        </svg>
                        <span x-text="login.whatsapp ? `Logado como ${login.whatsapp}` : 'Entrar'"></span>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col items-center">
                            <span class="text-2xl font-black text-blue-600">{{ $progressPercent }}%</span>
                            <span class="text-xs font-semibold text-gray-600">Progresso</span>
                        </div>
                        <button @click="showLessons = true"
                                class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold rounded-lg shadow-lg transition-all transform hover:scale-105 active:scale-95 whitespace-nowrap"
                                aria-label="Ver todas as aulas">
                            <svg class="w-6 h-6 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 4.5v15m6-15v15m-11-3.5h16M4.72 19.228A2.04 2.04 0 015.5 19h13a2.04 2.04 0 01.78.228m-14.667-11.667A2.04 2.04 0 015.5 5h13a2.04 2.04 0 01.78.228m0 0A2.001 2.001 0 0021 7v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                            <span class="text-sm font-bold">Ver aulas</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        @endunless

        <main class="max-w-[420px] mx-auto w-full px-4 py-4 space-y-4">
            @if (! $lesson)
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-600">
                    Conteúdo em preparação. Volte em breve.
                </div>
            @else
                @php
                    $certificateStageUrl = route('public.lessons.show', $course)
                        . '?lesson=' . $lesson->id . '&certificate_stage=1';
                @endphp
                <div class="relative bg-black rounded-xl overflow-hidden shadow-lg">
                    @if ($certificateStage)
                        <div class="aspect-video flex flex-col items-center justify-center gap-3 bg-white p-8 text-center">
                            <p class="text-lg font-bold text-blue-700">Parabéns! Você concluiu todas as aulas.</p>
                            <p class="text-sm text-blue-500">Clique no botão abaixo para solicitar o certificado.</p>
                            <button type="button"
                                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-full transition-all">
                                Solicitar certificado
                            </button>
                        </div>
                    @elseif ($youtubeId)
                        <div class="aspect-video">
                            <div class="plyr__video-embed" id="lesson-player">
                                <iframe
                                    src="https://www.youtube.com/embed/{{ $youtubeId }}?modestbranding=1&rel=0&enablejsapi=1"
                                    allowfullscreen
                                    allow="autoplay; encrypted-media">
                                </iframe>
                            </div>
                        </div>
                    @elseif ($lesson->video_url)
                        <div class="aspect-video">
                            <iframe
                                class="w-full h-full"
                                src="{{ $lesson->video_url }}"
                                allowfullscreen
                                loading="lazy">
                            </iframe>
                        </div>
                    @elseif ($lesson->content)
                        <div class="bg-white p-6 text-gray-800 leading-relaxed">
                            {!! nl2br(e($lesson->content)) !!}
                        </div>
                    @else
                        <div class="aspect-video flex items-center justify-center bg-gray-100 text-gray-600">
                            <div class="text-center p-6">
                                <svg class="w-16 h-16 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <p class="font-medium">Aula em breve</p>
                                <p class="text-sm mt-1">O conteúdo será liberado em breve</p>
                            </div>
                        </div>
                    @endif
                </div>

                @unless ($certificateStage)
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <div class="flex flex-col gap-1 mb-1 pb-1 border-b border-gray-50">
                            <div class="flex items-center gap-2">
                                
                                <p class="text-xs font-bold uppercase tracking-wider text-blue-600/80 font-inter">
                                    {{ $lesson->module->course->title }}
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-2">
                               
                                <p class="text-sm font-medium text-gray-500 font-inter" style="font-size: 0.7rem;">
                                    Módulo {{ $lesson->module->position }}: {{ $lesson->module->title }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
        
                            <div>
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-tight">Você está assistindo:</span>
                                <h2 class="text-xl font-bold text-gray-900 leading-tight font-poppins">
                                    {{ $lesson->title }}
                                </h2>
                            </div>
                        </div>
                    </div>

                    @unless ($isCompleted)
                        <button
                            type="button"
                            @click="showEnrollModal = true"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Marcar aula como concluída
                            </span>
                        </button>
                    @else
                        <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4 text-center">
                            <div class="flex items-center justify-center gap-2 text-green-700">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-bold text-lg">Aula concluída!</span>
                            </div>
                        </div>
                    @endunless
                @endunless

                @unless ($certificateStage)
                    <div class="grid grid-cols-2 gap-3">
                        @if ($previousLesson)
                            <a href="{{ route('public.lessons.show', $course) }}?lesson={{ $previousLesson->id }}"
                               class="flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-4 rounded-xl border-2 border-gray-200 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                <span>Anterior</span>
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if ($nextLesson)
                            <a href="{{ route('public.lessons.show', $course) }}?lesson={{ $nextLesson->id }}"
                               class="flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition-all">
                                <span>Próxima</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @else
                            <a href="{{ $certificateStageUrl }}"
                               class="flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition-all">
                                <span>Próxima</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                @endunless
            @endif
        </main>

        <div x-show="showLessons"
             x-transition.opacity
             @click="showLessons = false"
             class="fixed inset-0 z-50 bg-black/60 flex items-end justify-center p-0">
            <div @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 class="bg-white w-full max-w-[420px] rounded-t-3xl max-h-[90vh] flex flex-col shadow-2xl" style="padding-bottom: 60px;">

                <div class="flex items-center justify-between p-5 border-b bg-gray-50 rounded-t-3xl sticky top-0">
                    <h2 class="text-xl font-bold text-gray-900">Todas as aulas</h2>
                    <button @click="showLessons = false"
                            class="p-2 hover:bg-gray-200 rounded-lg transition-colors">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto p-4 space-y-3">
                    @foreach ($course->modules as $module)
                        <div x-data="{ open: {{ $module->id === $lesson?->module_id ? 'true' : 'false' }} }"
                             class="bg-white border-2 rounded-xl overflow-hidden"
                             :class="open ? 'border-blue-200' : 'border-gray-200'">

                            <button @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3 text-left">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                        <span class="font-bold text-blue-600">{{ $module->position }}</span>
                                    </div>
                                    <span class="font-semibold text-gray-900">{{ $module->title }}</span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 transition-transform"
                                     :class="open ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open"
                                 x-collapse
                                 class="border-t border-gray-200">
                                <ul class="divide-y divide-gray-100">
                                    @foreach ($module->lessons as $moduleLesson)
                                        @php
                                            $completed = in_array($moduleLesson->id, $completedLessonIds, true);
                                            $isActive = $moduleLesson->id === $lesson?->id;
                                        @endphp
                                        <li>
                                            <a href="{{ route('public.lessons.show', $course) }}?lesson={{ $moduleLesson->id }}"
                                               @class([
                                                   'flex items-center justify-between p-4 hover:bg-gray-50 transition-colors',
                                                   'bg-blue-50' => $isActive,
                                               ])>
                                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                                    <span class="text-sm font-medium text-gray-500 shrink-0">
                                                        {{ $moduleLesson->position }}
                                                    </span>
                                                    <span @class([
                                                        'text-sm truncate',
                                                        'font-bold text-blue-600' => $isActive,
                                                        'text-gray-700' => ! $isActive,
                                                    ])>
                                                        {{ $moduleLesson->title }}
                                                    </span>
                                                </div>
                                                @if ($completed)
                                                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                @elseif ($isActive)
                                                    <div class="w-2 h-2 bg-blue-500 rounded-full shrink-0"></div>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div x-cloak
             x-show="showLoginModal"
             x-transition.opacity
             @click="closeLogin()"
             class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
            <div @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="scale-95 opacity-0"
                 x-transition:enter-end="scale-100 opacity-100"
                 class="bg-white w-full max-w-md rounded-2xl shadow-2xl">
                <div class="p-6 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Entrar com WhatsApp</h3>
                            <p class="text-sm text-gray-600">Digite seu WhatsApp para confirmar sua vaga nas aulas.</p>
                        </div>
                        <button @click="closeLogin()"
                                class="p-2 hover:bg-gray-100 rounded-lg transition-colors shrink-0">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <template x-if="login.step === 1">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700">WhatsApp</label>
                                <div class="mt-2 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                                    <span class="text-sm font-semibold text-gray-600">+55</span>
                                    <input
                                        x-model="login.whatsapp"
                                        type="tel"
                                        maxlength="11"
                                        inputmode="numeric"
                                        @input="login.whatsapp = login.whatsapp.replace(/\D/g, '')"
                                        class="flex-1 bg-transparent text-sm text-gray-800 outline-none"
                                        placeholder="DDD + número"
                                    />
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="login.send()"
                                class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 transition">
                                Receber código
                            </button>
                        </div>
                    </template>

                    <template x-if="login.step === 2">
                        <div class="space-y-4">
                            <p class="text-sm text-gray-500">Enviamos um código de 4 dígitos para seu WhatsApp.</p>
                            <input
                                x-model="login.code"
                                type="tel"
                                maxlength="4"
                                inputmode="numeric"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center text-lg font-bold tracking-[0.4em]"
                                placeholder="0000"
                                @input="login.code = login.code.replace(/\D/g, '').slice(0, 4)"
                            />
                            <button
                                type="button"
                                @click="login.verify()"
                                class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 transition">
                                Confirmar e continuar
                            </button>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <button type="button" @click="login.step = 1" class="font-semibold text-blue-600">Editar número</button>
                                <button
                                    type="button"
                                    @click="login.resend()"
                                    :disabled="!login.canResend"
                                    class="font-semibold text-blue-600 disabled:text-gray-300">
                                    Reenviar código <span x-show="!login.canResend" x-text="`(${login.cooldown}s)`"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <p x-show="login.message"
                       class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700"
                       x-text="login.message">
                    </p>
                </div>
            </div>
        </div>

        <div x-cloak
             x-show="showEnrollModal"
             x-transition.opacity
             @click="showEnrollModal = false"
             class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
            <div @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="scale-95 opacity-0"
                 x-transition:enter-end="scale-100 opacity-100"
                 class="bg-white w-full max-w-md rounded-2xl shadow-2xl">
                <div class="p-6 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Você não está matriculado</h3>
                            <p class="text-sm text-gray-600">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                        </div>
                        <button @click="showEnrollModal = false"
                                class="p-2 hover:bg-gray-100 rounded-lg transition-colors shrink-0">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <a href="#"
                       class="w-full inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-all">
                        Matricular agora
                    </a>
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
        <script>
            (() => {
                const initPlayer = () => {
                    if (!window.Plyr) {
                        return;
                    }

                    const target = document.getElementById('lesson-player');
                    if (!target) {
                        return;
                    }

                    if (target._plyrInstance) {
                        target._plyrInstance.destroy();
                    }

                    target._plyrInstance = new Plyr(target, {
                        youtube: {
                            rel: 0,
                            modestbranding: 1,
                        },
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initPlayer, { once: true });
                } else {
                    initPlayer();
                }
            })();
        </script>
    @endif
@endpush
