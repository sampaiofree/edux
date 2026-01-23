<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\KavooController;
use App\Http\Controllers\Admin\EnrollmentController;
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
// Redireciona para dashboard (apenas conveniência)
Route::redirect('/', '/dashboard');

// Valida certificado publicado via token
Route::get('/certificates/verify/{token}', PublicCertificateController::class)->name('certificates.verify');
// Página pública do curso
Route::view('/catalogo', 'courses.catalog')->name('courses.public.index');
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
    Route::get('/certificado', [CertificadoController::class, 'index'])->name('certificado.index');
    // Edição do perfil do aluno
    Route::view('/conta', 'account.profile')->name('account.edit');

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
            // Edita identidade institucional
            Route::get('identity', [SystemIdentityController::class, 'edit'])
                ->name('admin.identity');
            // Atualiza identidade
            Route::put('identity', [SystemIdentityController::class, 'update'])
                ->name('admin.identity.update');
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
            // Central de notificações administrativas
            Route::view('notifications', 'admin.notifications.index')
                ->name('admin.notifications.index');
            // Área administrativa Kavoo
            Route::get('kavoo', [KavooController::class, 'index'])->name('admin.kavoo.index');
            Route::get('kavoo/create', [KavooController::class, 'create'])->name('admin.kavoo.create');
            Route::post('kavoo', [KavooController::class, 'store'])->name('admin.kavoo.store');
            Route::get('kavoo/{kavoo}/edit', [KavooController::class, 'edit'])->name('admin.kavoo.edit');
            Route::put('kavoo/{kavoo}', [KavooController::class, 'update'])->name('admin.kavoo.update');
            Route::delete('kavoo/{kavoo}', [KavooController::class, 'destroy'])->name('admin.kavoo.destroy');
            // Administracao de matriculas
            Route::get('enroll', [EnrollmentController::class, 'index'])->name('admin.enroll.index');
            Route::get('enroll/create', [EnrollmentController::class, 'create'])->name('admin.enroll.create');
            Route::post('enroll', [EnrollmentController::class, 'store'])->name('admin.enroll.store');
            Route::get('enroll/{enrollment}/edit', [EnrollmentController::class, 'edit'])->name('admin.enroll.edit');
            Route::put('enroll/{enrollment}', [EnrollmentController::class, 'update'])->name('admin.enroll.update');
            Route::delete('enroll/{enrollment}', [EnrollmentController::class, 'destroy'])->name('admin.enroll.destroy');
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
