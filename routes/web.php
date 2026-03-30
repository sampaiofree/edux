<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AccountDeletionRequestController as AdminAccountDeletionRequestController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\GeneratedCertificateController;
use App\Http\Controllers\Admin\PaymentWebhookController;
use App\Http\Controllers\Admin\SupportWhatsappNumberController;
use App\Http\Controllers\Admin\TrackingReportExportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AccountDeletionRequestController;
use App\Http\Controllers\AccountProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdmin\CourseController as SuperAdminCourseController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\EnrollmentController as SuperAdminEnrollmentController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\CourseCertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CityCampaignController;
use App\Http\Controllers\CityCampaignV2Controller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\PublicLessonController;
use App\Http\Controllers\PublicCertificateController;
use App\Http\Controllers\PublicCoursePageV2Controller;
use App\Http\Controllers\PublicCoursePageV3Controller;
use App\Http\Controllers\PublicCoursePageV4Controller;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\StudentCourseController;
use App\Http\Controllers\StudentFinalTestController;

use Illuminate\Support\Facades\Route;

// --- Rotas de design/desenvolvimento rápido ---
// Visual de prévia do design do estudante
Route::view('design', 'design.student-preview')->name('design.student.preview');
// Preview da dashboard estudantil para designers
Route::view('design/student-dashboard', 'student.dashboard')->name('design.student.dashboard');
// Visualização rápida da lista de cursos de um aluno
Route::view('design/student-courses', 'student.courses')->name('design.student.courses');

// --- Rotas de diagnóstico ---
// Verifica se o Ghostscript está disponível no container
Route::get('/test-gs', function () {
    return [
        'shell' => shell_exec('which gs'),
        'version' => shell_exec('gs --version'),
        'env_path' => getenv('PATH'),
    ];
});


// --- Rotas públicas ---
// Home pública
Route::get('/', HomeController::class)->name('home');

// Download do certificado publico
Route::get('/certificates/verify/{token}/download', [PublicCertificateController::class, 'download'])
    ->name('certificates.verify.download');
// Valida certificado publicado via token
Route::get('/certificates/verify/{token}', PublicCertificateController::class)->name('certificates.verify');
// Página pública do curso
Route::view('/catalogo', 'courses.catalog')->name('courses.public.index');
Route::get('/catalogo/{course:slug}', PublicCoursePageV4Controller::class)->name('courses.public.show');
Route::view('/catalogo-2', 'courses.catalog')->name('courses.public.v2.index');
Route::get('/catalogo-2/{course:slug}', PublicCoursePageV2Controller::class)->name('courses.public.v2.show');
Route::view('/catalogo-3', 'courses.catalog')->name('courses.public.v3.index');
Route::get('/catalogo-3/{course:slug}', PublicCoursePageV3Controller::class)->name('courses.public.v3.show');
Route::view('/catalogo-4', 'courses.catalog')->name('courses.public.v4.index');
Route::get('/catalogo-4/{course:slug}', PublicCoursePageV4Controller::class)->name('courses.public.v4.show');
Route::get('/cidade/{cidade}', CityCampaignController::class)->name('city.campaign.show');
Route::get('/cidade-2/{cidade}', CityCampaignV2Controller::class)->name('city.campaign.v2.show');
// Termos e condições
Route::view('/termos', 'legal.terms')->name('legal.terms');
// Política de privacidade
Route::view('/privacidade', 'legal.privacy')->name('legal.privacy');
// Acesso às aulas públicas (sem autenticação)
Route::get('/assistir/{course:slug}', [PublicLessonController::class, 'show'])->name('public.lessons.show');
// Envia OTP via WhatsApp (antes do logout removido)
Route::post('/assistir/whatsapp/enviar', [PublicLessonController::class, 'sendOtp'])->name('public.lessons.otp.send');
// Verifica o OTP do WhatsApp
Route::post('/assistir/whatsapp/confirmar', [PublicLessonController::class, 'verifyOtp'])->name('public.lessons.otp.verify');
// Marca aula como concluída para leads autenticados (público)
Route::post('/assistir/{course:slug}/aulas/{lesson}/concluir', [PublicLessonController::class, 'complete'])
    ->name('public.lessons.complete');

// --- Autenticação ---
// Formulário de login (visitante)
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    // Submissão do login
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

