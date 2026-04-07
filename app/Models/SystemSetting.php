<?php

namespace App\Models;

use App\Support\CityCampaignCache;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(static fn () => CityCampaignCache::bumpSettings());
        static::deleted(static fn () => CityCampaignCache::bumpSettings());
    }

    protected $fillable = [
        'owner_user_id',
        'domain',
        'mail_mailer',
        'mail_scheme',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_from_address',
        'mail_from_name',
        'escola_nome',
        'escola_cnpj',
        'meta_ads_pixel',
        'play_store_link',
        'apple_store_link',
        'force_app',
        'carta_estagio',
        'favicon_path',
        'default_logo_path',
        'default_logo_dark_path',
        'default_course_cover_path',
        'default_module_cover_path',
        'default_lesson_cover_path',
        'certificate_title_size',
        'certificate_subtitle_size',
        'certificate_body_size',
        'certificate_front_line1',
        'certificate_front_line3',
        'certificate_front_line6',
    ];

    protected function casts(): array
    {
        return [
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
            'force_app' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $setting = static::resolveCurrent();

        if ($setting) {
            return $setting;
        }

        abort(404, 'Sistema não encontrado para o domínio atual.');
    }

    public static function currentId(): ?int
    {
        return static::resolveCurrent()?->id;
    }

    public static function resolveCurrent(): ?self
    {
        if (! Schema::hasTable('system_settings')) {
            return null;
        }

        $host = static::requestHost();

        $fromDomain = static::forDomain($host);
        if ($fromDomain) {
            return $fromDomain;
        }

        $fromPlatformHost = static::forLocalPlatformHost($host);
        if ($fromPlatformHost) {
            return $fromPlatformHost;
        }

        $fromUser = static::forAuthenticatedUser();
        if ($fromUser) {
            return $fromUser;
        }

        return null;
    }

    public static function forDomain(?string $host): ?self
    {
        if (! static::isAllowedTenantDomain($host)) {
            return null;
        }

        $normalized = static::normalizeDomain($host);

        if ($normalized === null) {
            return null;
        }

        return static::query()
            ->where('domain', $normalized)
            ->first();
    }

    public static function forUser(?Authenticatable $user): ?self
    {
        if (! $user) {
            return null;
        }

        $systemSettingId = (int) ($user->system_setting_id ?? 0);

        if ($systemSettingId > 0) {
            return static::query()->find($systemSettingId);
        }

        return static::query()
            ->where('owner_user_id', $user->getAuthIdentifier())
            ->first();
    }

    public static function normalizeDomain(?string $value): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return null;
        }

        $candidate = trim(mb_strtolower($candidate, 'UTF-8'));

        return $candidate !== '' ? $candidate : null;
    }

    public static function tenantDomainValidationMessage(?string $value): ?string
    {
        return static::inspectTenantDomain($value)['error'];
    }

    public static function isAllowedTenantDomain(?string $value): bool
    {
        $inspection = static::inspectTenantDomain($value);

        return $inspection['normalized'] !== null && $inspection['error'] === null;
    }

    public static function defaultAppHost(): string
    {
        $host = static::hostFromValue(config('app.url'));

        return $host ?? 'localhost';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function paymentWebhookLinks(): HasMany
    {
        return $this->hasMany(PaymentWebhookLink::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function supportWhatsappNumbers(): HasMany
    {
        return $this->hasMany(SupportWhatsappNumber::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function certificateBrandings(): HasMany
    {
        return $this->hasMany(CertificateBranding::class);
    }

    public function appUrl(string $path = ''): string
    {
        $base = config('app.url');
        $scheme = parse_url((string) $base, PHP_URL_SCHEME) ?: 'https';
        $host = $this->domain ?: static::defaultAppHost();
        $normalizedPath = '/'.ltrim($path, '/');

        return rtrim("{$scheme}://{$host}", '/').($path === '' ? '' : $normalizedPath);
    }

    public function hasCustomMailConfiguration(): bool
    {
        return filled($this->mail_mailer);
    }

    public function resolvedMailFromAddress(): string
    {
        return trim((string) ($this->mail_from_address ?: config('mail.from.address', 'hello@example.com')));
    }

    public function resolvedMailFromName(): string
    {
        $schoolName = trim((string) ($this->escola_nome ?? ''));
        $fallback = trim((string) config('mail.from.name', config('app.name', 'Edux')));

        return trim((string) ($this->mail_from_name ?: ($schoolName !== '' ? $schoolName : $fallback)));
    }

    public function assetUrl(?string $column): ?string
    {
        if (! $column) {
            return null;
        }

        $path = $this->{$column};

        return $path ? Storage::disk('public')->url($path) : null;
    }

    private static function requestHost(): ?string
    {
        try {
            $request = request();
        } catch (\Throwable) {
            return null;
        }

        if (! $request) {
            return null;
        }

        return $request->getHost();
    }

    private static function forLocalPlatformHost(?string $host): ?self
    {
        if (! static::shouldUseLocalPlatformHostFallback($host)) {
            return null;
        }

        $ownedQuery = static::query()
            ->whereNotNull('owner_user_id')
            ->orderBy('id');

        if ($ownedQuery->count() === 1) {
            return $ownedQuery->first();
        }

        $allQuery = static::query()->orderBy('id');

        return $allQuery->count() === 1
            ? $allQuery->first()
            : null;
    }

    /**
     * @return array{normalized:?string,error:?string}
     */
    private static function inspectTenantDomain(?string $value): array
    {
        $raw = (string) $value;

        if ($raw === '') {
            return [
                'normalized' => null,
                'error' => null,
            ];
        }

        if (preg_match('/\s/u', $raw)) {
            return [
                'normalized' => null,
                'error' => 'O domínio não pode conter espaços.',
            ];
        }

        if (str_contains($raw, '://') || str_contains($raw, '/') || str_contains($raw, '?') || str_contains($raw, '#') || str_contains($raw, ':')) {
            return [
                'normalized' => null,
                'error' => 'Informe apenas o host, sem protocolo, porta ou caminho.',
            ];
        }

        $normalized = static::normalizeDomain($raw);

        if ($normalized === null) {
            return [
                'normalized' => null,
                'error' => 'Informe um host válido, como cursos.dominio.com.',
            ];
        }

        if (static::isLocalPlatformHost($normalized)) {
            return [
                'normalized' => $normalized,
                'error' => null,
            ];
        }

        if (! preg_match('/^[a-z0-9.-]+$/', $normalized)) {
            return [
                'normalized' => null,
                'error' => 'Use apenas letras, números, hífen e ponto no domínio.',
            ];
        }

        if (str_contains($normalized, '..') || str_starts_with($normalized, '.') || str_ends_with($normalized, '.')) {
            return [
                'normalized' => null,
                'error' => 'Informe um host válido, como cursos.dominio.com.',
            ];
        }

        $labels = explode('.', $normalized);

        if ($labels[0] !== 'cursos') {
            return [
                'normalized' => null,
                'error' => 'O domínio deve começar com cursos.',
            ];
        }

        if (count($labels) < 3) {
            return [
                'normalized' => null,
                'error' => 'Informe um subdomínio iniciando com cursos, como cursos.dominio.com.',
            ];
        }

        foreach ($labels as $label) {
            if (! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label)) {
                return [
                    'normalized' => null,
                    'error' => 'Informe um host válido, como cursos.dominio.com.',
                ];
            }
        }

        return [
            'normalized' => $normalized,
            'error' => null,
        ];
    }

    private static function hostFromValue(mixed $value): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return null;
        }

        if (str_contains($candidate, '://')) {
            $candidate = (string) parse_url($candidate, PHP_URL_HOST);
        } else {
            $candidate = preg_replace('#/.*$#', '', $candidate) ?? $candidate;
            $candidate = preg_replace('#:\\d+$#', '', $candidate) ?? $candidate;
        }

        return static::normalizeDomain($candidate);
    }

    private static function shouldUseLocalPlatformHostFallback(?string $host): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        $normalizedHost = static::normalizeDomain($host);

        return static::isLocalPlatformHost($normalizedHost);
    }

    private static function isLocalPlatformHost(?string $host): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        $normalizedHost = static::normalizeDomain($host);
        $platformHost = static::defaultAppHost();

        return $normalizedHost !== null
            && $platformHost !== ''
            && $normalizedHost === $platformHost;
    }

    private static function forAuthenticatedUser(): ?self
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        $systemSettingId = DB::table('users')
            ->where('id', $userId)
            ->value('system_setting_id');

        if ($systemSettingId) {
            return static::query()->find($systemSettingId);
        }

        return static::query()
            ->where('owner_user_id', $userId)
            ->first();
    }
}
