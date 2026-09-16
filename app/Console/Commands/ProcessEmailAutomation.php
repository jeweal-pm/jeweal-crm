<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\Email\EmailCampaignService;
use App\Services\Email\EmailSequenceService;
use Illuminate\Console\Command;

class ProcessEmailAutomation extends Command
{
    protected $signature = 'email:process-automation';

    protected $description = 'Process due email sequence steps and scheduled messages';

    public function handle(EmailCampaignService $campaigns, EmailSequenceService $sequences): int
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

        $sequences->processDue();

        return self::SUCCESS;
    }
}
