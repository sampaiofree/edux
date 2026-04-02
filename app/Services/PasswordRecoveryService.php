<?php

namespace App\Services;

use App\Mail\PasswordRecoveryCodeMail;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordRecoveryService
{
    public const CODE_TTL_SECONDS = 600;
    public const RESEND_COOLDOWN_SECONDS = 30;
    public const MAX_ATTEMPTS = 5;
    public const LOCK_SECONDS = 600;

    public function __construct(
        private readonly TenantMailManager $tenantMailManager,
    ) {}

    public function requestCode(string $email, string $ip, ?SystemSetting $systemSetting): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        if ($this->isLocked($normalizedEmail, $ip)) {
            return [
                'status' => 'locked',
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'retry_in' => $this->secondsUntilUnlock($normalizedEmail, $ip),
            ];
        }

        $lastSentAt = Cache::get($this->key($normalizedEmail, $ip, 'last_sent'));
        if ($lastSentAt) {
            $elapsed = now()->diffInSeconds($lastSentAt);
            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                return [
                    'status' => 'cooldown',
                    'message' => 'Aguarde alguns segundos para reenviar o código.',
                    'retry_in' => self::RESEND_COOLDOWN_SECONDS - $elapsed,
                ];
            }
        }

        $user = $this->recoverableUser($normalizedEmail, $systemSetting);

        if ($user) {
            $code = $this->generateCode();

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $normalizedEmail],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ],
            );

            try {
                $this->tenantMailManager->send(
                    $systemSetting,
                    $user->email,
                    new PasswordRecoveryCodeMail($systemSetting, $code),
                );
            } catch (\Throwable $exception) {
                DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
                Cache::forget($this->key($normalizedEmail, $ip, 'last_sent'));

                throw $exception;
            }
        } else {
            DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
        }

        Cache::put($this->key($normalizedEmail, $ip, 'last_sent'), now(), self::CODE_TTL_SECONDS);

        return [
            'status' => 'sent',
            'message' => 'Se o e-mail existir, enviamos um código para continuar.',
            'retry_in' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function verifyCode(string $email, string $ip, string $code): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        if ($this->isLocked($normalizedEmail, $ip)) {
            return [
                'status' => 'locked',
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'retry_in' => $this->secondsUntilUnlock($normalizedEmail, $ip),
            ];
        }

        $record = DB::table('password_reset_tokens')->where('email', $normalizedEmail)->first();
        if (! $record || blank($record->token)) {
            return [
                'status' => 'invalid',
                'message' => 'Código incorreto. Confira e tente novamente.',
            ];
        }

        $createdAt = blank($record->created_at) ? null : Carbon::parse((string) $record->created_at);
        $expiresAt = $createdAt?->copy()->addSeconds(self::CODE_TTL_SECONDS);

        if (! $createdAt || ! $expiresAt || now()->greaterThan($expiresAt)) {
            DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();

            return [
                'status' => 'expired',
                'message' => 'Código expirado. Peça um novo código.',
            ];
        }

        if (! Hash::check((string) $code, (string) $record->token)) {
            $attemptsKey = $this->key($normalizedEmail, $ip, 'attempts');
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, self::LOCK_SECONDS);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->lock($normalizedEmail, $ip);

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

        DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
        $this->clearAttempts($normalizedEmail, $ip);

        return [
            'status' => 'verified',
        ];
    }

    public function resetPassword(string $email, string $password, ?SystemSetting $systemSetting): ?User
    {
        $user = $this->recoverableUser($email, $systemSetting);

        if (! $user) {
            return null;
        }

        $user->forceFill([
            'password' => $password,
        ])->save();

        DB::table('password_reset_tokens')->where('email', $this->normalizeEmail($email))->delete();

        return $user->fresh();
    }

    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }

    public function recoverableUser(string $email, ?SystemSetting $systemSetting): ?User
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $systemSettingId = (int) ($systemSetting?->id ?? 0);

        if ($normalizedEmail === '' || $systemSettingId <= 0) {
            return null;
        }

        return User::withoutGlobalScopes()
            ->where('system_setting_id', $systemSettingId)
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->get()
            ->first(static fn (User $user): bool => ! $user->isSuperAdmin());
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function key(string $email, string $ip, string $suffix): string
    {
        $safeEmail = Str::of($email)->replace('@', '_at_')->replace('.', '_')->toString();

        return "password_recovery:{$safeEmail}:{$ip}:{$suffix}";
    }

    private function isLocked(string $email, string $ip): bool
    {
        $lockedUntil = Cache::get($this->key($email, $ip, 'locked_until'));

        return $lockedUntil && now()->lessThan($lockedUntil);
    }

    private function secondsUntilUnlock(string $email, string $ip): int
    {
        $lockedUntil = Cache::get($this->key($email, $ip, 'locked_until'));

        return $lockedUntil ? now()->diffInSeconds($lockedUntil) : 0;
    }

    private function lock(string $email, string $ip): void
    {
        Cache::put($this->key($email, $ip, 'locked_until'), now()->addSeconds(self::LOCK_SECONDS), self::LOCK_SECONDS);
    }

    private function clearAttempts(string $email, string $ip): void
    {
        Cache::forget($this->key($email, $ip, 'attempts'));
        Cache::forget($this->key($email, $ip, 'locked_until'));
    }
}
