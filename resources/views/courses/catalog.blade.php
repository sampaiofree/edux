@extends('layouts.student')

@section('title', 'Catalogo de cursos')

@section('content')
    <livewire:public-catalog context="catalog" />
@endsection
