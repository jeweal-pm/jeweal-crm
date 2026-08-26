<?php

namespace Tests\Feature;

use App\Models\EmailEnrollment;
use App\Models\EmailSequenceStep;
use App\Models\EmailSequenceTemplate;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
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

    public function test_root_can_manage_sequence_steps_from_the_detail_page(): void
    {
        $user = $this->rootUser();
        $template = EmailTemplate::create([
            'name' => 'Follow-up', 'code' => 'sequence-follow-up', 'email_type' => 'marketing',
            'category' => 'follow_up', 'subject' => 'Follow up', 'html_content' => '<p>Follow up</p>', 'status' => 'published',
        ]);

        $this->actingAs($user)->get(route('email.sequences'))->assertOk()->assertSee('Sequence library');
        $this->actingAs($user)->get(route('email.sequences.create'))->assertOk()->assertSee('New Email Sequence');
        $this->actingAs($user)->post(route('email.sequences.store'), [
            'name' => 'GIS follow-up', 'code' => 'gis-follow-up', 'description' => 'A GIS sequence',
        ])->assertRedirect();

        $sequence = EmailSequenceTemplate::where('code', 'gis-follow-up')->firstOrFail();
        $this->assertSame('draft', $sequence->status);
        $this->actingAs($user)->get(route('email.sequences.show', $sequence->id))->assertOk()->assertSee('Add step 1');
        $this->actingAs($user)->post(route('email.sequences.steps.store', $sequence->id), [
            'step_number' => 1, 'email_template_id' => $template->id, 'delay_value' => 0, 'delay_unit' => 'minutes',
        ])->assertRedirect(route('email.sequences.show', $sequence->id));

        $step = EmailSequenceStep::where('email_sequence_template_id', $sequence->id)->firstOrFail();
        $this->actingAs($user)->put(route('email.sequences.steps.update', [$sequence->id, $step->id]), [
            'step_number' => 1, 'email_template_id' => $template->id, 'delay_value' => 2, 'delay_unit' => 'days',
        ])->assertRedirect(route('email.sequences.show', $sequence->id));
        $this->assertDatabaseHas('email_sequence_steps', ['id' => $step->id, 'delay_value' => 2, 'delay_unit' => 'days']);

        $this->actingAs($user)->delete(route('email.sequences.steps.destroy', [$sequence->id, $step->id]))->assertRedirect(route('email.sequences.show', $sequence->id));
        $this->assertDatabaseMissing('email_sequence_steps', ['id' => $step->id]);
    }

    public function test_paused_sequence_does_not_process_active_enrollments(): void
    {
        Mail::fake();
        $template = EmailTemplate::create(['name' => 'Paused step', 'code' => 'paused-step', 'email_type' => 'marketing', 'category' => 'follow_up', 'subject' => 'Paused', 'html_content' => '<p>Paused</p>', 'status' => 'published']);
        $sequence = EmailSequenceTemplate::create(['name' => 'Paused sequence', 'code' => 'paused-sequence', 'status' => 'paused']);
        EmailSequenceStep::create(['email_sequence_template_id' => $sequence->id, 'step_number' => 1, 'email_template_id' => $template->id, 'delay_value' => 0, 'delay_unit' => 'minutes']);
        $subscriber = EmailSubscriber::create(['email' => 'paused@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'paused')]);
        $enrollment = EmailEnrollment::create(['email_subscriber_id' => $subscriber->id, 'email_sequence_template_id' => $sequence->id, 'status' => 'active', 'enrolled_at' => now(), 'next_scheduled_at' => now()]);

        Artisan::call('email:process-automation');

        $this->assertDatabaseMissing('email_messages', ['email_enrollment_id' => $enrollment->id]);
        $this->assertDatabaseHas('email_enrollments', ['id' => $enrollment->id, 'status' => 'active', 'current_step' => 1]);
    }

    public function test_root_can_remove_an_enrollment_and_suppress_pending_messages(): void
    {
        $user = $this->rootUser();
        $sequence = EmailSequenceTemplate::create(['name' => 'Remove enrollment', 'code' => 'remove-enrollment', 'status' => 'published']);
        $subscriber = EmailSubscriber::create(['email' => 'remove@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'remove')]);
        $enrollment = EmailEnrollment::create(['email_subscriber_id' => $subscriber->id, 'email_sequence_template_id' => $sequence->id, 'status' => 'active', 'enrolled_at' => now(), 'next_scheduled_at' => now()]);
        $message = \App\Models\EmailMessage::create([
            'message_id' => (string) Str::uuid(), 'idempotency_key' => 'remove-enrollment-message',
            'email_subscriber_id' => $subscriber->id, 'email_enrollment_id' => $enrollment->id,
            'message_type' => 'marketing', 'to_email' => $subscriber->email, 'subject' => 'Pending',
            'html_content' => '<p>Pending</p>', 'status' => 'queued', 'queued_at' => now(),
        ]);

        $this->actingAs($user)->delete(route('email.enrollments.destroy', $enrollment->id))->assertRedirect(route('email.enrollments'));

        $this->assertDatabaseMissing('email_enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseHas('email_messages', ['id' => $message->id, 'status' => 'suppressed', 'failure_reason' => 'Sequence enrollment removed']);
    }

    private function rootUser()
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');
        $user = \App\Models\User::factory()->create(['primary_role_id' => $role->id]);
        $user->assignRole($role);

        return $user;
    }
}
