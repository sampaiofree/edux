<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Mail\SystemMailTestMessage;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use App\Support\OneSignal\OneSignalPushService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class SystemAssetsManager extends Component
{
    use WithFileUploads;

    public SystemSetting $settings;

    public ?int $systemSettingId = null;

    public ?string $domain = null;

    public ?string $escola_nome = null;

    public ?string $escola_cnpj = null;

    public ?string $owner_user_id = null;

    public ?string $meta_ads_pixel = null;

    public ?string $mail_mailer = null;

    public ?string $mail_scheme = null;

    public ?string $mail_host = null;

    public ?string $mail_port = null;

    public ?string $mail_username = null;

    public ?string $mail_password = null;

    public ?string $mail_from_address = null;

    public ?string $mail_from_name = null;

    public bool $mail_password_configured = false;

    public ?string $onesignal_app_id = null;

    public ?string $onesignal_rest_api_key = null;

    public bool $onesignal_rest_api_key_configured = false;

    public ?string $test_email = null;

    public ?string $test_push_user_id = null;

    public bool $editingExplicitTenant = false;

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
            'domain' => $this->domainRules(),
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

    public function mount(?int $systemSettingId = null): void
    {
        $this->systemSettingId = $systemSettingId;
        $this->editingExplicitTenant = $systemSettingId !== null;
        $this->settings = $this->resolveSettings($systemSettingId);

        $this->syncStateFromSettings();
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
        $rules = [
            'domain' => $this->domainRules(),
            'escola_nome' => ['nullable', 'string', 'max:255'],
            'escola_cnpj' => ['nullable', 'string', 'max:32'],
        ];

        if ($this->editingExplicitTenant) {
            $rules['owner_user_id'] = $this->ownerRules();
        }

        $this->validate($rules);

        $domain = SystemSetting::normalizeDomain($this->domain);
        $escolaNome = trim((string) ($this->escola_nome ?? ''));
        $escolaCnpj = trim((string) ($this->escola_cnpj ?? ''));

        $attributes = [
            'domain' => $domain,
            'escola_nome' => $escolaNome !== '' ? $escolaNome : null,
            'escola_cnpj' => $escolaCnpj !== '' ? $escolaCnpj : null,
        ];

        if ($this->editingExplicitTenant) {
            $attributes['owner_user_id'] = $this->normalizedOwnerUserId();
        }

        $this->settings->update($attributes);

        $this->settings->refresh();
        $this->syncStateFromSettings();

        $message = 'Dados institucionais atualizados.';
        session()->flash('status_school_identity', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function saveMailSettings(): void
    {
        $validated = $this->validate($this->mailRules());

        $mailMailer = $this->normalizeOptional($validated['mail_mailer'] ?? null);
        $mailScheme = $this->normalizeMailScheme($validated['mail_scheme'] ?? null);
        $mailHost = $this->normalizeOptional($validated['mail_host'] ?? null);
        $mailPort = $this->normalizeOptional($validated['mail_port'] ?? null);
        $mailUsername = $this->normalizeOptional($validated['mail_username'] ?? null);
        $mailPassword = $this->normalizeOptional($validated['mail_password'] ?? null);
        $mailFromAddress = $this->normalizeOptional($validated['mail_from_address'] ?? null);
        $mailFromName = $this->normalizeOptional($validated['mail_from_name'] ?? null);

        $attributes = [
            'mail_mailer' => $mailMailer,
            'mail_scheme' => $mailScheme,
            'mail_host' => $mailHost,
            'mail_port' => $mailPort !== null ? (int) $mailPort : null,
            'mail_username' => $mailUsername,
            'mail_from_address' => $mailFromAddress,
            'mail_from_name' => $mailFromName,
        ];

        if ($mailPassword !== null) {
            $attributes['mail_password'] = $mailPassword;
        }

        $this->settings->update($attributes);
        $this->settings->refresh();
        $this->syncStateFromSettings(resetTestEmail: false);

        $message = 'Configurações de e-mail atualizadas.';
        session()->flash('status_mail_settings', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function sendTestEmail(): void
    {
        $this->validate(array_merge($this->mailRules(), [
            'test_email' => ['required', 'email', 'max:255'],
        ]));

        $systemSetting = $this->mailPreviewSystemSetting();
        $recipient = trim((string) $this->test_email);

        try {
            app(TenantMailManager::class)->send(
                $systemSetting,
                $recipient,
                new SystemMailTestMessage($systemSetting)
            );
        } catch (\Throwable $exception) {
            $message = 'Falha ao enviar e-mail de teste: '.$exception->getMessage();
            session()->flash('status_mail_test', $message);
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $message = 'E-mail de teste enviado para '.$recipient.'.';
        session()->flash('status_mail_test', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function saveOneSignalSettings(): void
    {
        [$appId, $restApiKey, $clearCredentials] = $this->validatedOneSignalSettingsForSave();

        $attributes = [
            'onesignal_app_id' => $appId,
        ];

        if ($clearCredentials) {
            $attributes['onesignal_rest_api_key'] = null;
        } elseif ($restApiKey !== null) {
            $attributes['onesignal_rest_api_key'] = $restApiKey;
        }

        $this->settings->update($attributes);
        $this->settings->refresh();
        $this->syncStateFromSettings(resetTestEmail: false);

        $message = 'Configurações de push atualizadas.';
        session()->flash('status_onesignal_settings', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function sendTestPush(): void
    {
        $previewSetting = $this->oneSignalPreviewSystemSetting();
        $student = $this->selectedTestPushStudent();

        if (! $student) {
            throw ValidationException::withMessages([
                'test_push_user_id' => 'Selecione um aluno da escola para enviar o push de teste.',
            ]);
        }

        try {
            app(OneSignalPushService::class)->sendTestPush($previewSetting, $student);
        } catch (\Throwable $exception) {
            $message = 'Falha ao enviar push de teste: '.$exception->getMessage();
            session()->flash('status_onesignal_test', $message);
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $message = 'Push de teste enviado para '.$student->name.'.';
        session()->flash('status_onesignal_test', $message);
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
            'isSuperAdminContext' => $this->editingExplicitTenant,
            'ownerOptions' => $this->ownerOptions(),
            'studentOptions' => $this->studentOptions(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function mailRules(): array
    {
        $requiresCustomConfig = fn (): bool => filled($this->normalizeOptional($this->mail_mailer));
        $usesSmtp = fn (): bool => $this->normalizeOptional($this->mail_mailer) === 'smtp';

        return [
            'mail_mailer' => ['nullable', Rule::in(['log', 'smtp'])],
            'mail_scheme' => ['nullable', Rule::in(['smtp', 'smtps', 'tls', 'ssl', 'starttls'])],
            'mail_host' => ['nullable', Rule::requiredIf($usesSmtp), 'string', 'max:255'],
            'mail_port' => ['nullable', Rule::requiredIf($usesSmtp), 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'mail_from_address' => ['nullable', Rule::requiredIf($requiresCustomConfig), 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function normalizeOptional(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array<int, mixed>
     */
    private function domainRules(): array
    {
        return [
            'required',
            'string',
            'max:191',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $message = SystemSetting::tenantDomainValidationMessage(is_string($value) ? $value : null);

                if ($message !== null) {
                    $fail($message);
                }
            },
            Rule::unique('system_settings', 'domain')->ignore($this->settings->id),
        ];
    }

    private function mailPreviewSystemSetting(): SystemSetting
    {
        $mailPassword = $this->normalizeOptional($this->mail_password);
        $systemSetting = clone $this->settings;
        $domain = SystemSetting::isAllowedTenantDomain($this->domain)
            ? SystemSetting::normalizeDomain($this->domain)
            : $this->settings->domain;

        $systemSetting->forceFill([
            'domain' => $domain,
            'mail_mailer' => $this->normalizeOptional($this->mail_mailer),
            'mail_scheme' => $this->normalizeMailScheme($this->mail_scheme),
            'mail_host' => $this->normalizeOptional($this->mail_host),
            'mail_port' => ($port = $this->normalizeOptional($this->mail_port)) !== null ? (int) $port : null,
            'mail_username' => $this->normalizeOptional($this->mail_username),
            'mail_password' => $mailPassword ?? $this->settings->mail_password,
            'mail_from_address' => $this->normalizeOptional($this->mail_from_address),
            'mail_from_name' => $this->normalizeOptional($this->mail_from_name),
            'escola_nome' => $this->normalizeOptional($this->escola_nome) ?? $this->settings->escola_nome,
        ]);

        return $systemSetting;
    }

    private function normalizeMailScheme(mixed $value): ?string
    {
        $normalized = $this->normalizeOptional($value);

        return $normalized !== null ? strtolower($normalized) : null;
    }

    /**
     * @return array{0:?string,1:?string,2:bool}
     */
    private function validatedOneSignalSettingsForSave(): array
    {
        $validated = $this->validate($this->oneSignalRules());

        $appId = $this->normalizeOptional($validated['onesignal_app_id'] ?? null);
        $restApiKey = $this->normalizeOptional($validated['onesignal_rest_api_key'] ?? null);
        $clearCredentials = $appId === null && $restApiKey === null;

        if ($appId === null && $restApiKey !== null) {
            throw ValidationException::withMessages([
                'onesignal_app_id' => 'Informe o App ID do OneSignal para salvar a chave REST.',
            ]);
        }

        if ($appId !== null && $restApiKey === null && ! $this->onesignal_rest_api_key_configured) {
            throw ValidationException::withMessages([
                'onesignal_rest_api_key' => 'Informe a chave REST do OneSignal para concluir a configuração.',
            ]);
        }

        return [$appId, $restApiKey, $clearCredentials];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function oneSignalRules(): array
    {
        return [
            'onesignal_app_id' => ['nullable', 'uuid'],
            'onesignal_rest_api_key' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function oneSignalPreviewSystemSetting(): SystemSetting
    {
        $validated = $this->validate(array_merge($this->oneSignalRules(), [
            'test_push_user_id' => ['required', 'integer'],
        ]));

        $appId = $this->normalizeOptional($validated['onesignal_app_id'] ?? null) ?? $this->settings->onesignal_app_id;
        $restApiKey = $this->normalizeOptional($validated['onesignal_rest_api_key'] ?? null) ?? $this->settings->onesignal_rest_api_key;

        if ($appId === null) {
            throw ValidationException::withMessages([
                'onesignal_app_id' => 'Informe o App ID do OneSignal antes de enviar um push de teste.',
            ]);
        }

        if ($restApiKey === null) {
            throw ValidationException::withMessages([
                'onesignal_rest_api_key' => 'Informe a chave REST do OneSignal antes de enviar um push de teste.',
            ]);
        }

        $systemSetting = clone $this->settings;
        $systemSetting->forceFill([
            'onesignal_app_id' => $appId,
            'onesignal_rest_api_key' => $restApiKey,
        ]);

        return $systemSetting;
    }

    private function resolveSettings(?int $systemSettingId): SystemSetting
    {
        if ($systemSettingId !== null) {
            abort_unless(auth()->user()?->isSuperAdmin(), 403);

            return SystemSetting::query()->findOrFail($systemSettingId);
        }

        return SystemSetting::current();
    }

    private function syncStateFromSettings(bool $resetTestEmail = true): void
    {
        $this->settings->refresh();
        $this->domain = $this->settings->domain;
        $this->escola_nome = $this->settings->escola_nome;
        $this->escola_cnpj = $this->settings->escola_cnpj;
        $this->owner_user_id = $this->settings->owner_user_id !== null ? (string) $this->settings->owner_user_id : null;
        $this->meta_ads_pixel = $this->settings->meta_ads_pixel;
        $this->mail_mailer = $this->settings->mail_mailer;
        $this->mail_scheme = $this->settings->mail_scheme;
        $this->mail_host = $this->settings->mail_host;
        $this->mail_port = $this->settings->mail_port ? (string) $this->settings->mail_port : null;
        $this->mail_username = $this->settings->mail_username;
        $this->mail_from_address = $this->settings->mail_from_address;
        $this->mail_from_name = $this->settings->mail_from_name;
        $this->mail_password = null;
        $this->mail_password_configured = filled($this->settings->getRawOriginal('mail_password'));
        $this->onesignal_app_id = $this->settings->onesignal_app_id;
        $this->onesignal_rest_api_key = null;
        $this->onesignal_rest_api_key_configured = filled($this->settings->getRawOriginal('onesignal_rest_api_key'));
        $this->test_push_user_id = null;

        if ($resetTestEmail || blank($this->test_email)) {
            $this->test_email = (string) (auth()->user()?->email ?? '');
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function ownerRules(): array
    {
        return [
            'nullable',
            'integer',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                $ownerId = (int) $value;

                if ($ownerId === (int) ($this->settings->owner_user_id ?? 0)) {
                    return;
                }

                $owner = User::withoutGlobalScopes()->find($ownerId);

                if (! $owner
                    || (string) ($owner->role->value ?? $owner->role) !== UserRole::ADMIN->value
                    || (int) ($owner->system_setting_id ?? 0) !== (int) $this->settings->id
                ) {
                    $fail('Selecione um administrador da mesma escola para ser o responsável.');
                }
            },
        ];
    }

    private function normalizedOwnerUserId(): ?int
    {
        $ownerId = $this->normalizeOptional($this->owner_user_id);

        return $ownerId !== null ? (int) $ownerId : null;
    }

    private function ownerOptions()
    {
        return User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN->value)
            ->where('system_setting_id', $this->settings->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'system_setting_id']);
    }

    private function studentOptions()
    {
        return User::withoutGlobalScopes()
            ->where('role', UserRole::STUDENT->value)
            ->where('system_setting_id', $this->settings->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'system_setting_id']);
    }

    private function selectedTestPushStudent(): ?User
    {
        $studentId = $this->normalizeOptional($this->test_push_user_id);

        if ($studentId === null) {
            return null;
        }

        return User::withoutGlobalScopes()
            ->whereKey((int) $studentId)
            ->where('role', UserRole::STUDENT->value)
            ->where('system_setting_id', $this->settings->id)
            ->first();
    }
}
