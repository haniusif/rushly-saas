<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        $logoImage = settings()->LogoImage;
        // The submitter's address must NOT be the envelope From: it is
        // attacker-controlled (any visitor can type any address), the provider
        // rejects it outright, and sending as an arbitrary third party is a
        // spoofing vector. Send from the platform and put the submitter in
        // Reply-To so replying still reaches them.
        $sender = tenantMailFrom();

        return $this->view('backend.contact.contact_mail', compact('data','logoImage'))
            ->from($sender['address'], $sender['name'])
            ->replyTo($data['email'], $data['name'] ?? $data['email'])
            ->to(settings()->email)
            ->subject($data['subject']);
    }
}
