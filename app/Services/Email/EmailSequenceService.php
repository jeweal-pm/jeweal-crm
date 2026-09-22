<?php

namespace App\Services\Email;

use App\Models\EmailEnrollment;
use Illuminate\Support\Carbon;

class EmailSequenceService
{
    public function __construct(
        private EmailMessageService $messages,
        private EmailTemplateRenderer $renderer,
        private EmailBrandingService $branding,
        private EmailUrlService $urls
    ) {
    }

    public function processDue(): int
    {
        $processed = 0;

        EmailEnrollment::query()
            ->with(['subscriber', 'sequence.steps.template'])
            ->where('status', 'active')
            ->whereNotNull('next_scheduled_at')
            ->where('next_scheduled_at', '<=', now())
            ->whereHas('sequence', fn ($query) => $query->where('status', 'published'))
            ->chunkById(100, function ($enrollments) use (&$processed) {
                foreach ($enrollments as $enrollment) {
                    $processed += $this->processEnrollment($enrollment) ? 1 : 0;
                }
            });

        return $processed;
    }

    public function processEnrollment(EmailEnrollment $enrollment): bool
    {
        $enrollment->loadMissing(['subscriber', 'sequence.steps.template']);

        if (
            $enrollment->status !== 'active'
            || ! $enrollment->next_scheduled_at
            || $enrollment->next_scheduled_at->isFuture()
            || $enrollment->sequence?->status !== 'published'
        ) {
            return false;
        }

        $currentStep = $enrollment->current_step ?: 1;
        $step = $enrollment->sequence->steps->firstWhere('step_number', $currentStep);
        $subscriber = $enrollment->subscriber;

        if (! $step || ($step->content_mode !== 'custom' && ! $step->template)) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'exit_reason' => 'sequence_complete',
            ]);

            return false;
        }

        $category = $step->template?->category ?: 'follow_up';

        if (! $subscriber || ! $subscriber->canReceiveMarketing($category)) {
            $enrollment->update([
                'status' => 'suppressed',
                'completed_at' => now(),
                'exit_reason' => 'suppressed',
            ]);

            return false;
        }

        $data = [
            'first_name' => $subscriber->first_name,
            'last_name' => $subscriber->last_name,
            'email' => $subscriber->email,
            'company_name' => $subscriber->company_name,
            'enquiry_number' => strtoupper((string) $subscriber->source_type).'-'.$subscriber->source_id,
            'enquiry_type' => $subscriber->source_type,
            'submitted_at' => optional($subscriber->created_at)->format('Y-m-d H:i'),
            'unsubscribe_url' => $this->urls->to('/unsubscribe/'.$subscriber->unsubscribe_token_hash),
        ];
        $rendered = $step->content_mode === 'custom'
            ? $this->renderer->renderCustom($step->subject, $step->html_content, $step->plain_text_content, $data)
            : $this->renderer->render($step->template, $data);
        $html = $this->branding->wrap(
            $rendered['html_content'],
            $subscriber->source_type,
            ($step->template?->name ?: 'custom content').' '.($step->template?->code ?: '').' '.$enrollment->sequence->name.' '.$enrollment->sequence->code
        );

        $message = $this->messages->queue(
            $subscriber,
            $step->template,
            $data,
            'marketing',
            [],
            'enrollment:'.$enrollment->id.':step:'.$step->step_number,
            null,
            ['enrollment_id' => $enrollment->id, 'step_id' => $step->id],
            [
                'subject' => $rendered['subject'],
                'html_content' => $html,
                'plain_text_content' => $rendered['plain_text_content'],
                'category' => $category,
            ],
            false
        );

        if (! in_array($message->status, ['queued', 'processing', 'sent', 'delivered'], true)) {
            return false;
        }

        $next = $enrollment->sequence->steps->firstWhere('step_number', $step->step_number + 1);
        $enrollment->update([
            'current_step' => $step->step_number + 1,
            'last_email_sent_at' => now(),
            'next_scheduled_at' => $next ? $this->nextAt($next) : null,
            'status' => $next ? 'active' : 'completed',
            'completed_at' => $next ? null : now(),
        ]);

        return true;
    }

    private function nextAt($step): Carbon
    {
        $seconds = match ($step->delay_unit) {
            'hours' => $step->delay_value * 3600,
            'days' => $step->delay_value * 86400,
            default => $step->delay_value * 60,
        };

        return now()->addSeconds($seconds);
    }
}
