<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels; 
class CompanySignup extends Mailable
{
    protected $data;
    public function __construct($data=null)
    {
        $this->data = $data;
    }
    public function build()
    {
        $data          = $this->data;
        // Was sending AS the tenant address, which the provider rejects for
        // unverified domains — see tenantMailFrom().
        $sender = tenantMailFrom();
        $mail   = $this->from($sender['address'], $sender['name']);
        if ($sender['reply_to']) {
            $mail->replyTo($sender['reply_to'], $sender['name']);
        }
        return $mail->subject('Welcome to new company')->view('backend.super-admin.company.mail.signup',compact('data'));
    }
}
