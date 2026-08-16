<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Rich sign-in invite email. Sent by an admin from Users → Change password
 * (with the "email the user" toggle on), the "Send login info" button on
 * the user view page, or Merchants → row action "Send login info by email".
 *
 * When $password is null the template renders the invite (link only) copy;
 * when it's set it renders the password + security-tips section. All the
 * contact/brand fields are optional — an unset field silently drops its
 * block, so a bare (userName, email, null, url) call still produces a
 * clean, minimal email.
 */
class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $userName,
        public string  $email,
        public ?string $password,
        public string  $loginUrl,
        /** Full "https://smile.rushly.tech" style URL — surfaced next to the CTA button. */
        public ?string $tenantDomain    = null,
        /** Rendered as the "Need help?" contact block. */
        public ?string $supportEmail    = null,
        public ?string $supportPhone    = null,
        public ?string $supportAddress  = null,
        /** Portal label ("Merchant portal", "Admin portal", …). */
        public ?string $portalName      = null,
        /** Absolute URL to the tenant/brand logo — 32-56px tall renders best. */
        public ?string $brandLogo       = null,
    ) {}

    public function build()
    {
        // Provider-authorized sender; tenant kept as display name + Reply-To.
        $sender = tenantMailFrom();
        $brand  = settings()?->name ?: config('app.name');

        $mail = $this->from($sender['address'], $sender['name']);
        if ($sender['reply_to']) {
            $mail->replyTo($sender['reply_to'], $sender['name']);
        }

        return $mail
            ->subject($this->password
                ? __('Your new password for :brand', ['brand' => $brand])
                : __('Sign in to :brand', ['brand' => $brand]))
            ->view('emails.user-credentials', [
                'userName'       => $this->userName,
                'email'          => $this->email,
                'password'       => $this->password,
                'loginUrl'       => $this->loginUrl,
                'brand'          => (string) $brand,
                'tenantDomain'   => $this->tenantDomain,
                'supportEmail'   => $this->supportEmail,
                'supportPhone'   => $this->supportPhone,
                'supportAddress' => $this->supportAddress,
                'portalName'     => $this->portalName ?: __('Portal'),
                'brandLogo'      => $this->brandLogo,
            ]);
    }
}
