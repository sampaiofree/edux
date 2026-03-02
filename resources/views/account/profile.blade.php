@php
    $layout = auth()->check() && auth()->user()->isStudent()
        ? 'layouts.student'
        : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'Minha conta')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Configuracoes</p>
            <h1 class="font-display text-3xl text-edux-primary">Perfil e seguranca</h1>
            <p class="text-slate-600">Atualize seus dados basicos, qualificacao e foto de perfil.</p>
        </header>

        <livewire:account.profile-form />

        @if (auth()->user()?->isStudent())
            <section class="space-y-4 rounded-card border border-red-200 bg-red-50 p-6 shadow-card">
                <div class="space-y-1">
                    <p class="text-sm uppercase tracking-wide text-red-700">Zona de risco</p>
                    <h2 class="font-display text-2xl text-red-800">Solicitar exclusao da conta</h2>
                    <p class="text-sm text-red-700">
                        Esta acao e irreversivel. Ao confirmar a solicitacao, nossa equipe administrativa fara a analise e podera excluir sua conta e historico vinculado.
                    </p>
                </div>

                @if ($pendingDeletionRequest)
                    <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Sua solicitacao foi enviada em {{ $pendingDeletionRequest->requested_at?->format('d/m/Y H:i') }} e esta em analise.
                    </div>
                @else
                    <form action="{{ route('account.deletion-requests.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <label class="space-y-2 text-sm font-semibold text-red-800">
                            <span>Motivo (opcional)</span>
                            <textarea
                                name="reason"
                                rows="4"
                                maxlength="1000"
                                class="w-full rounded-xl border border-red-200 bg-white px-4 py-3 text-slate-700 focus:border-red-400 focus:ring-red-200"
                                placeholder="Se quiser, explique o motivo do pedido."
                            >{{ old('reason') }}</textarea>
                            @error('reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <button
                            type="submit"
                            class="edux-btn bg-red-600 text-white hover:bg-red-700"
                            onclick="return confirm('Deseja realmente solicitar a exclusao da sua conta?')"
                        >
                            Solicitar exclusao da conta
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </section>
@endsection

