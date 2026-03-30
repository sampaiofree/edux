@extends('layouts.student')

@section('title', 'Teste final')

@section('content')
    <h1 style="margin-top:0;">Teste final · {{ $course->title }}</h1>
    <p style="margin-top:0; color:#475569;">{{ $finalTest->title }}</p>

    <div class="card" style="margin-bottom:1rem;">
        <ul style="margin:0; padding-left:1.25rem; line-height:1.6;">
            <li>Nota mínima: {{ $finalTest->passing_score }}%</li>
            <li>Tentativas permitidas: {{ $finalTest->max_attempts }}</li>
            <li>Duração: {{ $finalTest->duration_minutes ? $finalTest->duration_minutes.' minutos' : 'sem limite' }}</li>
            <li>Questões cadastradas: {{ $finalTest->questions->count() }}</li>
        </ul>
    </div>

    @if ($finalTest->questions->isEmpty())
        <div class="card" style="background:#fee2e2; color:#b91c1c;">
            Este teste ainda não possui questões disponíveis. Avise o administrador.
        </div>
    @else
        <form method="POST" action="{{ route('learning.courses.final-test.start', $course) }}" style="display:flex; flex-direction:column; gap:0.75rem;">
            @csrf
            <button type="submit" class="btn btn-primary" @disabled($attemptsCount >= $finalTest->max_attempts)>{{ $openAttempt ? 'Continuar tentativa' : 'Iniciar tentativa' }}</button>
        </form>

        @if ($attemptsCount >= $finalTest->max_attempts)
            <p style="color:#b91c1c; margin-top:0.5rem;">Você atingiu o limite de tentativas.</p>
        @endif
    @endif

    <div style="margin-top:1.5rem;">
        <a href="{{ route('dashboard') }}" wire:navigate class="btn btn-secondary">Voltar</a>
    </div>
@endsection
