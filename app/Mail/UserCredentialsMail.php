<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent by an admin from Users → Change password with the "email the user"
 * toggle on, or from the "Send login info" button on the user view page.
 * When $password is null we only ship the login URL — used for invites
 * where we don't know the plaintext (any pre-existing user).
 */
class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $userName,
        public string  $email,
        public ?string $password,
        public string  $loginUrl,
    ) {}

    public function build()
    {
        $from = settings()?->email ?: config('mail.from.address');
        $brand = settings()?->company_name ?? settings()?->name ?? config('app.name');

        return $this->from($from)
            ->subject($this->password
                ? __('Your new password for :brand', ['brand' => $brand])
                : __('Sign in to :brand', ['brand' => $brand]))
            ->view('emails.user-credentials', [
                'userName' => $this->userName,
                'email'    => $this->email,
                'password' => $this->password,
                'loginUrl' => $this->loginUrl,
                'brand'    => (string) $brand,
            ]);
    }
}
