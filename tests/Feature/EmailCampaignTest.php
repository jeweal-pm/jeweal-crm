<?php

namespace Tests\Feature;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignVariant;
use App\Models\EmailSegment;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use App\Services\Email\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-23 12:00:00'));
        config(['email_management.quiet_hours_start' => '21:00', 'email_management.quiet_hours_end' => '08:00', 'email_management.marketing_daily_limit' => 100, 'email_management.marketing_weekly_limit' => 100]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_campaign_sends_only_to_subscribed_segment_members_and_marks_completed(): void
    {
        Mail::fake();
        $template = $this->template('campaign-base', 'Base subject');
        $eligible = $this->subscriber('eligible@example.com', 'eligible');
        $suppressed = $this->subscriber('suppressed@example.com', 'suppressed', 'unsubscribed');
        $segment = EmailSegment::create(['name' => 'Audience', 'code' => 'audience', 'segment_type' => 'static']);
        $segment->subscribers()->attach([$eligible->id, $suppressed->id], ['is_snapshot' => true, 'added_at' => now()]);
        $campaign = EmailCampaign::create(['name' => 'Launch', 'campaign_type' => 'single', 'email_segment_id' => $segment->id, 'email_template_id' => $template->id, 'approval_status' => 'approved', 'status' => 'scheduled']);

        $count = app(EmailCampaignService::class)->run($campaign);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('email_messages', ['email_campaign_id' => $campaign->id, 'to_email' => 'eligible@example.com']);
        $this->assertDatabaseMissing('email_messages', ['to_email' => 'suppressed@example.com']);
        $this->assertDatabaseHas('email_campaigns', ['id' => $campaign->id, 'status' => 'completed']);
    }

    public function test_campaign_variant_assignment_is_stable_for_a_subscriber(): void
    {
        $base = $this->template('campaign-variant-base', 'Base');
        $variantTemplate = $this->template('campaign-variant-a', 'Variant A');
        $subscriber = $this->subscriber('variant@example.com', 'variant');
        $segment = EmailSegment::create(['name' => 'Variant audience', 'code' => 'variant-audience', 'segment_type' => 'static']);
        $segment->subscribers()->attach($subscriber->id, ['is_snapshot' => true, 'added_at' => now()]);
        $campaign = EmailCampaign::create(['name' => 'AB test', 'campaign_type' => 'single', 'email_segment_id' => $segment->id, 'email_template_id' => $base->id, 'approval_status' => 'approved', 'status' => 'scheduled']);
        EmailCampaignVariant::create(['email_campaign_id' => $campaign->id, 'variant_key' => 'A', 'email_template_id' => $variantTemplate->id, 'allocation' => 100, 'success_metric' => 'click_rate', 'minimum_sample_size' => 1]);

        app(EmailCampaignService::class)->run($campaign);

        $this->assertDatabaseHas('email_messages', ['email_campaign_id' => $campaign->id, 'subject' => 'Variant A']);
    }

    private function template(string $code, string $subject): EmailTemplate
    {
        return EmailTemplate::create(['name' => $code, 'code' => $code, 'email_type' => 'marketing', 'category' => 'promotion', 'subject' => $subject, 'html_content' => '<p>Hi {{first_name}}</p>', 'status' => 'published']);
    }

    private function subscriber(string $email, string $token, string $status = 'subscribed'): EmailSubscriber
    {
        return EmailSubscriber::create(['email' => $email, 'first_name' => 'Test', 'subscription_status' => $status, 'unsubscribe_token_hash' => hash('sha256', $token)]);
    }
}
