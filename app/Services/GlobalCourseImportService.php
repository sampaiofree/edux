<?php

namespace App\Services;

use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GlobalCourseImportService
{
    public function import(Course $sourceCourse, User $destinationAdmin): Course
    {
        $destinationSystemSettingId = (int) ($destinationAdmin->adminContextSystemSettingId() ?? 0);

        if ($destinationSystemSettingId < 1) {
            throw new RuntimeException('Administrador sem tenant válido para importar cursos.');
        }

        $sourceCourse = Course::withoutGlobalScopes()
            ->with([
                'modules.lessons',
                'finalTest.questions.options',
            ])
            ->whereKey($sourceCourse->id)
            ->firstOrFail();

        $sourceCourse->setRelation(
            'certificateBranding',
            CertificateBranding::withoutGlobalScopes()
                ->where('course_id', $sourceCourse->id)
                ->first()
        );

        if (! $sourceCourse->is_global) {
            throw new RuntimeException('Apenas cursos globais podem ser importados.');
        }

        return DB::transaction(function () use ($destinationAdmin, $destinationSystemSettingId, $sourceCourse): Course {
            $importedCourse = Course::create([
                'system_setting_id' => $destinationSystemSettingId,
                'owner_id' => $destinationAdmin->id,
                'title' => $sourceCourse->title,
                'slug' => $this->generateUniqueSlug($sourceCourse->title),
                'summary' => $sourceCourse->summary,
                'description' => $sourceCourse->description,
                'atuacao' => $sourceCourse->atuacao,
                'oquefaz' => $sourceCourse->oquefaz,
                'cover_image_path' => $this->duplicatePublicFile($sourceCourse->cover_image_path, 'course-covers'),
                'promo_video_url' => $sourceCourse->promo_video_url,
                'status' => 'draft',
                'duration_minutes' => $sourceCourse->duration_minutes,
                'published_at' => null,
                'kavoo_id' => null,
                'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
                'support_whatsapp_number_id' => null,
                'is_global' => false,
            ]);

            $this->cloneModulesAndLessons($sourceCourse, $importedCourse);
            $this->cloneFinalTest($sourceCourse, $importedCourse);
            $this->cloneCertificateBranding($sourceCourse, $importedCourse);

            return $importedCourse->fresh([
                'modules.lessons',
                'finalTest.questions.options',
                'certificateBranding',
            ]) ?? $importedCourse;
        });
    }

    private function cloneModulesAndLessons(Course $sourceCourse, Course $importedCourse): void
    {
        foreach ($sourceCourse->modules as $sourceModule) {
            $importedModule = $importedCourse->modules()->create([
                'title' => $sourceModule->title,
                'description' => $sourceModule->description,
                'position' => $sourceModule->position,
            ]);

            foreach ($sourceModule->lessons as $sourceLesson) {
                $importedModule->lessons()->create([
                    'title' => $sourceLesson->title,
                    'content' => $sourceLesson->content,
                    'video_url' => $sourceLesson->video_url,
                    'duration_minutes' => $sourceLesson->duration_minutes,
                    'position' => $sourceLesson->position,
                ]);
            }
        }
    }

    private function cloneFinalTest(Course $sourceCourse, Course $importedCourse): void
    {
        if (! $sourceCourse->finalTest) {
            return;
        }

        $importedFinalTest = $importedCourse->finalTest()->create([
            'title' => $sourceCourse->finalTest->title,
            'instructions' => $sourceCourse->finalTest->instructions,
            'passing_score' => $sourceCourse->finalTest->passing_score,
            'max_attempts' => $sourceCourse->finalTest->max_attempts,
            'duration_minutes' => $sourceCourse->finalTest->duration_minutes,
        ]);

        foreach ($sourceCourse->finalTest->questions as $sourceQuestion) {
            $importedQuestion = $importedFinalTest->questions()->create([
                'title' => $sourceQuestion->title,
                'statement' => $sourceQuestion->statement,
                'position' => $sourceQuestion->position,
                'weight' => $sourceQuestion->weight,
            ]);

            foreach ($sourceQuestion->options as $sourceOption) {
                $importedQuestion->options()->create([
                    'label' => $sourceOption->label,
                    'is_correct' => $sourceOption->is_correct,
                    'position' => $sourceOption->position,
                ]);
            }
        }
    }

    private function cloneCertificateBranding(Course $sourceCourse, Course $importedCourse): void
    {
        if (! $sourceCourse->certificateBranding) {
            return;
        }

        $importedCourse->certificateBranding()->create([
            'front_background_path' => $this->duplicatePublicFile(
                $sourceCourse->certificateBranding->front_background_path,
                'certificate-backgrounds'
            ),
            'back_background_path' => $this->duplicatePublicFile(
                $sourceCourse->certificateBranding->back_background_path,
                'certificate-backgrounds'
            ),
        ]);
    }

    private function duplicatePublicFile(?string $sourcePath, string $directory): ?string
    {
        if (! filled($sourcePath)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetPath = trim($directory, '/').'/'.Str::uuid().($extension !== '' ? '.'.$extension : '');

        $disk->copy($sourcePath, $targetPath);

        return $targetPath;
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'curso';
        $slug = $baseSlug;
        $counter = 1;

        while (Course::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
