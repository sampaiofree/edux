@extends('layouts.student')

@section('title', 'Gerar certificado')

@section('content')
    <livewire:certificado.checkout
        :course-id="$prefilledCourseId"
        :completion-date="$prefilledCompletionDate"
        :completion-confirmed="$prefilledCompletionConfirmed"
    />
@endsection
