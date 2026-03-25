<?php

namespace App\Support;

use App\Models\Course;
use App\Models\SystemSetting;

class HomePageDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $settings = SystemSetting::current();
        $schoolName = trim((string) ($settings->escola_nome ?? '')) ?: 'Portal Jovem Empreendedor';
        $logoUrl = $settings->assetUrl('default_logo_dark_path') ?: $settings->assetUrl('default_logo_path');
        $defaultCourseCoverUrl = $settings->assetUrl('default_course_cover_path');
        $featuredCourse = Course::query()
            ->where('status', 'published')
            ->whereNotNull('cover_image_path')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
        $featureImageUrl = $featuredCourse?->coverImageUrl() ?: $defaultCourseCoverUrl ?: $logoUrl;
        $cartaEstagioImageUrl = $settings->assetUrl('carta_estagio');

        return [
            'schoolName' => $schoolName,
            'pageTitle' => 'Iniciativa Social ' . $schoolName . ' | Qualificação Profissional',
            'logoUrl' => $logoUrl,
            'featureImageUrl' => $featureImageUrl,
            'certificatePreviewImageUrl' => 'https://jempreendedor.com/img/home_page/certificadoNovo2.webp',
            'topBannerItems' => [
                'Iniciativa privada de impacto social (sem vínculo com governo).',
                'Estude pelo celular no seu ritmo, sem mensalidades abusivas.',
                'Certificado reconhecido em todo Brasil com QR Code.',
                'Acesso imediato após a inscrição na taxa social única.',
            ],
            'heroTitle' => 'Sua oportunidade de aprender uma nova profissão com taxa social única.',
            'heroSubtitle' => 'O ' . $schoolName . ' liberou vagas exclusivas para qualificação profissional rápida. Uma iniciativa independente para você conquistar seu certificado e mudar de vida sem dívidas.',
            'reasonCards' => [
                [
                    'badge' => '01',
                    'image_url' => asset('images/home/reasons/qualificacao.webp'),
                    'title' => 'Cansado de perder vagas por falta de experiência?',
                    'body' => 'Nossos cursos focam no que o mercado realmente pede, preparando você para entrevistas e para o dia a dia da profissão.',
                ],
                [
                    'badge' => '02',
                    'image_url' => asset('images/home/reasons/semexperiencia.webp'),
                    'title' => 'Sem tempo ou dinheiro para cursos caros?',
                    'body' => 'Aqui não existe mensalidade. Você paga uma única taxa social simbólica e estuda quando e onde quiser pelo celular.',
                ],
                [
                    'badge' => '03',
                    'image_url' => asset('images/home/reasons/primeiroemprego.webp'),
                    'title' => 'Quer um currículo que chame a atenção?',
                    'body' => 'Ao concluir, você recebe um certificado válido com QR Code para provar sua competência para qualquer empresa.',
                ],
                [
                    'badge' => '04',
                    'image_url' => asset('images/home/reasons/empregomelhor.webp'),
                    'title' => 'Medo de cair em golpes ou promessas vazias?',
                    'body' => 'Somos uma instituição séria com milhares de alunos formados. Transparência total sobre o conteúdo e suporte real.',
                ],
            ],
            'whyStudyParagraphs' => [
                'O ' . $schoolName . ' acredita que a educação deve ser acessível. Por isso, organizamos este iniciativa social para oferecer cursos práticos e diretos ao ponto.',
                'Nossa estrutura é pensada para quem precisa de resultados rápidos: aulas dinâmicas, material em PDF incluso e foco total na sua empregabilidade.',
            ],
            'benefitColumns' => [
                [
                    'title' => 'Vantagens da Iniciativa Social',
                    'items' => [
                        'Taxa única acessível (Sem mensalidades).',
                        'Liberdade total de horários pelo app ou PC.',
                        'Conteúdo atualizado conforme o mercado atual.',
                        'Início imediato após a confirmação.',
                    ],
                ],
                [
                    'title' => 'Diferenciais do Certificado',
                    'items' => [
                        'Válido em todo o território nacional.',
                        'Autenticação via QR Code para empresas.',
                        'Pode ser usado para horas complementares.',
                        'Destaque real no seu currículo e LinkedIn.',
                    ],
                ],
            ],
            'certificateChecklist' => [
                'Certificado digital reconhecido liberado na conclusão.',
                'Suporte direto com professores para tirar dúvidas.',
                'Material de apoio em PDF já incluso no valor social.',
                'Garantia de 7 dias: sua satisfação ou reembolso total.',
            ],
            'testimonials' => [
                [
                    'id' => 'rejxwJ2lX-Q', // Link dos depoimentos reais que você passou
                    'label' => 'Depoimento de Alunos',
                    'title' => 'Quem fez, recomenda!',
                    'caption' => 'Veja como o projeto mudou a carreira de nossos alunos.',
                ],
                // ... manter os outros IDs se forem válidos
            ],
            'bonusItems' => [
                [
                    'eyebrow' => 'Exclusivo do Projeto',
                    'title' => 'Carta de Estágio e Recomendação',
                    'description' => 'Ao finalizar sua formação, você terá acesso a materiais que ajudam a abrir portas no mercado de trabalho, como nossa carta de recomendação exclusiva.',
                    'image_url' => $cartaEstagioImageUrl,
                    'placeholder' => 'B1',
                ],
                [
                    'eyebrow' => 'Estude em qualquer lugar',
                    'title' => 'Aplicativo Próprio para Celular',
                    'description' => 'Baixe as aulas e assista mesmo sem internet. Praticidade total para quem não pode perder tempo.',
                    'image_url' => null,
                    'placeholder' => 'B2',
                ],
            ],
            'faqItems' => [
                [
                    'question' => 'O curso tem mensalidades?',
                    'answer' => 'Não! Você paga apenas uma taxa única de inscrição para ajudar a manter o iniciativa social. Não há boletos mensais nem taxas escondidas.',
                ],
                [
                    'question' => 'O certificado é reconhecido?',
                    'answer' => 'Sim. O certificado do Portal Jovem Empreendedor é válido em todo o Brasil e aceito em processos seletivos e empresas.',
                ],
                [
                    'question' => 'Por que o valor é tão baixo?',
                    'answer' => 'Trata-se de uma iniciativa privada de impacto social. Nosso objetivo é qualificar o maior número de pessoas possível sem cobrar preços abusivos.',
                ],
                [
                    'question' => 'Como recebo o acesso?',
                    'answer' => 'Assim que o pagamento for confirmado, os dados de acesso são enviados para o seu e-mail. É tudo automático e imediato.',
                ],
            ],
        ];
    }
}
