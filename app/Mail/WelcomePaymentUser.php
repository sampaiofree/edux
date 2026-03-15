<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomePaymentUser extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword
    ) {
    }

    public function build(): self
    {
        $loginUrl = rtrim(config('app.url'), '/').'/login';

        return $this->subject('Bem-vindo(a) à EduX')
            ->view('emails.welcome-payment-user')
            ->with([
                'user' => $this->user,
                'loginUrl' => $loginUrl,
                'plainPassword' => $this->plainPassword,
            ]);
    }
}
