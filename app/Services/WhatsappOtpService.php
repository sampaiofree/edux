<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WhatsappOtpService
{
    private const CODE_TTL_SECONDS = 600;
    private const RESEND_COOLDOWN_SECONDS = 30;
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 600;

    public function send(string $whatsapp, string $ip): array
    {
        if ($this->isLocked($whatsapp, $ip)) {
            return [
                'status' => 'locked',
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'retry_in' => $this->secondsUntilUnlock($whatsapp, $ip),
            ];
        }

        $lastSentAt = Cache::get($this->key($whatsapp, $ip, 'last_sent'));
        if ($lastSentAt) {
            $elapsed = now()->diffInSeconds($lastSentAt);
            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                return [
                    'status' => 'cooldown',
                    'message' => 'Aguarde alguns segundos para reenviar.',
                    'retry_in' => self::RESEND_COOLDOWN_SECONDS - $elapsed,
                ];
            }
        }

        $code = (string) random_int(1000, 9999);

        Cache::put($this->key($whatsapp, $ip, 'code'), $code, self::CODE_TTL_SECONDS);
        Cache::put($this->key($whatsapp, $ip, 'last_sent'), now(), self::CODE_TTL_SECONDS);

        return [
            'status' => 'sent',
            'message' => 'Código enviado com sucesso.',
            'retry_in' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function verify(string $whatsapp, string $ip, string $code): array
    {
        if ($this->isLocked($whatsapp, $ip)) {
            return [
                'status' => 'locked',
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                'retry_in' => $this->secondsUntilUnlock($whatsapp, $ip),
            ];
        }

        $cachedCode = Cache::get($this->key($whatsapp, $ip, 'code'));
        if (! $cachedCode) {
            return [
                'status' => 'expired',
                'message' => 'Código expirado. Solicite um novo.',
            ];
        }

        if (! hash_equals((string) $cachedCode, (string) $code)) {
            $attemptsKey = $this->key($whatsapp, $ip, 'attempts');
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, self::LOCK_SECONDS);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->lock($whatsapp, $ip);

                return [
                    'status' => 'locked',
                    'message' => 'Muitas tentativas. Tente novamente em alguns minutos.',
                    'retry_in' => self::LOCK_SECONDS,
                ];
            }

            return [
                'status' => 'invalid',
                'message' => 'Código incorreto. Tente novamente.',
                'attempts_left' => self::MAX_ATTEMPTS - $attempts,
            ];
        }

        $this->clearAttempts($whatsapp, $ip);

        return [
            'status' => 'verified',
        ];
    }

    private function key(string $whatsapp, string $ip, string $suffix): string
    {
        $safe = Str::of($whatsapp)->replace('+', '')->replace(' ', '')->toString();

        return "whatsapp_otp:{$safe}:{$ip}:{$suffix}";
    }

    private function isLocked(string $whatsapp, string $ip): bool
    {
        $lockedUntil = Cache::get($this->key($whatsapp, $ip, 'locked_until'));

        return $lockedUntil && now()->lessThan($lockedUntil);
    }

    private function secondsUntilUnlock(string $whatsapp, string $ip): int
    {
        $lockedUntil = Cache::get($this->key($whatsapp, $ip, 'locked_until'));

        return $lockedUntil ? now()->diffInSeconds($lockedUntil) : 0;
    }

    private function lock(string $whatsapp, string $ip): void
    {
        Cache::put($this->key($whatsapp, $ip, 'locked_until'), now()->addSeconds(self::LOCK_SECONDS), self::LOCK_SECONDS);
    }

    private function clearAttempts(string $whatsapp, string $ip): void
    {
        Cache::forget($this->key($whatsapp, $ip, 'attempts'));
        Cache::forget($this->key($whatsapp, $ip, 'code'));
        Cache::forget($this->key($whatsapp, $ip, 'last_sent'));
        Cache::forget($this->key($whatsapp, $ip, 'locked_until'));
    }
}
