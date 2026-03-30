<section class="space-y-6">
    <header class="rounded-card bg-white p-6 shadow-card">
        <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
        <h1 class="font-display text-3xl text-edux-primary">Meus certificados</h1>
        <p class="text-slate-600 text-sm">Acesse, baixe ou compartilhe seu certificado. Se ainda não existir, clique em Gerar certificado.</p>
    </header>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-card bg-white p-6 shadow-card space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="uppercase tracking-wide text-edux-primary">Certificados gerados</p>
                <p class="text-slate-500 text-sm">Lista de certificados ja emitidos com seus links publicos.</p>
            </div>
            <span class="text-xs uppercase tracking-wide text-slate-400">{{ $certificates->count() }} certificados</span>
        </div>

        @if ($certificates->isEmpty())
            <p class="text-sm text-slate-500">
                Nenhum certificado gerado ainda. Gere o primeiro clicando no botao abaixo.
            </p>
        @else
            <div class="space-y-4">
                @foreach ($certificates as $certificate)
                    @php
                        $downloadRoute = $certificate->course
                            ? route('learning.courses.certificate.download', [$certificate->course->slug, $certificate])
                            : null;
                        $publicUrl = $certificate->public_token
                            ? route('certificates.verify', $certificate->public_token)
                            : null;
                    @endphp
                    <div class="rounded-2xl border border-edux-line px-4 py-4 md:px-6 md:py-5 md:flex md:items-center md:justify-between">
                        <div class="flex items-center gap-4 md:gap-6">
                            <div class="h-16 w-16 overflow-hidden rounded-2xl bg-slate-100">
                                @if ($certificate->course?->coverImageUrl())
                                    <img
                                        src="{{ $certificate->course->coverImageUrl() }}"
                                        alt="{{ $certificate->course->title }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold uppercase text-slate-400">
                                        Sem imagem
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p class="text-2xl font-semibold text-slate-800">
                                    {{ $certificate->course?->title ?? 'Curso excluido' }}
                                </p>
                                <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                                    <span>Emitido em {{ $certificate->issued_at?->format('d/m/Y') ?? '—' }}</span>
                                    <span>Numero {{ $certificate->number ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 md:mt-0 md:ml-8">
                            @if ($downloadRoute)
                                <a
                                    href="{{ $downloadRoute }}"
                                    data-certificate-share-trigger="1"
                                    data-certificate-download-url="{{ $downloadRoute }}"
                                    data-certificate-public-url="{{ $publicUrl ?? '' }}"
                                    data-certificate-title="{{ $certificate->course?->title ?? 'Certificado' }}"
                                    data-certificate-filename="{{ $certificate->course ? 'certificado-'.$certificate->course->slug.'.pdf' : 'certificado.pdf' }}"
                                    data-certificate-sharing-label="Preparando PDF..."
                                    class="edux-btn text-white px-4 py-2 text-sm font-semibold text-edux-primary shadow-sm"
                                >
                                    <span
                                        data-certificate-share-label
                                        data-web-label="Baixar PDF"
                                        data-native-label="Compartilhar PDF"
                                    >
                                        Baixar PDF
                                    </span>
                                </a>
                            @endif
                            @if ($publicUrl)
                                <a
                                    href="{{ $publicUrl }}"
                                    class="edux-btn bg-edux-primary px-4 py-2 text-sm font-semibold text-white hover:bg-edux-primary/80"
                                >
                                    Ver link publico
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex justify-end">
        <a href="{{ route('certificado.create') }}" wire:navigate class="edux-btn">
            Gerar certificado
        </a>
    </div>
</section>
