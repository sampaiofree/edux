{{-- resources/views/livewire/student/lesson-screen.blade.php --}}
<div x-data="{ 
    showLessons: false, 
    showPayment: @entangle('showPaymentModal'),
    videoReady: false 
}" class="min-h-screen bg-gray-50 pb-6">

    {{-- Header compacto e informativo --}}
    <header class="sticky top-0 z-30 bg-white shadow-sm border-b">
        <div class="max-w-[420px] mx-auto w-full px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500 truncate">{{ $course->title }}</p>
                    <h1 class="text-lg font-bold text-gray-900 truncate">{{ $lesson->title }}</h1>
                </div>
                <div class="flex items-center gap-3 shrink-0">
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

    <main class="max-w-[420px] mx-auto w-full px-4 py-4 space-y-4">
        
        {{-- Player de vídeo otimizado (com Plyr) --}}
        <div class="relative bg-black rounded-xl overflow-hidden shadow-lg">
            @if ($this->youtubeId)
                <div class="aspect-video">
                    <div class="plyr__video-embed" id="lesson-player">
                        <iframe 
                            src="https://www.youtube.com/embed/{{ $this->youtubeId }}?modestbranding=1&rel=0&enablejsapi=1" 
                            allowfullscreen 
                            allow="autoplay; encrypted-media">
                        </iframe>
                    </div>
                </div>
            @elseif ($lesson->video_url)
                <div class="aspect-video">
                    {{-- Para vídeos que não são YouTube, podemos ter um iframe direto ou configurar o Plyr para aceitar outras URLs --}}
                    {{-- Por simplicidade, vou manter o iframe direto aqui, mas Plyr pode ser configurado para ele também --}}
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

        {{-- Alertas --}}
        @if ($statusMessage)
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-800 font-medium">{{ $statusMessage }}</p>
                </div>
            </div>
        @endif

        @if ($errorMessage)
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-800 font-medium">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        {{-- Informações da aula --}}
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
                <span>📚 Módulo {{ $lesson->module->position }}</span>
                <span>📝 Aula {{ $lesson->position }}</span>
            </div>
            <p class="text-base font-medium text-gray-900">{{ $lesson->title }}</p>
        </div>

        {{-- Ação principal: Marcar como concluída --}}
        @unless ($isCompleted)
            <button 
                type="button" 
                wire:click="completeLesson" 
                wire:loading.attr="disabled"
                class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="completeLesson" class="flex items-center justify-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Marcar aula como concluída
                </span>
                <span wire:loading wire:target="completeLesson" class="inline-flex items-center justify-center gap-2 whitespace-nowrap align-middle">
                    <svg class="animate-spin h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Salvando...
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

        {{-- Navegação entre aulas --}}
        <div class="grid grid-cols-2 gap-3">
            @if ($previousLesson)
                <a href="{{ route('learning.courses.lessons.show', [$course, $previousLesson]) }}" wire:navigate
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
                <a href="{{ route('learning.courses.lessons.show', [$course, $nextLesson]) }}" wire:navigate
                   class="flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition-all">
                    <span>Próxima</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <div></div>
            @endif
        </div>

        {{-- Ações secundárias --}}
        <div class="space-y-3">
            {{-- Teste final --}}
            @if ($course->finalTest && $progressPercent >= 80)
                <a href="{{ route('learning.courses.final-test.intro', $course) }}" 
                   class="flex items-center justify-between bg-purple-50 hover:bg-purple-100 border-2 border-purple-200 text-purple-700 font-semibold py-4 px-5 rounded-xl transition-all">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Fazer teste final</span>
                    </div>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endif

            {{-- Certificado --}}
            @if ($hasPaidCertificate && $progressPercent >= 100)
                <button 
                    type="button"
                    wire:click="requestCertificate" 
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-between bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold py-4 px-5 rounded-xl shadow-md transition-all disabled:opacity-50">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <span wire:loading.remove wire:target="requestCertificate">Pegar meu certificado</span>
                        <span wire:loading wire:target="requestCertificate">Gerando...</span>
                    </div>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                @if ($canRename)
                    <p class="text-center text-sm text-gray-600">
                        Nome errado? 
                        <a href="{{ route('account.edit') }}" class="font-semibold text-blue-600 underline">
                            Corrigir aqui
                        </a>
                    </p>
                @endif
            @elseif (!$hasPaidCertificate && $progressPercent >= 100)
                <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-yellow-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1">
                            <p class="font-bold text-yellow-800 mb-1">Certificado disponível!</p>
                            <p class="text-sm text-yellow-700 mb-3">
                                Você concluiu o curso! Clique no botão abaixo para gerar seu certificado.
                            </p>
                            <a href="{{ route('certificado.index') }}"                                
                                class="inline-flex items-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-4 py-2 rounded-lg transition-colors">
                                Gerar certificado
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Voltar ao início --}}
        <!--<a href="{{ route('dashboard') }}" 
           class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Voltar para meus cursos</span>
        </a>-->
    </main>

    {{-- Modal: Lista de aulas (melhorado) --}}
    <div x-show="showLessons" 
         x-transition.opacity
         @click="showLessons = false"
         class="fixed inset-0 z-50 bg-black/60 flex items-end justify-center p-0">
        <div @click.stop 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             class="bg-white w-full max-w-[420px] rounded-t-3xl max-h-[90vh] flex flex-col shadow-2xl" style="padding-bottom: 60px;">
            
            {{-- Header do modal --}}
            <div class="flex items-center justify-between p-5 border-b bg-gray-50 rounded-t-3xl sticky top-0">
                <h2 class="text-xl font-bold text-gray-900">Todas as aulas</h2>
                <button @click="showLessons = false" 
                        class="p-2 hover:bg-gray-200 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Lista de módulos e aulas --}}
            <div class="overflow-y-auto p-4 space-y-3">
                @foreach ($course->modules as $module)
                    <div x-data="{ open: {{ $module->id === $lesson->module_id ? 'true' : 'false' }} }" 
                         class="bg-white border-2 rounded-xl overflow-hidden"
                         :class="open ? 'border-blue-200' : 'border-gray-200'">
                        
                        {{-- Cabeçalho do módulo --}}
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

                        {{-- Lista de aulas do módulo --}}
                        <div x-show="open" 
                             x-collapse
                             class="border-t border-gray-200">
                            <ul class="divide-y divide-gray-100">
                                @foreach ($module->lessons as $moduleLesson)
                                    @php
                                        $completed = in_array($moduleLesson->id, $completedLessonIds, true);
                                        $isActive = $moduleLesson->id === $lesson->id;
                                    @endphp
                                    <li>
                                    <a href="{{ route('learning.courses.lessons.show', [$course, $moduleLesson]) }}" wire:navigate
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
                                                    'text-gray-700' => !$isActive,
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

    {{-- Modal: Pagamento do certificado --}}
    <div x-show="showPayment" 
         x-transition.opacity
         @click="showPayment = false"
         class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.stop 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             class="bg-white w-full max-w-md rounded-2xl shadow-2xl">
            
            <div class="p-6 space-y-4">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            <h3 class="text-xl font-bold text-gray-900">Certificado</h3>
                        </div>
                        <p class="text-sm text-gray-600">Finalize o pagamento para liberar</p>
                    </div>
                    <button @click="showPayment = false" 
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Conteúdo do modal --}}
                <p class="text-sm text-gray-700">
                    Selecione a carga horária desejada para o certificado.
                </p>

                @php
                    $checkouts = $course->checkouts;
                @endphp

                @if ($checkouts->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($checkouts as $checkout)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3">
                                <div>
                                    <p class="text-base font-semibold text-gray-900">{{ $checkout->hours }}h</p>
                                    <p class="text-sm text-gray-500">
                                        R$ {{ number_format($checkout->price, 2, ',', '.') }}
                                    </p>
                                </div>
                                <a href="{{ $checkout->checkout_url }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-center bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                                    Ir para pagamento
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">
                        Nenhuma opção disponível no momento.
                    </p>
                @endif

                <button type="button" 
                        @click="showPayment = false"
                        class="w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-xl transition-all">
                    Entendi
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
    @if ($this->youtubeId) {{-- Apenas carrega o CSS do Plyr se for um vídeo do YouTube --}}
        <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    @endif
@endpush

@push('scripts')
    @if ($this->youtubeId) {{-- Apenas carrega o JS do Plyr se for um video do YouTube --}}
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

                const queueInit = () => requestAnimationFrame(initPlayer);

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', queueInit, { once: true });
                } else {
                    queueInit();
                }

                window.addEventListener('livewire:navigated', () => {
                    setTimeout(initPlayer, 50);
                });
            })();
        </script>
    @endif
@endpush
