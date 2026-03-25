<?php

namespace App\Support;

use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\SupportWhatsappNumber;
use App\Models\SystemSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicCoursePageViewDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Course $course): array
    {
        abort_if($course->status !== 'published', 404);

        $course->loadMissing([
            'owner',
            'modules.lessons' => fn ($query) => $query->orderBy('position'),
            'certificateBranding',
            'enrollments',
            'supportWhatsappNumber',
            'checkouts' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['bonuses' => fn ($bonusQuery) => $bonusQuery->orderBy('id')])
                ->orderBy('price')
                ->orderBy('hours')
                ->orderBy('id'),
        ]);

        $branding = CertificateBranding::resolveForCourse($course);
        $settings = SystemSetting::current();
        $schoolName = trim((string) ($settings->escola_nome ?? '')) ?: 'EduX';
        $logoUrl = $settings->assetUrl('default_logo_dark_path') ?: $settings->assetUrl('default_logo_path');
        $heroImageUrl = $course->coverImageUrl() ?: $settings->assetUrl('default_course_cover_path') ?: $logoUrl;
        $courseHoursLabel = $course->duration_minutes
            ? rtrim(rtrim(number_format($course->duration_minutes / 60, 1, ',', '.'), '0'), ',')
            : null;
        $totalLessonsCount = $course->modules->sum(fn ($module) => $module->lessons->count());
        $studentCount = $course->enrollments->count();
        $supportWhatsappContact = $this->resolveSupportWhatsappContact($course);

        $previewLessons = $course->modules
            ->sortBy('position')
            ->flatMap(fn ($module) => $module->lessons->sortBy('position'))
            ->take(5)
            ->values()
            ->map(function ($lesson) use ($course): array {
                $youtubeId = $this->extractYoutubeId($lesson->video_url);
                $playerType = $this->playerTypeFor($lesson->video_url, $youtubeId);
                $playerUrl = $this->playerUrlFor($lesson->video_url, $youtubeId);

                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'youtube_id' => $youtubeId,
                    'video_url' => $lesson->video_url,
                    'player_type' => $playerType,
                    'player_url' => $playerUrl,
                    'thumb_url' => $youtubeId
                        ? "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg"
                        : $course->coverImageUrl(),
                ];
            });

        $buyUrl = $course->checkouts->first()?->checkout_url
            ?? $course->certificate_payment_url
            ?? '#oferta';

        $certificateFrontPreview = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => 'Seu nome aqui',
            'issuedAt' => now(),
            'mode' => 'preview',
            'presentation' => 'minimal',
        ])->render();

        $certificateBackPreview = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
            'mode' => 'preview',
            'presentation' => 'minimal',
        ])->render();

        [$areasOfPractice, $practiceHighlights] = $this->buildPracticeBlocks($course);
        $modulesAccordion = $this->buildModulesAccordion($course, $courseHoursLabel, $totalLessonsCount);
        $testimonialCards = $this->buildTestimonialCards();
        $faqItems = $this->buildFaqItems($course);
        $bonusCards = $this->buildBonusCards($settings);
        $extraBonusItems = $course->checkouts
            ->flatMap(fn ($checkout) => $checkout->bonuses)
            ->unique(fn ($bonus) => Str::lower(trim((string) $bonus->nome)))
            ->values()
            ->map(fn ($bonus) => [
                'name' => trim((string) $bonus->nome),
                'description' => trim((string) ($bonus->descricao ?? '')),
                'price_label' => is_numeric($bonus->preco)
                    ? 'R$ '.number_format((float) $bonus->preco, 2, ',', '.')
                    : null,
            ]);

        [$plansPayload, $panelAction, $stickyAction] = $this->buildPlanPayload(
            $course,
            $settings,
            $supportWhatsappContact,
            $courseHoursLabel
        );

        return [
            'course' => $course,
            'studentCount' => $studentCount,
            'previewLessons' => $previewLessons,
            'buyUrl' => $buyUrl,
            'certificateFrontPreview' => $certificateFrontPreview,
            'certificateBackPreview' => $certificateBackPreview,
            'supportWhatsappContact' => $supportWhatsappContact,
            'schoolName' => $schoolName,
            'logoUrl' => $logoUrl,
            'heroImageUrl' => $heroImageUrl,
            'heroBadge' => 'Matrículas abertas',
            'heroSubtitle' => $course->summary ?: Str::limit(strip_tags((string) $course->description), 190),
            'heroKeypoints' => [
                [
                    'icon' => 'clock',
                    'text' => $courseHoursLabel
                        ? 'Carga horária de '.$courseHoursLabel.' horas com aplicação prática.'
                        : 'Conteúdo organizado em módulos com linguagem direta.',
                ],
                [
                    'icon' => 'certificate',
                    'text' => 'Certificado digital conforme conclusão do curso e das regras aplicáveis.',
                ],
                [
                    'icon' => 'check-circle',
                    'text' => 'Acesso online para assistir de onde e quando quiser.',
                ],
            ],
            'heroPrice' => $plansPayload['hero_price'],
            'heroProofItems' => $this->buildHeroProofItems($studentCount, $totalLessonsCount, $courseHoursLabel),
            'heroMediaNotes' => [
                'top' => 'Acesso online',
                'bottom' => $plansPayload['has_action'] ? 'Pagamento seguro' : 'Consulte disponibilidade',
            ],
            'proofStripItems' => $this->buildProofStripItems($studentCount, $totalLessonsCount, $courseHoursLabel),
            'modulesAccordion' => $modulesAccordion,
            'areasOfPractice' => $areasOfPractice,
            'practiceHighlights' => $practiceHighlights,
            'bonusCards' => $bonusCards,
            'extraBonusItems' => $extraBonusItems,
            'planSection' => $plansPayload,
            'panelAction' => $panelAction,
            'stickyAction' => $stickyAction,
            'certificateHighlights' => [
                'Documento digital com validação na plataforma.',
                'Comprovação de horas e conteúdo estudado.',
                'Formato útil para currículo, atividades complementares e processos seletivos.',
            ],
            'testimonialCards' => $testimonialCards,
            'faqItems' => $faqItems,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveSupportWhatsappContact(Course $course): ?array
    {
        $mode = $course->support_whatsapp_mode ?: Course::SUPPORT_WHATSAPP_MODE_ALL;
        $selected = $course->supportWhatsappNumber;

        $number = null;

        if ($mode === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC && $selected) {
            $number = $selected;
        } else {
            $activeNumbers = SupportWhatsappNumber::query()
                ->active()
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            if ($activeNumbers->isNotEmpty()) {
                $number = $this->pickRotatingWhatsappNumber($activeNumbers, $course);
            } elseif ($selected) {
                // Fallback para não quebrar a LP caso o número específico exista mas esteja inativo.
                $number = $selected;
            }
        }

        if (! $number) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $number->whatsapp) ?: '';
        if ($digits === '') {
            return null;
        }

        $message = rawurlencode("Olá! Quero tirar dúvidas sobre o curso {$course->title}.");
        $link = "https://wa.me/{$digits}?text={$message}";

        return [
            'id' => $number->id,
            'label' => $number->label,
            'whatsapp' => $number->whatsapp,
            'description' => $number->description,
            'link' => $link,
            'mode' => $mode,
            'is_rotating' => $mode === Course::SUPPORT_WHATSAPP_MODE_ALL,
        ];
    }

    private function pickRotatingWhatsappNumber(Collection $numbers, Course $course): ?SupportWhatsappNumber
    {
        if ($numbers->isEmpty()) {
            return null;
        }

        $visitorSeed = $this->supportRotationSeed($course);
        $hash = hash('sha256', $visitorSeed);
        $index = hexdec(substr($hash, 0, 8)) % $numbers->count();

        return $numbers->values()->get($index);
    }

    private function supportRotationSeed(Course $course): string
    {
        $request = request();

        $visitorUuid = trim((string) $request->cookie('edux_vid', ''));
        $sessionId = '';

        try {
            if (method_exists($request, 'hasSession') && $request->hasSession()) {
                $sessionId = (string) optional($request->session())->getId();
            }
        } catch (\Throwable) {
            $sessionId = '';
        }

        $ip = (string) ($request->ip() ?? '');
        $ua = (string) ($request->userAgent() ?? '');

        return implode('|', [
            'course',
            (string) $course->id,
            $visitorUuid !== '' ? $visitorUuid : 'no-visitor',
            $sessionId !== '' ? $sessionId : 'no-session',
            $ip !== '' ? $ip : 'no-ip',
            $ua !== '' ? substr($ua, 0, 120) : 'no-ua',
        ]);
    }

    private function extractYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/youtu\.be\/([\w-]+)/',
            '/youtube\.com\/watch\?v=([\w-]+)/',
            '/youtube\.com\/embed\/([\w-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return Arr::get($matches, 1);
            }
        }

        return null;
    }

    private function playerTypeFor(?string $url, ?string $youtubeId): string
    {
        if ($youtubeId) {
            return 'youtube';
        }

        if (! $url) {
            return 'none';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true)) {
            return 'video';
        }

        return 'iframe';
    }

    private function playerUrlFor(?string $url, ?string $youtubeId): ?string
    {
        if ($youtubeId) {
            return "https://www.youtube.com/embed/{$youtubeId}?modestbranding=1&rel=0&enablejsapi=1";
        }

        if (! $url) {
            return null;
        }

        return $url;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function buildPracticeBlocks(Course $course): array
    {
        $areas = collect(preg_split('/\s*;\s*/u', (string) ($course->atuacao ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $practice = collect(preg_split('/\s*;\s*/u', (string) ($course->oquefaz ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();

        if ($areas->isEmpty()) {
            $areas = collect([
                'Funções iniciais relacionadas à área de '.$course->title,
                'Ambientes que exigem organização, rotina e atenção prática',
                'Atividades de apoio em equipes e operações do dia a dia',
                'Processos internos em empresas, serviços e atendimento',
            ]);
        }

        if ($practice->isEmpty()) {
            $practice = collect([
                'Entender os conceitos introdutórios da área com linguagem simples.',
                'Executar rotinas práticas para ganhar mais segurança no dia a dia.',
                'Melhorar sua apresentação profissional com repertório e aplicação.',
            ]);
        }

        return [$areas->all(), $practice->all()];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildModulesAccordion(Course $course, ?string $courseHoursLabel, int $totalLessonsCount): array
    {
        $modules = $course->modules
            ->sortBy('position')
            ->values()
            ->map(function ($module, $index): array {
                return [
                    'index_label' => 'Módulo '.($module->position ?: ($index + 1)),
                    'title' => $module->title,
                    'lessons' => $module->lessons
                        ->sortBy('position')
                        ->values()
                        ->map(fn ($lesson, $lessonIndex) => 'Aula '.($lesson->position ?: ($lessonIndex + 1)).' - '.$lesson->title)
                        ->all(),
                ];
            })
            ->all();

        if ($modules !== []) {
            return $modules;
        }

        return [[
            'index_label' => 'Visão geral',
            'title' => 'Como funciona o curso '.$course->title,
            'lessons' => array_values(array_filter([
                $course->summary ?: Str::limit(strip_tags((string) $course->description), 150),
                $courseHoursLabel ? 'Carga horária total de '.$courseHoursLabel.' horas.' : null,
                $totalLessonsCount > 0 ? $totalLessonsCount.' aulas organizadas na plataforma.' : 'Conteúdo organizado em módulos e etapas de estudo.',
                'Acesso online para assistir no seu ritmo após a confirmação da matrícula.',
            ])),
        ]];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildTestimonialCards(): array
    {
        return [
            ['id' => 'rejxwJ2lX-Q', 'label' => 'Depoimento de aluno 1', 'title' => 'História de aluno', 'caption' => 'Clique para assistir ao relato completo.'],
            ['id' => '1hekoAyPVRs', 'label' => 'Depoimento de aluno 2', 'title' => 'Experiência de estudo', 'caption' => 'Vídeo carregado somente no clique.'],
            ['id' => 'Mnn2yIAlhZk', 'label' => 'Depoimento de aluno 3', 'title' => 'Relato de preparação', 'caption' => 'Conteúdo hospedado no YouTube.'],
            ['id' => '1qWXa9F0qBw', 'label' => 'Depoimento de aluno 4', 'title' => 'Trajetória na plataforma', 'caption' => 'Depoimento em vídeo de aluno.'],
        ];
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function buildFaqItems(Course $course): array
    {
        return [
            ['question' => 'Quando tenho acesso ao curso?', 'answer' => 'O acesso é liberado após a confirmação da matrícula, conforme as regras vigentes na plataforma.'],
            ['question' => 'Por quanto tempo posso assistir às aulas?', 'answer' => 'Você tem acesso ao conteúdo conforme as regras de acesso vigentes na plataforma após a matrícula.'],
            ['question' => 'O certificado é válido?', 'answer' => 'Sim. O certificado é emitido digitalmente conforme a conclusão do curso e pode ser validado na plataforma.'],
            ['question' => 'Preciso ter experiência prévia?', 'answer' => 'Não necessariamente. O conteúdo foi estruturado para quem está começando e para quem busca atualização prática.'],
            ['question' => 'Preciso fazer prova para ganhar o certificado?', 'answer' => $course->finalTest ? 'Sim. O certificado é liberado após a conclusão do curso e do teste final configurado.' : 'Não. Basta concluir o curso conforme as regras aplicáveis para liberar o certificado.'],
            ['question' => 'Este curso garante emprego?', 'answer' => 'Não. O curso ajuda na preparação profissional, fortalece seu currículo e sua prática, mas não garante contratação.'],
            ['question' => 'Posso estudar pelo celular?', 'answer' => 'Sim. A plataforma funciona no celular, tablet e computador para você estudar onde for melhor.'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildBonusCards(SystemSetting $settings): array
    {
        $hasCartaEstagio = $settings->assetUrl('carta_estagio') !== null;

        return [
            [
                'icon' => 'briefcase',
                'title' => $hasCartaEstagio ? 'Carta de estágio' : 'Materiais complementares',
                'description' => $hasCartaEstagio
                    ? 'Documento complementar para fortalecer sua apresentação profissional.'
                    : 'Alguns cursos oferecem materiais extras para apoiar sua jornada de estudos.',
            ],
            [
                'icon' => 'rocket',
                'title' => 'Estudo organizado',
                'description' => 'Conteúdo estruturado para você avançar com clareza e aplicação prática.',
            ],
            [
                'icon' => 'certificate',
                'title' => 'Certificado com validação',
                'description' => 'Documento digital para reforçar currículo e comprovar sua conclusão.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildHeroProofItems(int $studentCount, int $totalLessonsCount, ?string $courseHoursLabel): array
    {
        $items = [];

        if ($studentCount > 0) {
            $items[] = [
                'icon' => 'users',
                'text' => number_format($studentCount, 0, ',', '.').' matrículas registradas',
            ];
        }

        if ($totalLessonsCount > 0) {
            $items[] = [
                'icon' => 'book',
                'text' => $totalLessonsCount.' aulas organizadas',
            ];
        } elseif ($courseHoursLabel) {
            $items[] = [
                'icon' => 'clock',
                'text' => $courseHoursLabel.' horas de conteúdo',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildProofStripItems(int $studentCount, int $totalLessonsCount, ?string $courseHoursLabel): array
    {
        $items = [];

        if ($studentCount > 0) {
            $items[] = [
                'icon' => 'users',
                'value' => number_format($studentCount, 0, ',', '.').'+',
                'label' => 'matrículas registradas',
            ];
        } else {
            $items[] = [
                'icon' => 'rocket',
                'value' => 'Online',
                'label' => 'acesso pelo celular e computador',
            ];
        }

        $items[] = [
            'icon' => 'book',
            'value' => $totalLessonsCount > 0 ? (string) $totalLessonsCount : 'Módulos',
            'label' => $totalLessonsCount > 0 ? 'aulas organizadas' : 'conteúdo estruturado',
        ];

        $items[] = [
            'icon' => 'clock',
            'value' => $courseHoursLabel ? $courseHoursLabel.'h' : 'Online',
            'label' => $courseHoursLabel ? 'de conteúdo objetivo' : 'acesso flexível',
        ];

        $items[] = [
            'icon' => 'certificate',
            'value' => 'Certificado',
            'label' => 'digital conforme conclusão',
        ];

        return $items;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null}
     */
    private function buildPlanPayload(
        Course $course,
        SystemSetting $settings,
        ?array $supportWhatsappContact,
        ?string $courseHoursLabel
    ): array {
        $validCheckouts = $course->checkouts
            ->filter(fn ($checkout) => trim((string) ($checkout->checkout_url ?? '')) !== '')
            ->sortBy(fn ($checkout) => [(float) $checkout->price, (int) $checkout->hours, $checkout->id])
            ->values();

        $recommendedCheckout = $validCheckouts
            ->sortByDesc(fn ($checkout) => [
                $checkout->bonuses->count(),
                (int) ($checkout->hours ?? 0),
                (float) ($checkout->price ?? 0),
            ])
            ->first();

        $recommendedCheckout = $recommendedCheckout ?: $validCheckouts->first();
        $hasWhatsappAction = is_array($supportWhatsappContact ?? null) && trim((string) ($supportWhatsappContact['link'] ?? '')) !== '';
        $heroPrice = null;

        if ($recommendedCheckout) {
            $heroPrice = [
                'label' => $validCheckouts->count() > 1 ? 'Plano recomendado' : 'Investimento único de',
                'value' => 'R$ '.number_format((float) $recommendedCheckout->price, 2, ',', '.'),
                'cash_line' => 'Pagamento único da matrícula.',
            ];
        }

        $panelAction = [
            'type' => $recommendedCheckout ? 'checkout' : ($hasWhatsappAction ? 'whatsapp' : 'none'),
            'title' => $recommendedCheckout ? 'Resumo da matrícula' : ($hasWhatsappAction ? 'Atendimento por WhatsApp' : 'Disponibilidade da matrícula'),
            'price_label' => $recommendedCheckout
                ? 'R$ '.number_format((float) $recommendedCheckout->price, 2, ',', '.')
                : ($hasWhatsappAction ? 'Atendimento direto' : 'Consulte depois'),
            'description' => $recommendedCheckout
                ? ($recommendedCheckout->nome ?: 'Plano recomendado').($recommendedCheckout->hours ? ' • '.$recommendedCheckout->hours.'h' : '')
                : ($hasWhatsappAction ? 'Fale com a equipe para confirmar sua matrícula.' : 'No momento, não há uma opção ativa de matrícula para este curso.'),
            'meta_items' => [
                'Formato 100% online',
                'Acesso pelo celular e computador',
                'Pagamento seguro ou atendimento direto',
            ],
            'action_url' => $recommendedCheckout?->checkout_url ?: trim((string) ($supportWhatsappContact['link'] ?? '')),
            'action_label' => $recommendedCheckout ? 'Ir para matrícula' : ($hasWhatsappAction ? 'Falar no WhatsApp' : null),
            'checkout_id' => $recommendedCheckout?->id,
            'checkout_name' => $recommendedCheckout?->nome ?: ($recommendedCheckout ? 'Plano recomendado' : null),
            'checkout_hours' => $recommendedCheckout?->hours,
            'checkout_price' => $recommendedCheckout ? (float) $recommendedCheckout->price : null,
        ];

        $stickyAction = $panelAction['action_url']
            ? [
                'type' => $panelAction['type'],
                'url' => $panelAction['action_url'],
                'label' => $panelAction['type'] === 'whatsapp' ? 'Falar no WhatsApp' : 'Começar agora',
                'price_label' => $panelAction['price_label'],
                'checkout_id' => $panelAction['checkout_id'],
                'checkout_name' => $panelAction['checkout_name'],
                'checkout_hours' => $panelAction['checkout_hours'],
                'checkout_price' => $panelAction['checkout_price'],
            ]
            : null;

        $planCards = $validCheckouts->values()->map(function ($checkout, $index) use ($recommendedCheckout, $courseHoursLabel): array {
            $baseFeatures = [
                'Acesso ao curso completo',
                'Certificado digital conforme conclusão',
                $checkout->hours ? $checkout->hours.' horas de conteúdo' : ($courseHoursLabel ? $courseHoursLabel.' horas de conteúdo' : 'Estudo 100% online'),
            ];
            $bonusFeatures = $checkout->bonuses
                ->map(fn ($bonus) => trim((string) $bonus->nome))
                ->filter()
                ->all();

            return [
                'id' => $checkout->id,
                'tag' => 'Plano '.($index + 1),
                'heading' => $checkout->nome ?: ('Opção '.$checkout->hours.'h'),
                'description' => $checkout->descricao ?: 'Escolha esta opção para seguir ao pagamento com segurança.',
                'price_label' => 'R$ '.number_format((float) $checkout->price, 2, ',', '.'),
                'cash_label' => 'Pagamento único da matrícula',
                'features' => array_values(array_unique(array_filter([...$baseFeatures, ...$bonusFeatures]))),
                'action_url' => $checkout->checkout_url,
                'action_label' => $recommendedCheckout && $checkout->id === $recommendedCheckout->id
                    ? 'Quero este plano'
                    : 'Escolher esta opção',
                'checkout_id' => $checkout->id,
                'checkout_name' => $checkout->nome ?: ('Opção '.$checkout->hours.'h'),
                'checkout_hours' => $checkout->hours,
                'checkout_price' => (float) $checkout->price,
                'is_recommended' => $recommendedCheckout && $checkout->id === $recommendedCheckout->id,
            ];
        });

        if ($validCheckouts->count() >= 2) {
            return [[
                'mode' => 'plans',
                'has_action' => true,
                'hero_price' => $heroPrice,
                'primary_cards' => $planCards->take(2)->values()->all(),
                'additional_cards' => $planCards->skip(2)->values()->all(),
                'info_card' => null,
                'whatsapp_card' => null,
                'unavailable_message' => null,
            ], $panelAction, $stickyAction];
        }

        if ($validCheckouts->count() === 1) {
            return [[
                'mode' => 'plans',
                'has_action' => true,
                'hero_price' => $heroPrice,
                'primary_cards' => $planCards->take(1)->values()->all(),
                'additional_cards' => [],
                'info_card' => [
                    'heading' => 'Suporte e flexibilidade',
                    'description' => 'Se precisar de ajuda antes de concluir a matrícula, você ainda pode usar os canais de atendimento e comparar os detalhes desta formação com calma.',
                    'items' => [
                        'Pagamento seguro e acesso online.',
                        'Conteúdo organizado para estudar no seu ritmo.',
                        'Certificado digital conforme conclusão.',
                    ],
                ],
                'whatsapp_card' => null,
                'unavailable_message' => null,
            ], $panelAction, $stickyAction];
        }

        if ($hasWhatsappAction) {
            return [[
                'mode' => 'whatsapp',
                'has_action' => true,
                'hero_price' => null,
                'primary_cards' => [],
                'additional_cards' => [],
                'info_card' => null,
                'whatsapp_card' => [
                    'title' => 'Atendimento pelo WhatsApp',
                    'description' => 'No momento, a matrícula deste curso está sendo tratada diretamente pelo atendimento. Use o WhatsApp para confirmar disponibilidade e próximos passos.',
                    'action_url' => trim((string) $supportWhatsappContact['link']),
                    'action_label' => 'Falar no WhatsApp',
                ],
                'unavailable_message' => null,
            ], $panelAction, $stickyAction];
        }

        return [[
            'mode' => 'unavailable',
            'has_action' => false,
            'hero_price' => null,
            'primary_cards' => [],
            'additional_cards' => [],
            'info_card' => null,
            'whatsapp_card' => null,
            'unavailable_message' => 'Nenhuma opção de matrícula disponível no momento.',
        ], $panelAction, null];
    }
}
