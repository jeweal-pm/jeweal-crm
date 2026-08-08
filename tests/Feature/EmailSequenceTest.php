<?php

namespace Tests\Feature;

use App\Models\EmailEnrollment;
use App\Models\EmailSequenceStep;
use App\Models\EmailSequenceTemplate;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_sequence_enrollment_sends_step_and_completes_at_last_step(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-23 12:00:00'));
        config(['email_management.quiet_hours_start' => '21:00', 'email_management.quiet_hours_end' => '08:00', 'email_management.marketing_daily_limit' => 100, 'email_management.marketing_weekly_limit' => 100]);
        Mail::fake();
        $template = EmailTemplate::create(['name' => 'Sequence step', 'code' => 'sequence-step', 'email_type' => 'marketing', 'category' => 'follow_up', 'subject' => 'Follow up', 'html_content' => '<p>Follow up</p>', 'status' => 'published']);
        $sequence = EmailSequenceTemplate::create(['name' => 'Lead follow up', 'code' => 'lead-follow-up', 'status' => 'published']);
        EmailSequenceStep::create(['email_sequence_template_id' => $sequence->id, 'step_number' => 1, 'email_template_id' => $template->id, 'delay_value' => 0, 'delay_unit' => 'minutes', 'timezone' => 'Asia/Bangkok']);
        $subscriber = EmailSubscriber::create(['email' => 'sequence@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'sequence')]);
        $enrollment = EmailEnrollment::create(['email_subscriber_id' => $subscriber->id, 'email_sequence_template_id' => $sequence->id, 'status' => 'active', 'enrolled_at' => now(), 'next_scheduled_at' => now()]);

        Artisan::call('email:process-automation');

        $this->assertDatabaseHas('email_messages', ['email_enrollment_id' => $enrollment->id, 'to_email' => 'sequence@example.com']);
        $this->assertDatabaseHas('email_enrollments', ['id' => $enrollment->id, 'status' => 'completed', 'current_step' => 2]);
        Carbon::setTestNow();
    }

    public function test_sequence_prevents_duplicate_enrollment_for_same_subscriber(): void
    {
        $subscriber = EmailSubscriber::create(['email' => 'duplicate-sequence@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'duplicate-sequence')]);
        $sequence = EmailSequenceTemplate::create(['name' => 'Sequence', 'code' => 'duplicate-sequence', 'status' => 'published']);
        EmailEnrollment::create(['email_subscriber_id' => $subscriber->id, 'email_sequence_template_id' => $sequence->id, 'status' => 'active', 'enrolled_at' => now()]);
        $second = EmailEnrollment::firstOrCreate(['email_subscriber_id' => $subscriber->id, 'email_sequence_template_id' => $sequence->id], ['status' => 'active', 'enrolled_at' => now()]);

        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertDatabaseCount('email_enrollments', 1);
    }
}
