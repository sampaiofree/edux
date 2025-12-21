<?php

use App\Http\Controllers\Admin\SystemIdentityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\CourseCertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\PublicCertificateController;
use App\Http\Controllers\PublicCoursePageController;
use App\Http\Controllers\StudentCourseController;
use App\Http\Controllers\StudentFinalTestController;

use Illuminate\Support\Facades\Route;

Route::view('design', 'design.student-preview')->name('design.student.preview');
Route::view('design/student-dashboard', 'student.dashboard')->name('design.student.dashboard');
Route::view('design/student-courses', 'student.courses')->name('design.student.courses');


Route::redirect('/', '/dashboard');
Route::get('/certificado', [CertificadoController::class, 'index'])->name('certificado.index');
Route::get('/certificado/download', [CertificadoController::class, 'download'])->name('certificado.download');
Route::get('/certificates/verify/{token}', PublicCertificateController::class)->name('certificates.verify');
Route::get('/catalogo/{course:slug}', PublicCoursePageController::class)->name('courses.public.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard'); 
    Route::view('/conta', 'account.profile')->name('account.edit');

    Route::middleware('role:admin,teacher')->group(function (): void {
        Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::get('courses/{course}/modules', [CourseController::class, 'editModules'])->name('courses.modules.edit'); 
        Route::get('courses/{course}/final-test', [CourseController::class, 'editFinalTest'])->name('courses.final-test.edit');
        Route::post('courses/{course}', [CourseController::class, 'update'])->name('courses.update.post');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('admin/dux/rules', [\App\Http\Controllers\Admin\DuxRuleController::class, 'index'])->name('admin.dux.rules.index');
        Route::put('admin/dux/rules/{rule}', [\App\Http\Controllers\Admin\DuxRuleController::class, 'update'])->name('admin.dux.rules.update');

        Route::get('admin/dux/packs', [\App\Http\Controllers\Admin\DuxPackController::class, 'index'])->name('admin.dux.packs.index');
        Route::post('admin/dux/packs', [\App\Http\Controllers\Admin\DuxPackController::class, 'store'])->name('admin.dux.packs.store');
        Route::put('admin/dux/packs/{pack}', [\App\Http\Controllers\Admin\DuxPackController::class, 'update'])->name('admin.dux.packs.update');
        Route::delete('admin/dux/packs/{pack}', [\App\Http\Controllers\Admin\DuxPackController::class, 'destroy'])->name('admin.dux.packs.destroy');
        Route::view('certificates/branding', 'certificates.branding.edit')
            ->name('certificates.branding.edit');
        Route::get('admin/identity', [SystemIdentityController::class, 'edit'])
            ->name('admin.identity');
        Route::put('admin/identity', [SystemIdentityController::class, 'update'])
            ->name('admin.identity.update');
        Route::get('admin/users', [UserController::class, 'index'])
            ->name('admin.users.index');
        Route::get('admin/users/create', fn () => view('admin.users.create'))
            ->name('admin.users.create');
        Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])
            ->name('admin.users.edit');
        Route::put('admin/users/{user}', [UserController::class, 'update'])
            ->name('admin.users.update');
        Route::view('admin/professors/create', 'admin.users.create')
            ->name('admin.professors.create');
        Route::view('admin/certificates/payments', 'admin.certificates.payments')
            ->name('admin.certificates.payments');
        Route::view('admin/notifications', 'admin.notifications.index')
            ->name('admin.notifications.index');
    });

    Route::prefix('learning')
        ->middleware('role:student')
        ->name('learning.')
        ->group(function (): void {
            Route::get('courses/{course:slug}', [StudentCourseController::class, 'redirectToNextLesson'])
                ->name('courses.show');
            Route::post('courses/{course:slug}/enroll', [StudentCourseController::class, 'enroll'])
                ->name('courses.enroll');
            Route::get('courses/{course:slug}/lessons/{lesson}', [StudentCourseController::class, 'lesson'])
                ->name('courses.lessons.show');
            Route::post('courses/{course:slug}/lessons/{lesson}/complete', [LessonProgressController::class, 'store'])
                ->name('courses.lessons.complete');

            Route::get('courses/{course:slug}/final-test', [StudentFinalTestController::class, 'intro'])
                ->name('courses.final-test.intro');
            Route::post('courses/{course:slug}/final-test/start', [StudentFinalTestController::class, 'start'])
                ->name('courses.final-test.start');
            Route::get('courses/{course:slug}/final-test/attempt/{attempt}', [StudentFinalTestController::class, 'attempt'])
                ->name('courses.final-test.attempt');
            Route::post('courses/{course:slug}/final-test/attempt/{attempt}', [StudentFinalTestController::class, 'submit'])
                ->name('courses.final-test.submit');
            Route::view('notifications', 'learning.notifications.index')
                ->name('notifications.index');

            Route::get('courses/{course:slug}/certificate/{certificate}', [CourseCertificateController::class, 'show'])
                ->name('courses.certificate.show');
            Route::get('courses/{course:slug}/certificate/{certificate}/download', [CourseCertificateController::class, 'download'])
                ->name('courses.certificate.download');
        });
});
