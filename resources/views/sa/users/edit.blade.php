@extends('layouts.sa')

@section('title', 'Super Admin | Editar usuário')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Usuário global</p>
                <h1 class="font-display text-3xl text-edux-primary">{{ $user->preferredName() }}</h1>
                <p class="text-sm text-slate-600">Edite dados do usuário e, se necessário, mova a conta entre escolas sem quebrar vínculos.</p>
            </div>
            <a href="{{ route('sa.users.index') }}" class="edux-btn bg-white text-edux-primary">Voltar para a lista</a>
        </header>

        <form method="POST" action="{{ route('sa.users.update', $user->id) }}" enctype="multipart/form-data" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Nome completo</span>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>E-mail</span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Escola / tenant</span>
                    <select name="system_setting_id" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) old('system_setting_id', $user->system_setting_id) === (string) $tenant->id)>
                                {{ $tenantLabel($tenant) }} — ID #{{ $tenant->id }}
                            </option>
                        @endforeach
                    </select>
                    @error('system_setting_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>WhatsApp</span>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('whatsapp') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Foto de perfil</span>
                    <input type="file" name="profile_photo" accept="image/*" class="w-full rounded-xl border border-dashed border-edux-line px-4 py-3 file:mr-3 file:rounded-lg file:border-none file:bg-edux-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                    @error('profile_photo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    @if ($user->profilePhotoUrl())
                        <div class="flex items-center gap-3 pt-2 text-xs text-slate-500">
                            <img src="{{ $user->profilePhotoUrl() }}" alt="Foto atual" class="h-10 w-10 rounded-full object-cover">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="remove_photo" value="1" class="text-edux-primary focus:ring-edux-primary">
                                <span>Remover foto atual</span>
                            </label>
                        </div>
                    @endif
                </label>
            </div>

            <div class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Perfil de acesso</span>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($roles as $option)
                        <label class="flex items-center gap-2 rounded-2xl border border-edux-line/60 px-4 py-3 text-sm font-semibold text-slate-600">
                            <input type="radio" name="role" value="{{ $option->value }}" @checked(old('role', $user->role->value) === $option->value) class="text-edux-primary focus:ring-edux-primary">
                            <span>{{ $option->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('role') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <label class="block space-y-2 text-sm font-semibold text-slate-600">
                <span>Qualificações / bio</span>
                <textarea rows="4" name="qualification" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">{{ old('qualification', $user->qualification) }}</textarea>
                @error('qualification') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Nova senha (opcional)</span>
                    <input type="password" name="password" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Confirmar nova senha</span>
                    <input type="password" name="password_confirmation" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                </label>
            </div>

            <div class="rounded-2xl border border-edux-line/60 bg-edux-background/70 p-4 text-sm text-slate-600">
                <p class="font-semibold text-edux-primary">Contexto atual</p>
                <p class="mt-2">Escola atual: {{ $tenantLabel($user->systemSetting) }}</p>
                <p class="mt-1">ID do usuário: #{{ $user->id }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Salvar alterações</button>
                <a href="{{ route('sa.users.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
