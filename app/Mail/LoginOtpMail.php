<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $expiresInMinutes,
        public string $userName,
    ) {}

    public function build()
    {
        $from = settings()?->email ?: config('mail.from.address');

        return $this->from($from)
            ->subject(__('auth.login_otp_subject'))
            ->view('emails.login-otp', [
                'code'             => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
                'userName'         => $this->userName,
            ]);
    }
}
