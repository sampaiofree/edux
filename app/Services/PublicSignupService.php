<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\SignupActivationCodeMail;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PublicSignupService
{
    public const CODE_TTL_SECONDS = 600;
    public const RESEND_COOLDOWN_SECONDS = 30;
    public const MAX_ATTEMPTS = 5;
    public const LOCK_SECONDS = 600;

    public function __construct(
        private readonly TenantMailManager $tenantMailManager,
    ) {}

    public function requestCode(string $name, string $email, string $ip, ?SystemSetting $systemSetting): array
    {
        $normalizedName = $this->normalizeName($name);
        $normalizedEmail = $this->normalizeEmail($email);
        $systemSettingId = (int) ($systemSetting?->id ?? 0);

        if ($normalizedName === '' || $normalizedEmail === '' || $systemSettingId <= 0) {
            return [
                'status' => 'invalid',
                'message' => 'Não foi possível iniciar o cadastro agora.',
            ];
        }

        if ($this->emailAlreadyExists($normalizedEmail, $systemSettingId)) {
            return [
                'status' => 'existing',
                'message' => 'Já existe uma conta com esse e-mail. Entre ou recupere sua senha.',
            ];
        }

        if ($this->isLocked($systemSettingId, $normalizedEmail, $ip)) {
            return [
                'status' => 'locked',
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'retry_in' => $this->secondsUntilUnlock($systemSettingId, $normalizedEmail, $ip),
            ];
        }

        $lastSentAt = Cache::get($this->key($systemSettingId, $normalizedEmail, $ip, 'last_sent'));
        if ($lastSentAt) {
            $elapsed = now()->diffInSeconds(Carbon::parse((string) $lastSentAt));

            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                return [
                    'status' => 'cooldown',
                    'message' => 'Aguarde alguns segundos para reenviar o código.',
                    'retry_in' => self::RESEND_COOLDOWN_SECONDS - $elapsed,
                ];
            }
        }

        $code = $this->generateCode();
        Cache::put($this->key($systemSettingId, $normalizedEmail, $ip, 'token'), Hash::make($code), self::CODE_TTL_SECONDS);
        Cache::put($this->key($systemSettingId, $normalizedEmail, $ip, 'created_at'), now()->toIso8601String(), self::CODE_TTL_SECONDS);

        try {
            $this->tenantMailManager->send(
                $systemSetting,
                $normalizedEmail,
                new SignupActivationCodeMail($systemSetting, $code),
            );
        } catch (\Throwable $exception) {
            Cache::forget($this->key($systemSettingId, $normalizedEmail, $ip, 'token'));
            Cache::forget($this->key($systemSettingId, $normalizedEmail, $ip, 'created_at'));
            Cache::forget($this->key($systemSettingId, $normalizedEmail, $ip, 'last_sent'));

            throw $exception;
        }

        Cache::put($this->key($systemSettingId, $normalizedEmail, $ip, 'last_sent'), now()->toIso8601String(), self::CODE_TTL_SECONDS);

        return [
            'status' => 'sent',
            'message' => 'Enviamos um código para ativar sua conta.',
            'retry_in' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function verifyCode(string $email, string $ip, ?SystemSetting $systemSetting, string $code): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $systemSettingId = (int) ($systemSetting?->id ?? 0);

        if ($normalizedEmail === '' || $systemSettingId <= 0) {
            return [
                'status' => 'invalid',
                'message' => 'Código incorreto. Confira e tente novamente.',
            ];
        }

        if ($this->isLocked($systemSettingId, $normalizedEmail, $ip)) {
            return [
                'status' => 'locked',
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'retry_in' => $this->secondsUntilUnlock($systemSettingId, $normalizedEmail, $ip),
            ];
        }

        $token = Cache::get($this->key($systemSettingId, $normalizedEmail, $ip, 'token'));
        $createdAt = Cache::get($this->key($systemSettingId, $normalizedEmail, $ip, 'created_at'));

        if (! is_string($token) || $token === '' || ! is_string($createdAt) || $createdAt === '') {
            return [
                'status' => 'expired',
                'message' => 'Código expirado. Peça um novo código.',
            ];
        }

        $expiresAt = Carbon::parse($createdAt)->addSeconds(self::CODE_TTL_SECONDS);

        if (now()->greaterThan($expiresAt)) {
            $this->clearCode($systemSettingId, $normalizedEmail, $ip);

            return [
                'status' => 'expired',
                'message' => 'Código expirado. Peça um novo código.',
            ];
        }

        if (! Hash::check((string) $code, $token)) {
            $attemptsKey = $this->key($systemSettingId, $normalizedEmail, $ip, 'attempts');
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, self::LOCK_SECONDS);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->lock($systemSettingId, $normalizedEmail, $ip);

                return [
                    'status' => 'locked',
                    'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                    'retry_in' => self::LOCK_SECONDS,
                ];
            }

            return [
                'status' => 'invalid',
                'message' => 'Código incorreto. Confira e tente novamente.',
                'attempts_left' => self::MAX_ATTEMPTS - $attempts,
            ];
        }

        $this->clearCode($systemSettingId, $normalizedEmail, $ip);
        $this->clearAttempts($systemSettingId, $normalizedEmail, $ip);

        return [
            'status' => 'verified',
        ];
    }

    public function activateAccount(string $name, string $email, string $password, ?SystemSetting $systemSetting): ?User
    {
        $normalizedName = $this->normalizeName($name);
        $normalizedEmail = $this->normalizeEmail($email);
        $systemSettingId = (int) ($systemSetting?->id ?? 0);

        if ($normalizedName === '' || $normalizedEmail === '' || $systemSettingId <= 0) {
            return null;
        }

        if ($this->emailAlreadyExists($normalizedEmail, $systemSettingId)) {
            return null;
        }

        $user = User::create([
            'name' => $normalizedName,
            'display_name' => $normalizedName,
            'email' => $normalizedEmail,
            'password' => $password,
            'role' => UserRole::STUDENT,
            'system_setting_id' => $systemSettingId,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user->fresh();
    }

    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }

    public function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function emailAlreadyExists(string $email, int $systemSettingId): bool
    {
        return User::withoutGlobalScopes()
            ->where('system_setting_id', $systemSettingId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
    }

    private function clearCode(int $systemSettingId, string $email, string $ip): void
    {
        Cache::forget($this->key($systemSettingId, $email, $ip, 'token'));
        Cache::forget($this->key($systemSettingId, $email, $ip, 'created_at'));
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function key(int $systemSettingId, string $email, string $ip, string $suffix): string
    {
        $safeEmail = Str::of($email)->replace('@', '_at_')->replace('.', '_')->toString();

        return "signup:{$systemSettingId}:{$safeEmail}:{$ip}:{$suffix}";
    }

    private function isLocked(int $systemSettingId, string $email, string $ip): bool
    {
        $lockedUntil = Cache::get($this->key($systemSettingId, $email, $ip, 'locked_until'));

        return $lockedUntil && now()->lessThan(Carbon::parse((string) $lockedUntil));
    }

    private function secondsUntilUnlock(int $systemSettingId, string $email, string $ip): int
    {
        $lockedUntil = Cache::get($this->key($systemSettingId, $email, $ip, 'locked_until'));

        return $lockedUntil ? now()->diffInSeconds(Carbon::parse((string) $lockedUntil)) : 0;
    }

    private function lock(int $systemSettingId, string $email, string $ip): void
    {
        Cache::put(
            $this->key($systemSettingId, $email, $ip, 'locked_until'),
            now()->addSeconds(self::LOCK_SECONDS)->toIso8601String(),
            self::LOCK_SECONDS
        );
    }

    private function clearAttempts(int $systemSettingId, string $email, string $ip): void
    {
        Cache::forget($this->key($systemSettingId, $email, $ip, 'attempts'));
        Cache::forget($this->key($systemSettingId, $email, $ip, 'locked_until'));
    }
}
