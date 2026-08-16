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
        // Sending AS the tenant's own address got every mail rejected by the
        // provider when that domain wasn't verified — see tenantMailFrom().
        $sender = tenantMailFrom();

        $mail = $this->from($sender['address'], $sender['name']);
        if ($sender['reply_to']) {
            $mail->replyTo($sender['reply_to'], $sender['name']);
        }

        return $mail
            ->subject(__('auth.login_otp_subject'))
            ->view('emails.login-otp', [
                'code'             => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
                'userName'         => $this->userName,
            ]);
    }
}
