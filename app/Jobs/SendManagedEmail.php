<?php

namespace App\Jobs;

use App\Mail\ManagedEmailMailable;
use App\Models\EmailMessage;
use App\Services\Email\EmailUrlService;
use App\Services\Email\EmailSenderResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendManagedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $messageId)
    {
    }

    public function handle(): void
    {
        $message = EmailMessage::query()->with(['subscriber', 'template'])->find($this->messageId);
        if (! $message || in_array($message->status, ['sent', 'delivered'], true)) {
            return;
        }

        if (config('email_management.emergency_stop') || $message->status === 'suppressed') {
            return;
        }

        $message->increment('attempts');
        $message->update(['status' => 'processing']);

        try {
            $mail = Mail::to($message->to_email);
            foreach ($message->cc ?: [] as $email) {
                $mail->cc($email);
            }
            foreach ($message->bcc ?: [] as $email) {
                $mail->bcc($email);
            }
            $senders = app(EmailSenderResolver::class);
            $senderEmail = $senders->resolve(
                $message->subscriber?->source_type,
                $message->template?->sender_email
            );
            $senderName = $senders->resolveName(
                $message->subscriber?->source_type,
                $message->template?->sender_name
            );
            $mailable = new ManagedEmailMailable(
                $message->html_content,
                $message->plain_text_content ?: strip_tags($message->html_content),
                $message->subject,
                $senderEmail,
                $senderName,
                $message->template?->reply_to_email,
                $message->message_id
            );
            $mailable->withSymfonyMessage(function ($symfonyMessage) use ($message) {
                if ($message->subscriber) {
                    $symfonyMessage->getHeaders()->addTextHeader('List-Unsubscribe', '<'.app(EmailUrlService::class)->to('/unsubscribe/'.$message->subscriber->unsubscribe_token_hash).'>');
                    $symfonyMessage->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                }
            });
            $mail->send($mailable);

            $message->update(['status' => 'sent', 'sent_at' => now(), 'last_event_at' => now()]);
            if ($message->subscriber) {
                $message->subscriber->update(['last_sent_at' => now()]);
            }
        } catch (\Throwable $exception) {
            $message->update(['status' => 'failed', 'failure_reason' => 'Mail provider error']);
            throw $exception;
        }
    }
}
