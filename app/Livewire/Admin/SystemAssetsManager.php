<?php

namespace App\Livewire\Admin;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SystemAssetsManager extends Component
{
    use WithFileUploads;

    public SystemSetting $settings;
    public ?string $escola_nome = null;
    public ?string $escola_cnpj = null;
    public ?string $meta_ads_pixel = null;

    /** @var array<string, mixed> */
    public array $uploads = [
        'favicon' => null,
        'logo' => null,
        'logo_dark' => null,
        'course' => null,
        'module' => null,
        'lesson' => null,
        'carta_estagio' => null,
    ];

    protected array $fieldMap = [
        'favicon' => [
            'column' => 'favicon_path',
            'label' => 'Favicon',
            'hint' => 'PNG/SVG até 256 KB',
        ],
        'logo' => [
            'column' => 'default_logo_path',
            'label' => 'Logo padrão',
            'hint' => 'PNG transparente até 512 KB',
        ],
        'logo_dark' => [
            'column' => 'default_logo_dark_path',
            'label' => 'Logo (versão dark)',
            'hint' => 'PNG branco translúcido para fundos escuros',
        ],
        'course' => [
            'column' => 'default_course_cover_path',
            'label' => 'Imagem padrão do curso',
            'hint' => 'Sugestão 1280x720 px',
        ],
        'module' => [
            'column' => 'default_module_cover_path',
            'label' => 'Imagem padrão do módulo',
            'hint' => 'Sugestão 800x400 px',
        ],
        'lesson' => [
            'column' => 'default_lesson_cover_path',
            'label' => 'Imagem padrão da aula',
            'hint' => 'Sugestão 800x400 px',
        ],
        'carta_estagio' => [
            'column' => 'carta_estagio',
            'label' => 'Carta de estagio (modelo)',
            'hint' => 'Upload em WEBP para uso na LP do curso',
        ],
    ];

    protected function rules(): array
    {
        return [
            'escola_nome' => ['nullable', 'string', 'max:255'],
            'escola_cnpj' => ['nullable', 'string', 'max:32'],
            'meta_ads_pixel' => ['nullable', 'string', 'max:64'],
            'uploads.favicon' => ['nullable', 'image', 'max:256'],
            'uploads.logo' => ['nullable', 'image', 'max:512'],
            'uploads.logo_dark' => ['nullable', 'image', 'max:512'],
            'uploads.course' => ['nullable', 'image', 'max:1024'],
            'uploads.module' => ['nullable', 'image', 'max:1024'],
            'uploads.lesson' => ['nullable', 'image', 'max:1024'],
            'uploads.carta_estagio' => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:4096'],
        ];
    }

    public function mount(): void
    {
        $this->settings = SystemSetting::current();
        $this->escola_nome = $this->settings->escola_nome;
        $this->escola_cnpj = $this->settings->escola_cnpj;
        $this->meta_ads_pixel = $this->settings->meta_ads_pixel;
    }

    public function updatedUploads(): void
    {
        // Reseta mensagens de sucesso sempre que novo arquivo é escolhido.
        session()->forget('status');
    }

    public function saveMetaAdsPixel(): void
    {
        $this->validateOnly('meta_ads_pixel');

        $normalized = preg_replace('/\D+/', '', (string) $this->meta_ads_pixel);
        $value = $normalized !== '' ? $normalized : null;

        $this->settings->update([
            'meta_ads_pixel' => $value,
        ]);

        $this->meta_ads_pixel = $this->settings->fresh()->meta_ads_pixel;

        $message = 'Meta Ads Pixel atualizado.';
        session()->flash('status', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function saveSchoolIdentity(): void
    {
        $this->validate([
            'escola_nome' => ['nullable', 'string', 'max:255'],
            'escola_cnpj' => ['nullable', 'string', 'max:32'],
        ]);

        $escolaNome = trim((string) ($this->escola_nome ?? ''));
        $escolaCnpj = trim((string) ($this->escola_cnpj ?? ''));

        $this->settings->update([
            'escola_nome' => $escolaNome !== '' ? $escolaNome : null,
            'escola_cnpj' => $escolaCnpj !== '' ? $escolaCnpj : null,
        ]);

        $this->settings->refresh();
        $this->escola_nome = $this->settings->escola_nome;
        $this->escola_cnpj = $this->settings->escola_cnpj;

        $message = 'Dados institucionais atualizados.';
        session()->flash('status_school_identity', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function save(string $field): void
    {
        if (! array_key_exists($field, $this->fieldMap)) {
            return;
        }

        $this->resetErrorBag("uploads.$field");

        $this->validateOnly("uploads.$field");

        $file = $this->uploads[$field];

        if (! $file) {
            $message = 'Selecione um arquivo para continuar.';
            session()->flash('status', $message);
            $this->dispatch('notify', type: 'error', message: $message);
            return;
        }

        $column = $this->fieldMap[$field]['column'];

        if ($old = $this->settings->{$column}) {
            Storage::disk('public')->delete($old);
        }

        // Garantir diretório e guardar em disco público
        Storage::disk('public')->makeDirectory('system-assets');
        $path = $file->store('system-assets', 'public');
        $this->settings->update([$column => $path]);
        // Atualiza o valor em memória para refletir no preview imediatamente.
        $this->settings->{$column} = $path;

        $this->uploads[$field] = null;

        $successMessage = $this->fieldMap[$field]['label'].' atualizado.';
        session()->flash("status_{$field}", $successMessage);
        $this->dispatch('notify', type: 'success', message: $successMessage);

        logger()->info('system-assets upload', [
            'field' => $field,
            'column' => $column,
            'stored_as' => $path,
            'original' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.system-assets-manager', [
            'fields' => $this->fieldMap,
            'settings' => $this->settings,
        ]);
    }
}
