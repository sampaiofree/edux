<?php

namespace App\Support\Mail;

use App\Models\SystemSetting;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;

class TenantMailManager
{
    public function __construct(
        private readonly MailFactory $mailFactory,
    ) {}

    public function send(?SystemSetting $systemSetting, mixed $users, MailableContract $mailable): void
    {
        $this->mailerFor($systemSetting)
            ->to($users)
            ->send($mailable);
    }

    public function mailerFor(?SystemSetting $systemSetting): MailerContract
    {
        if (! method_exists($this->mailFactory, 'build')) {
            return $this->mailFactory;
        }

        $config = $this->mailerConfigFor($systemSetting);

        if ($config === null) {
            return $this->mailFactory->mailer();
        }

        $mailer = $this->mailFactory->build($config);

        if (filled($config['from']['address'] ?? null)) {
            $mailer->alwaysFrom(
                $config['from']['address'],
                $config['from']['name'] ?? null,
            );
        }

        return $mailer;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mailerConfigFor(?SystemSetting $systemSetting): ?array
    {
        if (! $systemSetting?->hasCustomMailConfiguration()) {
            return null;
        }

        $driver = trim((string) $systemSetting->mail_mailer);
        $from = [
            'address' => $systemSetting->resolvedMailFromAddress(),
            'name' => $systemSetting->resolvedMailFromName(),
        ];

        return match ($driver) {
            'smtp' => [
                'name' => 'tenant-smtp-'.$systemSetting->id,
                'transport' => 'smtp',
                'host' => $this->nullableString($systemSetting->mail_host) ?? config('mail.mailers.smtp.host'),
                'port' => $systemSetting->mail_port ?: config('mail.mailers.smtp.port'),
                'username' => $this->nullableString($systemSetting->mail_username),
                'password' => $this->nullableString($systemSetting->mail_password),
                'timeout' => config('mail.mailers.smtp.timeout'),
                'local_domain' => $systemSetting->domain ?: config('mail.mailers.smtp.local_domain'),
                'from' => $from,
                ...$this->smtpSchemeConfig(
                    $this->nullableString($systemSetting->mail_scheme) ?? config('mail.mailers.smtp.scheme')
                ),
            ],
            'log' => [
                'name' => 'tenant-log-'.$systemSetting->id,
                'transport' => 'log',
                'channel' => config('mail.mailers.log.channel'),
                'from' => $from,
            ],
            default => null,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function smtpSchemeConfig(?string $value): array
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'smtp' => ['scheme' => 'smtp'],
            'tls', 'starttls' => [
                'scheme' => 'smtp',
                'require_tls' => true,
            ],
            'ssl', 'smtps' => ['scheme' => 'smtps'],
            default => ['scheme' => $normalized],
        };
    }
}
