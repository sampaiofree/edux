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
        $schoolName = trim((string) ($settings->escola_nome ?? '')) ?: 'EduX';
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
            'pageTitle' => $schoolName.' | Cursos online',
            'logoUrl' => $logoUrl,
            'featureImageUrl' => $featureImageUrl,
            'topBannerItems' => [
                'Cursos 100% online com acesso organizado.',
                'Estudo no seu ritmo pelo celular ou computador.',
                'Certificado digital conforme conclusão do curso.',
                'Páginas públicas claras para comparar formações.',
            ],
            'heroTitle' => 'Escolha um curso online e avance para a página completa da sua formação.',
            'heroSubtitle' => 'A home pública do '.$schoolName.' foi organizada para mostrar os cursos disponíveis com clareza, sem promessas irreais e com acesso direto às páginas locais de matrícula.',
            'reasonCards' => [
                [
                    'badge' => '01',
                    'title' => 'Quer fortalecer seu currículo com uma formação objetiva?',
                    'body' => 'Os cursos publicados ajudam você a ganhar repertório, prática e linguagem profissional para se preparar melhor.',
                ],
                [
                    'badge' => '02',
                    'title' => 'Precisa estudar no seu próprio ritmo?',
                    'body' => 'A plataforma foi pensada para funcionar bem em diferentes rotinas, com acesso online simples e direto.',
                ],
                [
                    'badge' => '03',
                    'title' => 'Procura páginas claras antes de decidir?',
                    'body' => 'Cada curso leva para uma landing local com resumo, detalhes da proposta e informações de matrícula.',
                ],
                [
                    'badge' => '04',
                    'title' => 'Busca uma experiência sem excesso de informação?',
                    'body' => 'A home concentra o essencial para você comparar cursos publicados e seguir com mais contexto.',
                ],
            ],
            'whyStudyParagraphs' => [
                'O '.$schoolName.' organiza seus cursos publicados em uma página pública direta, pensada para facilitar a descoberta de novas formações e o acesso à landing individual de cada curso.',
                'A proposta é apresentar conteúdo e estrutura de forma clara, com foco em preparação profissional, navegação simples e leitura rápida em qualquer dispositivo.',
            ],
            'benefitColumns' => [
                [
                    'title' => 'Benefícios para quem estuda',
                    'items' => [
                        'Estudo flexível para encaixar na rotina.',
                        'Conteúdo introdutório e aplicado em diferentes áreas.',
                        'Acesso digital com experiência simples de navegar.',
                        'Mais contexto para currículo, entrevistas e atualização profissional.',
                    ],
                ],
                [
                    'title' => 'Diferenciais da experiência',
                    'items' => [
                        'Home pública focada em clareza e comparação rápida.',
                        'Páginas locais de curso com informações próprias.',
                        'Certificado digital conforme conclusão e regras aplicáveis.',
                        'Materiais complementares em cursos específicos, quando configurados.',
                    ],
                ],
            ],
            'certificateChecklist' => [
                'Certificado digital liberado conforme a conclusão do curso e das regras aplicáveis.',
                'Acesso online para estudar no celular ou computador.',
                'Páginas individuais com informações próprias de cada formação.',
                'Estrutura pensada para decidir com mais contexto antes da matrícula.',
            ],
            'testimonials' => [
                [
                    'id' => 'rejxwJ2lX-Q',
                    'label' => 'Depoimento de aluno 1',
                    'title' => 'História de aluno',
                    'caption' => 'Clique para assistir ao relato completo.',
                ],
                [
                    'id' => '1hekoAyPVRs',
                    'label' => 'Depoimento de aluno 2',
                    'title' => 'Experiência de estudo',
                    'caption' => 'Vídeo hospedado no YouTube.',
                ],
                [
                    'id' => 'Mnn2yIAlhZk',
                    'label' => 'Depoimento de aluno 3',
                    'title' => 'Relato de preparação',
                    'caption' => 'Conteúdo carregado somente no clique.',
                ],
                [
                    'id' => '1qWXa9F0qBw',
                    'label' => 'Depoimento de aluno 4',
                    'title' => 'Trajetória na plataforma',
                    'caption' => 'A estrutura inicial usa placeholder local.',
                ],
            ],
            'bonusItems' => [
                [
                    'eyebrow' => 'Material complementar',
                    'title' => $cartaEstagioImageUrl
                        ? 'Carta de estágio configurada na plataforma'
                        : 'Materiais extras quando o curso oferecer suporte adicional',
                    'description' => $cartaEstagioImageUrl
                        ? 'Quando configurada, a carta de estágio aparece como apoio complementar em cursos aplicáveis. Ela não representa promessa de vaga ou contratação.'
                        : 'Alguns cursos podem incluir materiais complementares para apoiar sua organização e sua apresentação profissional.',
                    'image_url' => $cartaEstagioImageUrl,
                    'placeholder' => 'B1',
                ],
                [
                    'eyebrow' => 'Experiência organizada',
                    'title' => 'Uma home pública criada para levar você até a página do curso com clareza',
                    'description' => 'Você compara os cursos disponíveis, escolhe a formação mais aderente ao seu momento e segue para a landing local com informações completas.',
                    'image_url' => null,
                    'placeholder' => 'B2',
                ],
            ],
            'faqItems' => [
                [
                    'question' => 'Como funciona o acesso ao curso?',
                    'answer' => 'Depois da confirmação da matrícula, o acesso é liberado conforme a configuração do curso. As aulas ficam disponíveis online para assistir pelo celular ou computador.',
                ],
                [
                    'question' => 'Preciso ter experiência prévia para começar?',
                    'answer' => 'Não necessariamente. A plataforma reúne cursos com linguagem simples e organização clara para quem está começando ou quer se atualizar.',
                ],
                [
                    'question' => 'O curso garante emprego?',
                    'answer' => 'Não. Os cursos apoiam a preparação profissional e podem fortalecer currículo e repertório, mas não garantem contratação.',
                ],
                [
                    'question' => 'Como funciona o certificado?',
                    'answer' => 'Ao concluir o curso, e quando aplicável também cumprir o teste final, o certificado digital fica disponível conforme as regras da plataforma.',
                ],
                [
                    'question' => 'Posso estudar pelo celular?',
                    'answer' => 'Sim. A navegação foi pensada para funcionar tanto no celular quanto no computador.',
                ],
                [
                    'question' => 'A home leva para páginas locais de curso?',
                    'answer' => 'Sim. Os cards desta página apontam para as rotas públicas locais de cada curso publicado.',
                ],
            ],
        ];
    }
}
