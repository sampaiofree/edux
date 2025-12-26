<?php

use App\Http\Controllers\Admin\SystemIdentityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\CourseCertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\PublicLessonController;
use App\Http\Controllers\PublicCertificateController;
use App\Http\Controllers\PublicCoursePageController;
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
// Redireciona para dashboard (apenas conveniência)
Route::redirect('/', '/dashboard');
// Visualiza página do certificado (sem autenticação)
Route::get('/certificado', [CertificadoController::class, 'index'])->name('certificado.index');
// Download público do certificado
Route::get('/certificado/download', [CertificadoController::class, 'download'])->name('certificado.download');
// Valida certificado publicado via token
Route::get('/certificates/verify/{token}', PublicCertificateController::class)->name('certificates.verify');
// Página pública do curso
Route::get('/catalogo/{course:slug}', PublicCoursePageController::class)->name('courses.public.show');
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
    // Dashboard geral do usuário autenticado
    Route::get('/dashboard', DashboardController::class)->name('dashboard'); 
    // Edição do perfil do aluno
    Route::view('/conta', 'account.profile')->name('account.edit');

    Route::middleware('role:admin,teacher')->group(function (): void {
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

    });

    Route::middleware('role:admin')->group(function (): void {
        // Administração e configurações globais
        // Listagem de regras DUX
        Route::get('admin/dux/rules', [\App\Http\Controllers\Admin\DuxRuleController::class, 'index'])->name('admin.dux.rules.index');
        // Atualiza regra DUX específica
        Route::put('admin/dux/rules/{rule}', [\App\Http\Controllers\Admin\DuxRuleController::class, 'update'])->name('admin.dux.rules.update');

        // Listagem de pacotes DUX
        Route::get('admin/dux/packs', [\App\Http\Controllers\Admin\DuxPackController::class, 'index'])->name('admin.dux.packs.index');
        // Cria novo pacote DUX
        Route::post('admin/dux/packs', [\App\Http\Controllers\Admin\DuxPackController::class, 'store'])->name('admin.dux.packs.store');
        // Atualiza pacote DUX
        Route::put('admin/dux/packs/{pack}', [\App\Http\Controllers\Admin\DuxPackController::class, 'update'])->name('admin.dux.packs.update');
        // Remove pacote DUX
        Route::delete('admin/dux/packs/{pack}', [\App\Http\Controllers\Admin\DuxPackController::class, 'destroy'])->name('admin.dux.packs.destroy');
        // Editor de branding de certificados
        Route::view('certificates/branding', 'certificates.branding.edit')
            ->name('certificates.branding.edit');
        // Edita identidade institucional
        Route::get('admin/identity', [SystemIdentityController::class, 'edit'])
            ->name('admin.identity');
        // Atualiza identidade
        Route::put('admin/identity', [SystemIdentityController::class, 'update'])
            ->name('admin.identity.update');
        // Listagem de usuários
        Route::get('admin/users', [UserController::class, 'index'])
            ->name('admin.users.index');
        // Criar novo usuário via formulário
        Route::get('admin/users/create', fn () => view('admin.users.create'))
            ->name('admin.users.create');
        // Editar usuário específico
        Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])
            ->name('admin.users.edit');
        // Atualizar dados do usuário
        Route::put('admin/users/{user}', [UserController::class, 'update'])
            ->name('admin.users.update');
        // Formulário de criação de professores (reusa layout de usuários)
        Route::view('admin/professors/create', 'admin.users.create')
            ->name('admin.professors.create');
        // Lista de pagamentos de certificados
        Route::view('admin/certificates/payments', 'admin.certificates.payments')
            ->name('admin.certificates.payments');
        // Central de notificações administrativas
        Route::view('admin/notifications', 'admin.notifications.index')
            ->name('admin.notifications.index');
    });

    Route::prefix('learning')
        ->middleware('role:student')
        ->name('learning.')
        ->group(function (): void {
            // Área do estudante
            // Redireciona o estudante para a próxima aula não concluída
            Route::get('courses/{course:slug}', [StudentCourseController::class, 'redirectToNextLesson'])
                ->name('courses.show');
            // Matrícula de estudante no curso
            Route::post('courses/{course:slug}/enroll', [StudentCourseController::class, 'enroll'])
                ->name('courses.enroll');
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
