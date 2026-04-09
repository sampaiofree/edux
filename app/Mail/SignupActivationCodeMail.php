<?php

namespace App\Mail;

use App\Models\SystemSetting;
use App\Services\PublicSignupService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SignupActivationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?SystemSetting $systemSetting,
        public string $code,
    ) {}

    public function build(): self
    {
        $schoolName = trim((string) ($this->systemSetting?->escola_nome ?: $this->systemSetting?->resolvedMailFromName() ?: config('app.name', 'EduX')));

        return $this->subject('Seu código para ativar sua conta')
            ->view('emails.signup-activation-code')
            ->with([
                'schoolName' => $schoolName,
                'code' => $this->code,
                'expiresInMinutes' => (int) floor(PublicSignupService::CODE_TTL_SECONDS / 60),
            ]);
    }
}
