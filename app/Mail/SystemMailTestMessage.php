<?php

namespace App\Mail;

use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemMailTestMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SystemSetting $systemSetting,
    ) {}

    public function build(): self
    {
        $schoolName = trim((string) ($this->systemSetting->escola_nome ?: $this->systemSetting->resolvedMailFromName()));
        $domain = $this->systemSetting->domain ?: parse_url((string) config('app.url'), PHP_URL_HOST);

        return $this->subject('Teste de e-mail da escola '.$schoolName)
            ->view('emails.system-mail-test-message')
            ->with([
                'schoolName' => $schoolName,
                'domain' => $domain,
                'mailer' => $this->systemSetting->mail_mailer ?: config('mail.default'),
                'sentAt' => now()->format('d/m/Y H:i:s'),
            ]);
    }
}
