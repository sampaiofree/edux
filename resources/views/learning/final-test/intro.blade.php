@extends('layouts.student')

@section('title', 'Teste final')

@section('content')
    @php
        $latestAttemptPassed = (bool) ($latestSubmittedAttempt?->passed ?? false);
        $showResultCard = $latestSubmittedAttempt !== null;
        $primaryActionLabel = $openAttempt
            ? 'Continuar prova'
            : ($showResultCard && ! $latestAttemptPassed ? 'Tentar novamente' : 'Iniciar prova');
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Teste final</p>
            <h1 class="font-display text-3xl text-edux-primary">{{ $course->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $finalTest->title }}</p>
        </header>

        @if ($showResultCard)
            <div @class([
                'rounded-card p-6 shadow-card',
                'bg-emerald-50 ring-1 ring-emerald-200' => $latestAttemptPassed && $canGenerateCertificate,
                'bg-amber-50 ring-1 ring-amber-200' => $latestAttemptPassed && ! $canGenerateCertificate,
                'bg-rose-50 ring-1 ring-rose-200' => ! $latestAttemptPassed,
            ])>
                <p @class([
                    'text-sm font-semibold uppercase tracking-wide',
                    'text-emerald-700' => $latestAttemptPassed && $canGenerateCertificate,
                    'text-amber-700' => $latestAttemptPassed && ! $canGenerateCertificate,
                    'text-rose-700' => ! $latestAttemptPassed,
                ])>
                    Resultado da prova
                </p>

                <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                    <div class="space-y-2">
                        <h2 class="font-display text-3xl text-slate-900">
                            @if ($latestAttemptPassed)
                                Você passou na prova!
                            @else
                                Você ainda não passou.
                            @endif
                        </h2>
                        <p class="text-lg font-semibold text-slate-800">Sua nota foi {{ $latestSubmittedAttempt->score }}%.</p>
                        <p class="max-w-2xl text-sm text-slate-600">
                            @if ($latestAttemptPassed && $canGenerateCertificate)
                                Seu certificado já pode ser emitido. Toque no botão abaixo para pegar seu certificado.
                            @elseif ($latestAttemptPassed)
                                Você passou na prova. Agora termine todas as aulas para liberar o certificado.
                            @elseif (($attemptsRemaining ?? 0) > 0 || $attemptsRemaining === null)
                                Revise com calma e tente novamente quando estiver pronto.
                            @else
                                Você usou todas as tentativas disponíveis. Se precisar, fale com o suporte da escola.
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if ($canGenerateCertificate && $certificateCreateUrl)
                            <a href="{{ $certificateCreateUrl }}" wire:navigate class="edux-btn">
                                Pegar meu certificado
                            </a>
                        @elseif (! $latestAttemptPassed && (($attemptsRemaining ?? 0) > 0 || $attemptsRemaining === null) && ! $openAttempt)
                            <form method="POST" action="{{ route('learning.courses.final-test.start', $course) }}">
                                @csrf
                                <button type="submit" class="edux-btn">Tentar novamente</button>
                            </form>
                        @endif

                        <a href="{{ route('dashboard') }}" wire:navigate class="edux-btn bg-white text-edux-primary">
                            Voltar
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-card bg-white p-6 shadow-card">
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-center justify-between gap-4">
                    <span>Nota mínima</span>
                    <strong class="text-slate-900">{{ $finalTest->passing_score }}%</strong>
                </li>
                <li class="flex items-center justify-between gap-4">
                    <span>Tentativas permitidas</span>
                    <strong class="text-slate-900">{{ $finalTest->max_attempts }}</strong>
                </li>
                <li class="flex items-center justify-between gap-4">
                    <span>Tentativas usadas</span>
                    <strong class="text-slate-900">{{ $attemptsCount }}</strong>
                </li>
                <li class="flex items-center justify-between gap-4">
                    <span>Duração</span>
                    <strong class="text-slate-900">{{ $finalTest->duration_minutes ? $finalTest->duration_minutes.' minutos' : 'Sem limite' }}</strong>
                </li>
                <li class="flex items-center justify-between gap-4">
                    <span>Questões</span>
                    <strong class="text-slate-900">{{ $finalTest->questions->count() }}</strong>
                </li>
            </ul>
        </div>

        @if ($finalTest->questions->isEmpty())
            <div class="rounded-card border border-rose-200 bg-rose-50 p-6 text-sm text-rose-700 shadow-card">
                Este teste ainda não possui questões disponíveis. Avise o administrador.
            </div>
        @elseif (! ($latestAttemptPassed && $canGenerateCertificate) && ! ($latestAttemptPassed && ! $isEligibleForCertificate))
            <div class="rounded-card bg-white p-6 shadow-card">
                <form method="POST" action="{{ route('learning.courses.final-test.start', $course) }}" class="flex flex-col gap-3">
                    @csrf
                    <button
                        type="submit"
                        class="edux-btn"
                        @disabled($attemptsCount >= $finalTest->max_attempts && ! $openAttempt)
                    >
                        {{ $primaryActionLabel }}
                    </button>
                    <p class="text-sm text-slate-500">
                        Leia cada pergunta com calma e marque apenas uma resposta por questão.
                    </p>
                </form>

                @if ($attemptsCount >= $finalTest->max_attempts && ! $openAttempt)
                    <p class="mt-3 text-sm font-semibold text-rose-600">Você atingiu o limite de tentativas.</p>
                @endif
            </div>
        @endif
    </section>
@endsection
