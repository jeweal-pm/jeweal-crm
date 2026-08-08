<?php

namespace App\Services\Email;

use App\Models\EmailCampaign;
use App\Models\EmailEnrollment;

class EmailCampaignService
{
    public function __construct(private EmailSegmentService $segments, private EmailMessageService $messages)
    {
    }

    public function run(EmailCampaign $campaign): int
    {
        if ($campaign->campaign_type === 'sequence') {
            return $this->enrollSequence($campaign);
        }

        $template = $campaign->template;
        if (! $template || ! $campaign->segment) {
            return 0;
        }

        $count = 0;
        $limit = $campaign->sending_limit ?: PHP_INT_MAX;
        $query = $this->segments->members($campaign->segment);
        $excluded = $campaign->excludedSegment;
        if ($excluded) {
            $query->whereNotIn('id', $this->segments->members($excluded)->select('id'));
        }

        $campaign->update(['status' => 'running', 'started_at' => now()]);
        $variants = $campaign->variants()->with('template')->get();
        $query->chunkById(100, function ($subscribers) use (&$count, $limit, $campaign, $template, $variants) {
            foreach ($subscribers as $subscriber) {
                if ($count >= $limit) {
                    return false;
                }
                $variant = $this->variantFor($variants, $subscriber->id);
                $selectedTemplate = $variant?->template ?: $template;
                $message = $this->messages->queue($subscriber, $selectedTemplate, [
                    'first_name' => $subscriber->first_name,
                    'last_name' => $subscriber->last_name,
                    'email' => $subscriber->email,
                    'company_name' => $subscriber->company_name,
                    'enquiry_number' => strtoupper((string) $subscriber->source_type).'-'.$subscriber->source_id,
                    'enquiry_type' => $subscriber->source_type,
                    'submitted_at' => optional($subscriber->created_at)->format('Y-m-d H:i'),
                    'unsubscribe_url' => url('/unsubscribe/'.$subscriber->unsubscribe_token_hash),
                ], 'marketing', [], 'campaign:'.$campaign->id.':subscriber:'.$subscriber->id, null, ['campaign_id' => $campaign->id], ['subject' => $variant?->subject]);
                if (! in_array($message->status, ['suppressed', 'deferred'], true)) {
                    $count++;
                }
            }

            return true;
        });
        $campaign->update(['status' => 'completed', 'completed_at' => now()]);

        return $count;
    }

    private function variantFor($variants, int $subscriberId)
    {
        if ($variants->isEmpty()) {
            return null;
        }
        $bucket = crc32((string) $subscriberId) % 100;
        $cursor = 0;
        foreach ($variants as $variant) {
            $cursor += $variant->allocation;
            if ($bucket < $cursor) {
                return $variant;
            }
        }

        return $variants->last();
    }

    private function enrollSequence(EmailCampaign $campaign): int
    {
        $sequence = $campaign->sequence;
        if (! $sequence) {
            return 0;
        }

        $count = 0;
        $this->segments->members($campaign->segment)->chunkById(100, function ($subscribers) use (&$count, $sequence, $campaign) {
            foreach ($subscribers as $subscriber) {
                $enrollment = EmailEnrollment::firstOrCreate([
                    'email_subscriber_id' => $subscriber->id,
                    'email_sequence_template_id' => $sequence->id,
                ], [
                    'email_campaign_id' => $campaign->id,
                    'sequence_version' => (string) $sequence->version,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'next_scheduled_at' => now(),
                ]);
                $count += $enrollment->wasRecentlyCreated ? 1 : 0;
            }

            return true;
        });
        $campaign->update(['status' => 'scheduled']);

        return $count;
    }
}
