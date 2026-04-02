<?php

namespace App\Mail;

use App\Models\SystemSetting;
use App\Services\PasswordRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?SystemSetting $systemSetting,
        public string $code,
    ) {}

    public function build(): self
    {
        $schoolName = trim((string) ($this->systemSetting?->escola_nome ?: $this->systemSetting?->resolvedMailFromName() ?: config('app.name', 'EduX')));

        return $this->subject('Seu código para recuperar a senha')
            ->view('emails.password-recovery-code')
            ->with([
                'schoolName' => $schoolName,
                'code' => $this->code,
                'expiresInMinutes' => (int) floor(PasswordRecoveryService::CODE_TTL_SECONDS / 60),
            ]);
    }
}
