<?php

namespace App\Mail;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminAudienceMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SystemSetting $systemSetting,
        public User $user,
        public string $subjectLine,
        public string $bodyText,
        public ?string $buttonText = null,
        public ?string $buttonUrl = null,
    ) {}

    public function build(): self
    {
        $schoolName = trim((string) ($this->systemSetting->escola_nome ?: $this->systemSetting->resolvedMailFromName()));

        return $this->subject($this->subjectLine)
            ->view('emails.admin-audience-message')
            ->with([
                'schoolName' => $schoolName !== '' ? $schoolName : 'EduX',
            ]);
    }
}
