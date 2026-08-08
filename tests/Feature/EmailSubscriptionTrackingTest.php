<?php

namespace Tests\Feature;

use App\Models\EmailMessage;
use App\Models\EmailSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSubscriptionTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_and_click_tracking_record_events_without_exposing_recipient_email(): void
    {
        $subscriber = EmailSubscriber::create(['email' => 'tracked@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'tracked')]);
        $message = EmailMessage::create([
            'message_id' => '11111111-1111-4111-8111-111111111111', 'idempotency_key' => 'tracking-test', 'email_subscriber_id' => $subscriber->id,
            'message_type' => 'marketing', 'to_email' => $subscriber->email, 'subject' => 'Tracked', 'html_content' => '<p>Tracked</p>', 'status' => 'sent',
        ]);

        $this->get('/email-track/open/'.$message->message_id)->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->get('/email-track/click/'.$message->message_id.'?url='.rawurlencode('https://example.com/pricing'))->assertRedirect('https://example.com/pricing');

        $this->assertDatabaseHas('email_events', ['email_message_id' => $message->id, 'event_type' => 'opened']);
        $this->assertDatabaseHas('email_events', ['email_message_id' => $message->id, 'event_type' => 'clicked', 'url' => 'https://example.com/pricing']);
        $this->assertDatabaseMissing('email_events', ['metadata' => json_encode(['email' => $subscriber->email])]);
    }

    public function test_brevo_webhook_updates_delivery_status_and_suppresses_hard_bounce(): void
    {
        $subscriber = EmailSubscriber::create(['email' => 'bounce@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'bounce')]);
        $message = EmailMessage::create([
            'message_id' => '22222222-2222-4222-8222-222222222222', 'idempotency_key' => 'bounce-test', 'email_subscriber_id' => $subscriber->id,
            'message_type' => 'marketing', 'to_email' => $subscriber->email, 'subject' => 'Bounce', 'html_content' => '<p>Bounce</p>', 'status' => 'sent',
        ]);

        $this->postJson('/api/email/webhooks/brevo', ['messageId' => $message->message_id, 'event' => 'hard_bounce'])->assertOk();

        $this->assertDatabaseHas('email_messages', ['id' => $message->id, 'status' => 'hard_bounce']);
        $this->assertDatabaseHas('email_subscribers', ['id' => $subscriber->id, 'subscription_status' => 'hard_bounce']);
        $this->assertDatabaseHas('email_suppression_lists', ['email' => 'bounce@example.com', 'category' => 'all_marketing']);
    }
}