// Encerramento seguro da sessão
Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// --- Rotas protegidas (usuário autenticado) ---
Route::middleware('auth')->group(function (): void {
    Route::prefix('sa')
        ->name('sa.')
        ->middleware('super_admin')
        ->group(function (): void {
            Route::get('/', SuperAdminDashboardController::class)->name('dashboard');

            Route::get('tenants', [SuperAdminTenantController::class, 'index'])->name('tenants.index');
            Route::get('tenants/{id}/edit', [SuperAdminTenantController::class, 'edit'])->name('tenants.edit');
            Route::get('users', [SuperAdminUserController::class, 'index'])->name('users.index');
            Route::get('users/create', [SuperAdminUserController::class, 'create'])->name('users.create');
            Route::post('users', [SuperAdminUserController::class, 'store'])->name('users.store');
            Route::get('users/{id}/edit', [SuperAdminUserController::class, 'edit'])->name('users.edit');
            Route::put('users/{id}', [SuperAdminUserController::class, 'update'])->name('users.update');
            Route::delete('users/{id}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');

            Route::get('courses', [SuperAdminCourseController::class, 'index'])->name('courses.index');
            Route::get('courses/{id}/edit', [SuperAdminCourseController::class, 'edit'])->name('courses.edit');
            Route::put('courses/{id}', [SuperAdminCourseController::class, 'update'])->name('courses.update');
            Route::delete('courses/{id}', [SuperAdminCourseController::class, 'destroy'])->name('courses.destroy');

            Route::get('enrollments', [SuperAdminEnrollmentController::class, 'index'])->name('enrollments.index');
            Route::get('enrollments/{id}/edit', [SuperAdminEnrollmentController::class, 'edit'])->name('enrollments.edit');
            Route::put('enrollments/{id}', [SuperAdminEnrollmentController::class, 'update'])->name('enrollments.update');
            Route::delete('enrollments/{id}', [SuperAdminEnrollmentController::class, 'destroy'])->name('enrollments.destroy');
        });

    // Dashboard geral do usuário autenticado
    Route::get('/dashboard', DashboardController::class)->name('dashboard'); 
    Route::get('/certificado', [CertificadoController::class, 'index'])->name('certificado.index');
    // Edição do perfil do aluno
    Route::get('/conta', AccountProfileController::class)->name('account.edit');
    Route::post('/conta/exclusao', [AccountDeletionRequestController::class, 'store'])
        ->name('account.deletion-requests.store');

    Route::prefix('push')
        ->middleware('role:student')
        ->group(function (): void {
            Route::post('subscribe', [PushSubscriptionController::class, 'store'])
                ->name('push.subscribe');
            Route::delete('subscribe', [PushSubscriptionController::class, 'destroy'])
                ->name('push.unsubscribe');
        });

    Route::prefix('admin')
        ->middleware('role:admin')
        ->group(function (): void {
            Route::view('dashboard', 'dashboard.admin')->name('admin.dashboard');

            // Gerenciamento de cursos
            // Formulário de criação de curso
            Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
            // Persistência de novo curso
            Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
            // Edição de curso existente
            Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
            // Edita módulos do curso
            Route::get('courses/{course}/modules', [CourseController::class, 'editModules'])->name('courses.modules.edit'); 
            // Tela de teste final do curso
            Route::get('courses/{course}/final-test', [CourseController::class, 'editFinalTest'])->name('courses.final-test.edit');
            // Atualiza curso (POST porque usa formulário)
            Route::post('courses/{course}', [CourseController::class, 'update'])->name('courses.update.post');
            // Exclui curso
            Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

            // Administração e configurações globais
            // Editor de branding de certificados
            Route::view('certificates/branding', 'certificates.branding.edit')
                ->name('certificates.branding.edit');
            // Compatibilidade: identidade redireciona para configuracoes do sistema
            Route::get('identity', fn () => redirect()->route('admin.system.edit'))
                ->name('admin.identity');
            // Configuracoes gerais do sistema (assets + pixel)
            Route::view('system', 'admin.system.edit')
                ->name('admin.system.edit');
            // Listagem de usuários
            Route::get('users', [UserController::class, 'index'])
                ->name('admin.users.index');
            // Criar novo usuário via formulário
            Route::get('users/create', fn () => view('admin.users.create'))
                ->name('admin.users.create');
            // Editar usuário específico
            Route::get('users/{user}/edit', [UserController::class, 'edit'])
                ->name('admin.users.edit');
            // Atualizar dados do usuário
            Route::put('users/{user}', [UserController::class, 'update'])
                ->name('admin.users.update');
            // Categorias de cursos
            Route::get('categories', [CategoryController::class, 'index'])->name('admin.categories.index');
            Route::get('categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
            Route::post('categories', [CategoryController::class, 'store'])->name('admin.categories.store');
            Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
            // Lista de pagamentos de certificados
            Route::view('certificates/payments', 'admin.certificates.payments')
                ->name('admin.certificates.payments');
            // Lista de certificados gerados
            Route::get('certificados-gerados', [GeneratedCertificateController::class, 'index'])
                ->name('admin.certificates.generated.index');
            // Download do certificado gerado (admin)
            Route::get('certificados-gerados/{certificate}/download', [GeneratedCertificateController::class, 'download'])
                ->name('admin.certificates.generated.download');
            // Central de notificações administrativas
            Route::view('notifications', 'admin.notifications.index')
                ->name('admin.notifications.index');
            // Área administrativa Webhooks de pagamento
            Route::get('webhooks', [PaymentWebhookController::class, 'index'])->name('admin.webhooks.index');
            Route::get('webhooks/create', [PaymentWebhookController::class, 'create'])->name('admin.webhooks.create');
            Route::post('webhooks', [PaymentWebhookController::class, 'store'])->name('admin.webhooks.store');
            Route::get('webhooks/{webhookLink}/edit', [PaymentWebhookController::class, 'edit'])->name('admin.webhooks.edit');
            Route::put('webhooks/{webhookLink}', [PaymentWebhookController::class, 'update'])->name('admin.webhooks.update');
            Route::delete('webhooks/{webhookLink}', [PaymentWebhookController::class, 'destroy'])->name('admin.webhooks.destroy');
            Route::post('webhooks/{webhookLink}/simulate', [PaymentWebhookController::class, 'simulate'])->name('admin.webhooks.simulate');
            Route::post('webhooks/{webhookLink}/field-mappings', [PaymentWebhookController::class, 'upsertFieldMapping'])->name('admin.webhooks.field-mappings.upsert');
            Route::delete('webhooks/{webhookLink}/field-mappings/{mapping}', [PaymentWebhookController::class, 'removeFieldMapping'])->name('admin.webhooks.field-mappings.destroy');
            Route::post('webhooks/{webhookLink}/event-mappings', [PaymentWebhookController::class, 'upsertEventMapping'])->name('admin.webhooks.event-mappings.upsert');
            Route::delete('webhooks/{webhookLink}/event-mappings/{mapping}', [PaymentWebhookController::class, 'removeEventMapping'])->name('admin.webhooks.event-mappings.destroy');
            Route::post('webhooks/{webhookLink}/product-mappings', [PaymentWebhookController::class, 'upsertProductMapping'])->name('admin.webhooks.product-mappings.upsert');
            Route::delete('webhooks/{webhookLink}/product-mappings/{mapping}', [PaymentWebhookController::class, 'removeProductMapping'])->name('admin.webhooks.product-mappings.destroy');
            Route::get('webhooks/{webhookLink}/events', [PaymentWebhookController::class, 'events'])->name('admin.webhooks.events.index');
            Route::get('webhooks/{webhookLink}/events/{paymentEvent}', [PaymentWebhookController::class, 'showEvent'])->name('admin.webhooks.events.show');
            Route::post('webhooks/{webhookLink}/events/{paymentEvent}/replay', [PaymentWebhookController::class, 'replay'])->name('admin.webhooks.events.replay');
            // Números de WhatsApp para atendimento
            Route::get('whatsapp-atendimento', [SupportWhatsappNumberController::class, 'index'])->name('admin.support-whatsapp.index');
            Route::get('whatsapp-atendimento/create', [SupportWhatsappNumberController::class, 'create'])->name('admin.support-whatsapp.create');
            Route::post('whatsapp-atendimento', [SupportWhatsappNumberController::class, 'store'])->name('admin.support-whatsapp.store');
            Route::get('whatsapp-atendimento/{supportWhatsappNumber}/edit', [SupportWhatsappNumberController::class, 'edit'])->name('admin.support-whatsapp.edit');
            Route::put('whatsapp-atendimento/{supportWhatsappNumber}', [SupportWhatsappNumberController::class, 'update'])->name('admin.support-whatsapp.update');
            Route::delete('whatsapp-atendimento/{supportWhatsappNumber}', [SupportWhatsappNumberController::class, 'destroy'])->name('admin.support-whatsapp.destroy');
            // Administracao de matriculas
            Route::get('enroll', [EnrollmentController::class, 'index'])->name('admin.enroll.index');
            Route::get('enroll/create', [EnrollmentController::class, 'create'])->name('admin.enroll.create');
            Route::post('enroll', [EnrollmentController::class, 'store'])->name('admin.enroll.store');
            Route::get('enroll/{enrollment}/edit', [EnrollmentController::class, 'edit'])->name('admin.enroll.edit');
            Route::put('enroll/{enrollment}', [EnrollmentController::class, 'update'])->name('admin.enroll.update');
            Route::delete('enroll/{enrollment}', [EnrollmentController::class, 'destroy'])->name('admin.enroll.destroy');
            // Relatorio de tracking first-party (origens, funil, cliques)
            Route::view('tracking', 'admin.tracking.index')->name('admin.tracking.index');
            Route::get('tracking/export/origens', [TrackingReportExportController::class, 'sources'])
                ->name('admin.tracking.export.sources');
            Route::get('tracking/export/atribuicoes', [TrackingReportExportController::class, 'attributions'])
                ->name('admin.tracking.export.attributions');
            Route::get('account-deletion-requests', [AdminAccountDeletionRequestController::class, 'index'])
                ->name('admin.account-deletion-requests.index');
            Route::post('account-deletion-requests/{accountDeletionRequest}/destroy-account', [AdminAccountDeletionRequestController::class, 'destroyAccount'])
                ->name('admin.account-deletion-requests.destroy-account');
            Route::post('account-deletion-requests/{accountDeletionRequest}/reject', [AdminAccountDeletionRequestController::class, 'reject'])
                ->name('admin.account-deletion-requests.reject');
        });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('courses/create', fn () => redirect()->route('courses.create'));
        Route::get('courses/{course}/edit', fn ($course) => redirect()->route('courses.edit', $course));
        Route::get('courses/{course}/modules', fn ($course) => redirect()->route('courses.modules.edit', $course));
        Route::get('courses/{course}/final-test', fn ($course) => redirect()->route('courses.final-test.edit', $course));
        Route::get('certificates/branding', fn () => redirect()->route('certificates.branding.edit'));
    });

    Route::prefix('learning')
        ->middleware('role:student')
        ->name('learning.')
        ->group(function (): void {
            // Área do estudante
            // Redireciona o estudante para a próxima aula não concluída
            Route::get('courses/{course:slug}', [StudentCourseController::class, 'redirectToNextLesson'])
                ->name('courses.show');
            // Visualiza uma aula específica
            Route::get('courses/{course:slug}/lessons/{lesson}', [StudentCourseController::class, 'lesson'])
                ->name('courses.lessons.show');
            // Marca aula como concluída no fluxo do aluno
            Route::post('courses/{course:slug}/lessons/{lesson}/complete', [LessonProgressController::class, 'store'])
                ->name('courses.lessons.complete');

            // Introdução ao teste final
            Route::get('courses/{course:slug}/final-test', [StudentFinalTestController::class, 'intro'])
                ->name('courses.final-test.intro');
            // Inicia o teste final
            Route::post('courses/{course:slug}/final-test/start', [StudentFinalTestController::class, 'start'])
                ->name('courses.final-test.start');
            // Visualiza tentativa do teste final
            Route::get('courses/{course:slug}/final-test/attempt/{attempt}', [StudentFinalTestController::class, 'attempt'])
                ->name('courses.final-test.attempt');
            // Submete resposta do teste final
            Route::post('courses/{course:slug}/final-test/attempt/{attempt}', [StudentFinalTestController::class, 'submit'])
                ->name('courses.final-test.submit');
            // Lista de notificações do estudante
            Route::view('notifications', 'learning.notifications.index')
                ->name('notifications.index');

            // Visualiza certificado emitido
            Route::get('courses/{course:slug}/certificate/{certificate}', [CourseCertificateController::class, 'show'])
                ->name('courses.certificate.show');
            // Download do certificado do curso
            Route::get('courses/{course:slug}/certificate/{certificate}/download', [CourseCertificateController::class, 'download'])
                ->name('courses.certificate.download');
        });
});
