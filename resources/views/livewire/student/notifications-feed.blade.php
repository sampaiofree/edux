@php
    use Illuminate\Support\Str;
@endphp

<section class="space-y-4">
    <!--<div class="rounded-card bg-white p-5 shadow-card">
        <p class="text-sm uppercase tracking-wide text-edux-primary">Notificacoes</p>
        <h2 class="text-2xl font-display text-edux-primary">Mensagens para voce</h2>
        <p class="text-slate-600 text-sm">Fique por dentro de tudo o que acontece no EduX.</p>
    </div>-->

    <div class="space-y-4">
        @forelse ($notifications as $notification)
            @php
                $hasSeen = $user && $notification->views->isNotEmpty();
            @endphp
            <article class="rounded-card bg-white p-5 shadow-card space-y-3 border-l-4 {{ $hasSeen ? 'border-edux-line' : 'border-edux-primary/80' }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-edux-primary text-lg">{{ $notification->title }}</h3>
                        @if (! $hasSeen)
                            <span class="rounded-full bg-edux-primary/10 px-2 py-0.5 text-xs font-semibold text-edux-primary">Nova</span>
                        @endif
                    </div>
                    <span class="text-xs uppercase text-slate-400">{{ optional($notification->published_at)->format('d/m/Y H:i') ?? 'Rascunho' }}</span>
                </div>
                @if ($notification->image_path)
                    <img src="{{ asset('storage/'.$notification->image_path) }}" alt="{{ $notification->title }}" class="w-full rounded-xl object-cover">
                @endif
                @if ($notification->video_url)
                    <div class="aspect-video w-full overflow-hidden rounded-xl">
                        <iframe src="{{ $notification->video_url }}" class="h-full w-full" allowfullscreen loading="lazy"></iframe>
                    </div>
                @endif
                @if ($notification->body)
                    <p class="text-sm text-slate-600">{{ Str::of($notification->body)->limit(320) }}</p>
                @endif
                @if ($notification->button_label && $notification->button_url)
                    <a href="{{ $notification->button_url }}" target="_blank" rel="noopener" class="edux-btn inline-flex">
                        {{ $notification->button_label }}
                    </a>
                @endif
            </article>
        @empty
            <div class="rounded-card bg-white p-6 text-center text-slate-500 shadow-card">
                Nenhuma notificacao por aqui ainda.
            </div>
        @endforelse
    </div>

    <div>
        {{ $notifications->links() }}
    </div>
</section>
