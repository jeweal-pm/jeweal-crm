<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class ManagedEmailMailable extends Mailable
{
    use Queueable;

    public function __construct(
        public string $emailHtml,
        public string $emailPlainText,
        public string $emailSubject,
        public ?string $senderEmail = null,
        public ?string $senderName = null,
        public ?string $replyToEmail = null,
        public ?string $messageId = null
    ) {
    }

    public function build(): self
    {
        $mail = $this->subject($this->emailSubject)
            ->from($this->senderEmail ?: config('mail.from.address'), $this->senderName ?: config('mail.from.name'))
            ->view('mail.managed-email')
            ->text('mail.managed-email-text');
        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail);
        }

        return $mail->withSymfonyMessage(function ($message) {
            if ($this->messageId) {
                $message->getHeaders()->addTextHeader('X-CRM-Message-ID', $this->messageId);
            }
        });
    }
}
