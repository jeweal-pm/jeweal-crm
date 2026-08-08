<?php

namespace App\Mail;

use App\Models\GmsStoneEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GmsStoneEnquiryReply extends Mailable
{
    use Queueable;
    use SerializesModels;

    public GmsStoneEnquiry $enquiry;

    public string $replySubject;

    public string $replyMessage;

    public User $sender;

    public function __construct(GmsStoneEnquiry $enquiry, string $replySubject, string $replyMessage, User $sender)
    {
        $this->enquiry = $enquiry;
        $this->replySubject = $replySubject;
        $this->replyMessage = $replyMessage;
        $this->sender = $sender;
    }

    public function build(): self
    {
        return $this
            ->subject($this->replySubject)
            ->view('mail.gms-stone-enquiry-reply');
    }
}
