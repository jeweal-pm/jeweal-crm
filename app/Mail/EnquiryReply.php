<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnquiryReply extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Model $enquiry;

    public string $enquiryType;

    public string $replySubject;

    public string $replyMessage;

    public User $sender;

    public function __construct(Model $enquiry, string $enquiryType, string $replySubject, string $replyMessage, User $sender)
    {
        $this->enquiry = $enquiry;
        $this->enquiryType = $enquiryType;
        $this->replySubject = $replySubject;
        $this->replyMessage = $replyMessage;
        $this->sender = $sender;
    }

    public function build(): self
    {
        return $this
            ->subject($this->replySubject)
            ->view('mail.enquiry-reply');
    }
}
