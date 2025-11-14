<div>
    @if ($open && $notification)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="absolute inset-0" wire:click="dismiss"></div>
            <div class="relative z-10 w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-edux-primary">Aviso</p>
                    <h3 class="text-2xl font-display text-edux-primary">{{ $notification->title }}</h3>
                    <p class="text-xs text-slate-400">{{ optional($notification->published_at)->format('d/m/Y H:i') ?? 'Recente' }}</p>
                </div>
                <button type="button" class="text-sm font-semibold text-slate-500 hover:text-edux-primary" wire:click="dismiss">
                    Fechar
                </button>
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
                <p class="text-sm text-slate-600">{{ $notification->body }}</p>
            @endif
            <div class="flex flex-wrap gap-3">
                @if ($notification->button_label && $notification->button_url)
                    <a href="{{ $notification->button_url }}" target="_blank" rel="noopener" class="edux-btn">
                        {{ $notification->button_label }}
                    </a>
                @endif
                <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="dismiss">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
