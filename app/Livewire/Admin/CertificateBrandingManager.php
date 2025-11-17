<?php

namespace App\Livewire\Admin;

use App\Models\CertificateBranding;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class CertificateBrandingManager extends Component
{
    use WithFileUploads;

    public ?CertificateBranding $branding = null;
    public SystemSetting $settings;
    public $front_background;
    public $back_background;
    public int $titleSize = 68;
    public int $subtitleSize = 52;
    public int $bodySize = 40;
    public string $line1;
    public string $line3;
    public string $line6;

    public function mount(): void
    {
        $this->branding = CertificateBranding::firstOrCreate(['course_id' => null]);
        $this->settings = SystemSetting::current();
        $this->titleSize = $this->settings->certificate_title_size ?? $this->titleSize;
        $this->subtitleSize = $this->settings->certificate_subtitle_size ?? $this->subtitleSize;
        $this->bodySize = $this->settings->certificate_body_size ?? $this->bodySize;
        $this->line1 = $this->settings->certificate_front_line1 ?? 'Certificamos que';
        $this->line3 = $this->settings->certificate_front_line3 ?? 'concluiu com 100% de aproveitamento o curso';
        $this->line6 = $this->settings->certificate_front_line6 ?? 'Com carga horária de {duration}, no período de {start} a {end}, promovido pelo portal de cursos EDUX.';
    }

    public function save(): void
    {
        $this->validate([
            'front_background' => ['nullable', 'image', 'max:4096'],
            'back_background' => ['nullable', 'image', 'max:4096'],
            'titleSize' => ['required', 'integer', 'between:12,120'],
            'subtitleSize' => ['required', 'integer', 'between:12,120'],
            'bodySize' => ['required', 'integer', 'between:10,120'],
            'line1' => ['required', 'string', 'max:255'],
            'line3' => ['required', 'string', 'max:255'],
            'line6' => ['required', 'string', 'max:500'],
        ]);

        $data = [];

        if ($this->front_background) {
            $this->deleteFile($this->branding->front_background_path);
            $data['front_background_path'] = $this->front_background->store('certificate-backgrounds', 'public');
        }

        if ($this->back_background) {
            $this->deleteFile($this->branding->back_background_path);
            $data['back_background_path'] = $this->back_background->store('certificate-backgrounds', 'public');
        }

        if ($data) {
            $this->branding->update($data);
            $this->branding->refresh();
            $this->reset(['front_background', 'back_background']);
        }

        $this->settings->update([
            'certificate_title_size' => $this->titleSize,
            'certificate_subtitle_size' => $this->subtitleSize,
            'certificate_body_size' => $this->bodySize,
            'certificate_front_line1' => $this->line1,
            'certificate_front_line3' => $this->line3,
            'certificate_front_line6' => $this->line6,
        ]);

        session()->flash('status', 'Modelos e textos atualizados.');
    }

    public function deleteFront(): void
    {
        if (! $this->branding->front_background_path) {
            return;
        }

        $this->deleteFile($this->branding->front_background_path);
        $this->branding->update(['front_background_path' => null]);
        $this->branding->refresh();
        $this->front_background = null;
    }

    public function deleteBack(): void
    {
        if (! $this->branding->back_background_path) {
            return;
        }

        $this->deleteFile($this->branding->back_background_path);
        $this->branding->update(['back_background_path' => null]);
        $this->branding->refresh();
        $this->back_background = null;
    }

    public function render()
    {
        return view('livewire.admin.certificate-branding-manager');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
