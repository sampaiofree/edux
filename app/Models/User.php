<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\BelongsToSystemSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BelongsToSystemSetting;

    use HasFactory, Notifiable;

    private const BOOTSTRAP_SUPER_ADMIN_EMAIL = 'sampaio.free@gmail.com';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'display_name',
        'password',
        'role',
        'system_setting_id',
        'whatsapp',
        'qualification',
        'profile_photo_path',
        'name_change_available',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'name_change_available' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            if (! $user->isAdmin()) {
                return;
            }

            if ($user->system_setting_id) {
                $setting = $user->systemSetting()->withoutGlobalScopes()->first();

                if ($setting && ! $setting->owner_user_id) {
                    $setting->forceFill([
                        'owner_user_id' => $user->id,
                    ])->save();
                }

                return;
            }

            $setting = SystemSetting::query()
                ->whereNull('owner_user_id')
                ->orderBy('id')
                ->first();

            if (! $setting) {
                $setting = SystemSetting::create([
                    'owner_user_id' => $user->id,
                ]);
            } else {
                $setting->forceFill([
                    'owner_user_id' => $setting->owner_user_id ?: $user->id,
                    'domain' => null,
                ])->save();
            }

            $user->forceFill([
                'system_setting_id' => $setting->id,
            ])->saveQuietly();
        });
    }

    protected function resolveSystemSettingIdForNewRecord(): ?int
    {
        $role = $this->getAttribute('role');
        $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

        if ($roleValue === UserRole::ADMIN->value) {
            $host = null;

            try {
                $host = request()->getHost();
            } catch (\Throwable) {
                $host = null;
            }

            $fromDomain = SystemSetting::forDomain($host);
            if ($fromDomain) {
                return $fromDomain->id;
            }

            $fromUser = SystemSetting::forUser(auth()->user());

            return $fromUser?->id;
        }

        return SystemSetting::currentId();
    }

    /**
     * @return list<string>
     */
    public static function configuredSuperAdminEmails(): array
    {
        return collect([
            ...config('auth.super_admin_emails', []),
            self::BOOTSTRAP_SUPER_ADMIN_EMAIL,
        ])
            ->map(fn (mixed $email): ?string => static::normalizeEmailValue($email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function systemSetting(): BelongsTo
    {
        return $this->belongsTo(SystemSetting::class);
    }

    public function ownedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'owner_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments');
    }

    public function lessonCompletions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function certificatePayments(): HasMany
    {
        return $this->hasMany(CertificatePayment::class);
    }

    public function notificationViews(): HasMany
    {
        return $this->hasMany(NotificationView::class);
    }

    public function paymentEntitlements(): HasMany
    {
        return $this->hasMany(PaymentEntitlement::class);
    }

    public function finalTestAttempts(): HasMany
    {
        return $this->hasMany(FinalTestAttempt::class);
    }

    public function oneSignalExternalId(): string
    {
        return sprintf(
            'tenant:%d:user:%d',
            (int) ($this->system_setting_id ?? 0),
            (int) ($this->id ?? 0)
        );
    }

    public function oneSignalEmail(): ?string
    {
        $email = static::normalizeEmailValue($this->email);

        if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    public function oneSignalSmsPhone(): ?string
    {
        return static::normalizePhoneForOneSignal($this->whatsapp);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        $email = static::normalizeEmailValue($this->email);

        return $email !== null
            && in_array($email, static::configuredSuperAdminEmails(), true);
    }

    public function hasAdminPrivileges(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function adminContextSystemSettingId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return SystemSetting::currentId()
                ?? ((int) ($this->system_setting_id ?? 0) ?: null)
                ?? SystemSetting::forUser($this)?->id;
        }

        return ((int) ($this->system_setting_id ?? 0) ?: null)
            ?? SystemSetting::forUser($this)?->id;
    }

    public function canAccessSystemSetting(?int $systemSettingId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $currentSystemSettingId = $this->adminContextSystemSettingId();

        return $systemSettingId !== null
            && $currentSystemSettingId !== null
            && (int) $currentSystemSettingId === (int) $systemSettingId;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::STUDENT;
    }

    public function preferredName(): string
    {
        return $this->display_name ?: $this->name;
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/'.$this->profile_photo_path)
            : null;
    }

    private static function normalizeEmailValue(mixed $value): ?string
    {
        $email = trim((string) $value);

        if ($email === '') {
            return null;
        }

        return mb_strtolower($email, 'UTF-8');
    }

    private static function normalizePhoneForOneSignal(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return '+'.$digits;
    }
}
