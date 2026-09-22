<?php

namespace App\Services\Email;

use App\Jobs\SendManagedEmail;
use App\Models\EmailMessage;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use Illuminate\Support\Str;

class EmailMessageService
{
    public function __construct(
        private EmailTemplateRenderer $renderer,
        private EmailSubscriberService $subscribers,
        private EmailUrlService $urls
    )
    {
    }

    public function queue(
        EmailSubscriber $subscriber,
        ?EmailTemplate $template,
        array $data,
        string $messageType = 'marketing',
        array $recipients = [],
        ?string $idempotencyKey = null,
        ?int $delaySeconds = null,
        array $relations = [],
        array $overrides = [],
        bool $respectSubscriberFrequencyLimits = true
    ): EmailMessage {
        $category = $template?->category ?: ($overrides['category'] ?? 'follow_up');

        if ($messageType === 'marketing' && ! $subscriber->canReceiveMarketing($category)) {
            return new EmailMessage(['status' => 'suppressed']);
        }

        if ($messageType === 'marketing' && ! $this->withinFrequencyLimits($subscriber, $respectSubscriberFrequencyLimits)) {
            return new EmailMessage(['status' => 'deferred']);
        }

        $rendered = $template
            ? $this->renderer->render($template, $data)
            : $this->renderer->renderCustom(
                (string) ($overrides['subject'] ?? ''),
                (string) ($overrides['html_content'] ?? ''),
                $overrides['plain_text_content'] ?? null,
                $data
            );
        $messageId = (string) Str::uuid();
        $key = $idempotencyKey ?: hash('sha256', implode('|', [$subscriber->id, $template?->id ?: 'custom', $messageType, now()->format('Y-m-d-H-i')]));
        $message = EmailMessage::firstOrCreate(['idempotency_key' => $key], [
            'message_id' => $messageId,
            'email_subscriber_id' => $subscriber->id,
            'email_template_id' => $template?->id,
            'email_campaign_id' => $relations['campaign_id'] ?? null,
            'email_enrollment_id' => $relations['enrollment_id'] ?? null,
            'email_sequence_step_id' => $relations['step_id'] ?? null,
            'message_type' => $messageType,
            'to_email' => $recipients['to'] ?? $subscriber->email,
            'to_data' => $data,
            'cc' => array_values($recipients['cc'] ?? []),
            'bcc' => array_values($recipients['bcc'] ?? []),
            'subject' => $overrides['subject'] ?? $rendered['subject'],
            'html_content' => $this->withTracking($overrides['html_content'] ?? $rendered['html_content'], $messageId, $subscriber),
            'plain_text_content' => $overrides['plain_text_content'] ?? $rendered['plain_text_content'],
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        if ($message->wasRecentlyCreated) {
            $job = SendManagedEmail::dispatch($message->id);
            if ($delaySeconds !== null && $delaySeconds > 0) {
                $job->delay(now()->addSeconds($delaySeconds));
            }
        }

        return $message;
    }

    private function withTracking(string $html, string $messageId, EmailSubscriber $subscriber): string
    {
        if (! config('email_management.tracking_enabled')) {
            return $html;
        }

        $html = preg_replace_callback('/(<a\b[^>]*href=["\'])(https?:\/\/[^"\']+)(["\'])/i', function (array $match) use ($messageId) {
            return $match[1].$this->urls->to('/email-track/click/'.$messageId).'?url='.rawurlencode($match[2]).$match[3];
        }, $html);

        return $html.'<img src="'.e($this->urls->to('/email-track/open/'.$messageId)).'" width="1" height="1" alt="" style="display:none" />';
    }

    private function withinFrequencyLimits(EmailSubscriber $subscriber, bool $respectSubscriberFrequencyLimits = true): bool
    {
        $now = now()->timezone(config('email_management.timezone'));
        $start = $now->copy()->setTimeFromTimeString(config('email_management.quiet_hours_start'));
        $end = $now->copy()->setTimeFromTimeString(config('email_management.quiet_hours_end'));
        $quiet = $start->lt($end)
            ? $now->betweenIncluded($start, $end)
            : $now->gte($start) || $now->lte($end);
        if ($quiet) {
            return false;
        }

        $global = \App\Models\EmailMessage::query()
            ->where('message_type', 'marketing')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($global >= config('email_management.daily_sending_limit')) {
            return false;
        }

        if (! $respectSubscriberFrequencyLimits) {
            return true;
        }

        $daily = $subscriber->messages()
            ->where('message_type', 'marketing')
            ->whereIn('status', ['queued', 'processing', 'sent', 'delivered'])
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $weekly = $subscriber->messages()
            ->where('message_type', 'marketing')
            ->whereIn('status', ['queued', 'processing', 'sent', 'delivered'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return $daily < config('email_management.marketing_daily_limit')
            && $weekly < config('email_management.marketing_weekly_limit');
    }
}
