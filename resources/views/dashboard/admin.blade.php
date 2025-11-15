@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-10">
        <livewire:admin.dashboard />
        <livewire:admin.notifications-manager />
    </div>
@endsection
