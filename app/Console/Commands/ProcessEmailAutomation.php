<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Models\EmailEnrollment;
use App\Services\Email\EmailCampaignService;
use App\Services\Email\EmailMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessEmailAutomation extends Command
{
    protected $signature = 'email:process-automation';

    protected $description = 'Process due email sequence steps and scheduled messages';

    public function handle(EmailMessageService $messages, EmailCampaignService $campaigns): int
    {
        if (config('email_management.emergency_stop')) {
            return self::SUCCESS;
        }

        EmailCampaign::query()
            ->where('status', 'scheduled')
            ->where('approval_status', 'approved')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->each(fn (EmailCampaign $campaign) => $campaigns->run($campaign));

        EmailEnrollment::query()
            ->with(['subscriber', 'sequence.steps.template'])
            ->where('status', 'active')
            ->whereNotNull('next_scheduled_at')
            ->where('next_scheduled_at', '<=', now())
            ->chunkById(100, function ($enrollments) use ($messages) {
                foreach ($enrollments as $enrollment) {
                    $step = $enrollment->sequence->steps->firstWhere('step_number', $enrollment->current_step);
                    if (! $step || ! $enrollment->subscriber->canReceiveMarketing($step->template->category)) {
                        $enrollment->update(['status' => $step ? 'suppressed' : 'completed', 'completed_at' => now(), 'exit_reason' => $step ? 'suppressed' : 'sequence_complete']);

                        continue;
                    }

                    $subscriber = $enrollment->subscriber;
                    $messages->queue($subscriber, $step->template, [
                        'first_name' => $subscriber->first_name,
                        'last_name' => $subscriber->last_name,
                        'email' => $subscriber->email,
                        'company_name' => $subscriber->company_name,
                        'enquiry_number' => strtoupper((string) $subscriber->source_type).'-'.$subscriber->source_id,
                        'enquiry_type' => $subscriber->source_type,
                        'submitted_at' => optional($subscriber->created_at)->format('Y-m-d H:i'),
                        'unsubscribe_url' => url('/unsubscribe/'.$subscriber->unsubscribe_token_hash),
                    ], 'marketing', [], 'enrollment:'.$enrollment->id.':step:'.$step->step_number, null, ['enrollment_id' => $enrollment->id, 'step_id' => $step->id]);

                    $next = $enrollment->sequence->steps->firstWhere('step_number', $step->step_number + 1);
                    $enrollment->update([
                        'current_step' => $step->step_number + 1,
                        'last_email_sent_at' => now(),
                        'next_scheduled_at' => $next ? $this->nextAt($next) : null,
                        'status' => $next ? 'active' : 'completed',
                        'completed_at' => $next ? null : now(),
                    ]);
                }
            });

        return self::SUCCESS;
    }

    private function nextAt($step): Carbon
    {
        $seconds = match ($step->delay_unit) {
            'hours' => $step->delay_value * 3600, 'days' => $step->delay_value * 86400, default => $step->delay_value * 60,
        };

        return now()->addSeconds($seconds);
    }
}
