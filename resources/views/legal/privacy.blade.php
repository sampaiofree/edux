@extends('layouts.public-lesson')

@section('title', 'Política de privacidade')

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[28px] bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-6 py-8 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-200/80">Privacidade</p>
            <h1 class="mt-3 font-['Poppins'] text-3xl font-bold leading-tight">
                Política de privacidade da {{ $schoolName }}
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                Esta página resume como a {{ $schoolName }} trata informações pessoais no domínio
                <span class="font-semibold text-white">{{ $displayDomain }}</span>.
            </p>
        </section>

        <section class="space-y-4 rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Dados institucionais</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Informações básicas da escola responsável por este ambiente.
                    </p>
                </div>
                <a
                    href="{{ route('legal.support') }}"
                    class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                >
                    Ir para suporte
                </a>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Escola</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-900">{{ $schoolName }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Domínio</dt>
                    <dd class="mt-2 text-sm font-medium text-slate-900">{{ $displayDomain }}</dd>
                </div>
                @if (filled($settings->escola_cnpj))
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">CNPJ</dt>
                        <dd class="mt-2 text-sm font-medium text-slate-900">{{ $settings->escola_cnpj }}</dd>
                    </div>
                @endif
                @if ($contactEmail)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Contato principal</dt>
                        <dd class="mt-2 text-sm font-medium text-slate-900">
                            @if ($contactName)
                                {{ $contactName }}<br>
                            @endif
                            <a href="mailto:{{ $contactEmail }}" class="text-cyan-700 underline-offset-4 hover:underline">
                                {{ $contactEmail }}
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="space-y-5 rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <article class="space-y-2">
                <h2 class="text-lg font-semibold text-slate-900">1. Coleta de informações</h2>
                <p class="text-sm leading-6 text-slate-600">
                    A {{ $schoolName }} pode tratar informações fornecidas em formulários, processos de matrícula,
                    acesso às áreas do aluno e interações realizadas neste domínio para viabilizar o atendimento,
                    a entrega dos cursos e a comunicação institucional.
                </p>
            </article>

            <article class="space-y-2">
                <h2 class="text-lg font-semibold text-slate-900">2. Uso dos dados</h2>
                <p class="text-sm leading-6 text-slate-600">
                    Os dados podem ser utilizados para autenticação, gestão acadêmica, emissão de certificados,
                    suporte, envio de avisos operacionais e melhoria da experiência digital da escola.
                </p>
            </article>

            <article class="space-y-2">
                <h2 class="text-lg font-semibold text-slate-900">3. Compartilhamento e segurança</h2>
                <p class="text-sm leading-6 text-slate-600">
                    A {{ $schoolName }} adota medidas razoáveis para proteger os dados tratados neste ambiente.
                    Informações pessoais somente devem ser compartilhadas quando necessárias para a prestação do serviço,
                    cumprimento de obrigações legais ou operação técnica da plataforma.
                </p>
            </article>

            <article class="space-y-2">
                <h2 class="text-lg font-semibold text-slate-900">4. Direitos do titular</h2>
                <p class="text-sm leading-6 text-slate-600">
                    Se você tiver dúvidas sobre tratamento de dados, atualização cadastral ou solicitações relacionadas
                    à sua privacidade, utilize os canais de contato da escola listados abaixo.
                </p>
            </article>
        </section>

        <section class="rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Contato sobre privacidade</h2>
            @if ($contactEmail)
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Solicitações relacionadas à privacidade podem ser encaminhadas para
                    <a href="mailto:{{ $contactEmail }}" class="font-medium text-cyan-700 underline-offset-4 hover:underline">
                        {{ $contactEmail }}
                    </a>.
                </p>
            @else
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Esta escola ainda não configurou um e-mail público específico para contato sobre privacidade.
                    Utilize a página de suporte para localizar os canais disponíveis no momento.
                </p>
            @endif
        </section>
    </div>
@endsection
