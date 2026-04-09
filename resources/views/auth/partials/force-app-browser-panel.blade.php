<section
    class="hidden p-2 text-center sm:p-3"
    data-login-force-app-browser="1"
    hidden
>
    <div class="mx-auto max-w-md space-y-6">
        <span class="inline-flex rounded-full bg-edux-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-edux-primary">
            Acesso pelo app
        </span>

        <div class="space-y-3">
            <h2 class="font-display text-3xl leading-tight text-slate-900">{{ $forceAppTitle }}</h2>
            <p class="text-sm leading-6 text-slate-600 sm:text-base">
                {{ $forceAppDescription }}
            </p>
        </div>

        <div class="space-y-3">
            @if ($playStoreLink)
                <a
                    href="{{ $playStoreLink }}"
                    target="_blank"
                    rel="noopener"
                    class="group flex w-full items-center justify-between rounded-[1.4rem] border border-slate-200 bg-white px-5 py-4 text-left text-slate-900 shadow-[0_16px_35px_rgba(15,23,42,0.08)] transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-[0_20px_40px_rgba(15,23,42,0.12)]"
                >
                    <span class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 3.6 14.9 12 4 20.4Z" fill="#34A853"/>
                                <path d="M14.9 12 18.2 9.4 21 11.1c.8.5.8 1.7 0 2.2l-2.8 1.7Z" fill="#FBBC04"/>
                                <path d="M4 3.6 11.8 9.6l2.9 2.4-2.9 2.4L4 20.4Z" fill="#4285F4" opacity=".9"/>
                                <path d="M4 3.6 21 11.1c.8.5.8 1.7 0 2.2L4 20.4l10.9-8.4Z" fill="#EA4335" opacity=".88"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Android</span>
                            <span class="mt-1 block text-base font-semibold">Abrir na Play Store</span>
                        </span>
                    </span>
                    <svg class="h-5 w-5 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                    </svg>
                </a>
            @endif

            @if ($appleStoreLink)
                <a
                    href="{{ $appleStoreLink }}"
                    target="_blank"
                    rel="noopener"
                    class="group flex w-full items-center justify-between rounded-[1.4rem] border border-slate-200 bg-white px-5 py-4 text-left text-slate-900 shadow-[0_16px_35px_rgba(15,23,42,0.08)] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-[0_20px_40px_rgba(15,23,42,0.12)]"
                >
                    <span class="flex items-center gap-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M16.63 12.59c.03 3.12 2.75 4.16 2.78 4.17-.02.07-.43 1.47-1.4 2.92-.84 1.25-1.71 2.49-3.09 2.52-1.35.03-1.78-.8-3.32-.8-1.55 0-2.03.77-3.29.83-1.31.05-2.31-1.31-3.16-2.56-1.73-2.51-3.06-7.08-1.28-10.18.89-1.54 2.47-2.52 4.18-2.55 1.3-.02 2.54.88 3.32.88.78 0 2.25-1.09 3.79-.93.65.03 2.46.27 3.63 1.98-.09.06-2.17 1.26-2.16 3.72Zm-2.11-6.23c.7-.85 1.16-2.03 1.03-3.2-1 .04-2.2.67-2.92 1.52-.64.74-1.21 1.94-1.05 3.08 1.11.09 2.23-.56 2.94-1.4Z" />
                            </svg>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">iPhone</span>
                            <span class="mt-1 block text-base font-semibold">Abrir na App Store</span>
                        </span>
                    </span>
                    <svg class="h-5 w-5 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                    </svg>
                </a>
            @endif
        </div>

        @php($forceAppLinks = $forceAppLinks ?? [])
        @if ($forceAppLinks !== [])
            <div class="space-y-2 text-sm font-semibold">
                @foreach ($forceAppLinks as $link)
                    <a href="{{ $link['href'] }}" class="block text-edux-primary hover:underline">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
