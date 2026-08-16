<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantSignup extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($data=null)
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
        // Was sending AS the tenant address, which the provider rejects for
        // unverified domains — see tenantMailFrom().
        $sender = tenantMailFrom();
        $mail   = $this->from($sender['address'], $sender['name']);
        if ($sender['reply_to']) {
            $mail->replyTo($sender['reply_to'], $sender['name']);
        }
        return $mail->subject('Welcome to new merchant')->view('backend.merchant.mail.signup',compact('data'));
    }
}
