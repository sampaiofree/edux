<section class="space-y-6">
    <header class="rounded-card bg-white p-6 shadow-card">
        <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
        <h1 class="font-display text-3xl text-edux-primary">Meus cursos e certificados</h1>
        <p class="text-slate-600 text-sm">Acesse seus cursos, gere certificados pendentes ou baixe os certificados ja emitidos.</p>
    </header>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-card bg-white p-6 shadow-card space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="uppercase tracking-wide text-edux-primary">{{ $enrollments->count() }} Cursos matriculados</p>
                <p class="text-slate-500 text-sm">Encontre seu curso e clique em gerar certificado </p>
            </div>
            <!--<span class="text-xs uppercase tracking-wide text-slate-400">{{ $enrollments->count() }} cursos</span>-->
        </div>

        @if ($enrollments->isEmpty())
            <p class="text-sm text-slate-500">
                Voce ainda nao possui matriculas ativas para gerar certificados.
            </p>
        @else
            <div class="space-y-4">
                @foreach ($enrollments as $enrollment)
                    @php
                        $course = $enrollment->course;
                        $certificate = $course ? $course->certificates->first() : null;
                        $generateRoute = $course
                            ? route('certificado.create', ['course_id' => $course->id])
                            : route('certificado.create');
                        $downloadRoute = $course && $certificate
                            ? route('learning.courses.certificate.download', [$course, $certificate])
                            : null;
                        $publicUrl = $certificate?->public_token
                            ? route('certificates.verify', $certificate->public_token)
                            : null;
                        $certificateIssuanceBlocked = ! $certificate && (bool) $enrollment->certificate_issuance_blocked;
                        $supportUrl = $course
                            ? $settings->schoolWhatsappLink("Olá! Preciso de ajuda para liberar a emissão do certificado do curso {$course->title}.")
                            : null;
                        $coverUrl = $course?->coverImageUrl();
                        $progress = (int) ($enrollment->progress_percent ?? 0);
                    @endphp
                    <article
                        class="relative rounded-2xl border border-edux-line px-4 py-4 pr-16 md:px-6 md:py-5 md:pr-20 md:flex md:items-center md:justify-between"
                        wire:key="certificate-course-{{ $enrollment->id }}"
                        x-data="{ optionsOpen: false }"
                        @click.away="optionsOpen = false"
                    >
                        <div class="absolute right-4 top-4 z-10" x-cloak>
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-slate-500 transition hover:border-edux-primary/40 hover:text-edux-primary focus:outline-none focus:ring-2 focus:ring-edux-primary/30"
                                aria-label="Abrir opcoes do curso"
                                @click="optionsOpen = ! optionsOpen"
                            >
                                <span class="flex flex-col items-center gap-0.5" aria-hidden="true">
                                    <span class="h-1 w-1 rounded-full bg-current"></span>
                                    <span class="h-1 w-1 rounded-full bg-current"></span>
                                    <span class="h-1 w-1 rounded-full bg-current"></span>
                                </span>
                            </button>
                            <div
                                x-show="optionsOpen"
                                x-transition.origin.top.right
                                class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-edux-line bg-white py-1 text-sm shadow-card"
                            >
                                <a href="{{ $generateRoute }}" wire:navigate class="block px-4 py-3 font-semibold text-edux-primary hover:bg-edux-background">
                                    Gerar novo certificado
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 md:gap-6">
                            <div class="w-20 overflow-hidden rounded-xl bg-slate-100">
                                @if ($coverUrl)
                                    <img
                                        src="{{ $coverUrl }}"
                                        alt="{{ $course->title }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold uppercase text-slate-400">
                                        Sem imagem
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p class="font-semibold text-slate-800">
                                    {{ $course?->title ?? 'Curso excluido' }}
                                </p>
                                <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                                    @if ($certificate)
                                        <span>Certificado gerado</span>
                                        <span>Emitido em {{ $certificate->issued_at?->format('d/m/Y') ?? '—' }}</span>
                                        <span>Numero {{ $certificate->number ?? '—' }}</span>
                                    @else
                                        <span>{{ $progress >= 100 ? 'Curso concluido' : 'Progresso '.$progress.'%' }}</span>
                                        <span>{{ $certificateIssuanceBlocked ? 'Certificado bloqueado' : 'Certificado pendente' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 md:mt-0 md:ml-8">
                            @if ($certificate && $downloadRoute)
                                <a
                                    href="{{ $downloadRoute }}"
                                    data-certificate-share-trigger="1"
                                    data-certificate-download-url="{{ $downloadRoute }}"
                                    data-certificate-public-url="{{ $publicUrl ?? '' }}"
                                    data-certificate-title="{{ $course?->title ?? 'Certificado' }}"
                                    data-certificate-filename="{{ $course ? 'certificado-'.$course->slug.'.pdf' : 'certificado.pdf' }}"
                                    data-certificate-sharing-label="Preparando PDF..."
                                    class="edux-btn bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <path d="M7 10l5 5 5-5" />
                                        <path d="M12 15V3" />
                                    </svg>
                                    <span
                                        data-certificate-share-label
                                        data-web-label="Baixar certificado"
                                        data-native-label="Compartilhar PDF"
                                    >
                                        Baixar certificado
                                    </span>
                                </a>
                            @elseif ($certificateIssuanceBlocked)
                                <div class="max-w-sm space-y-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    <p>{{ \App\Models\Enrollment::CERTIFICATE_ISSUANCE_BLOCKED_MESSAGE }}</p>
                                    @if ($supportUrl)
                                        <a href="{{ $supportUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            Falar com suporte
                                        </a>
                                    @endif
                                </div>
                            @else
                                <a href="{{ $generateRoute }}" wire:navigate class="edux-btn bg-edux-cta px-4 py-2 text-sm font-semibold text-edux-text shadow-sm">
                                    <svg aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <path d="M14 2v6h6" />
                                        <path d="M12 18v-6" />
                                        <path d="M9 15h6" />
                                    </svg>
                                    <span>Gerar certificado</span>
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
