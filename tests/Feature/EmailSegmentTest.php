<?php

namespace Tests\Feature;

use App\Models\EmailSegment;
use App\Models\EmailSubscriber;
use App\Models\Enquiry;
use App\Services\Email\EmailSegmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSegmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_segment_filters_by_subscription_source_and_customer_status(): void
    {
        $customer = Enquiry::factory()->create(['status' => 'customer']);
        $lead = Enquiry::factory()->create(['status' => 'lead_mql']);
        EmailSubscriber::create(['email' => 'customer@example.com', 'source_type' => 'general', 'source_id' => $customer->id, 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'customer')]);
        EmailSubscriber::create(['email' => 'lead@example.com', 'source_type' => 'general', 'source_id' => $lead->id, 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'lead')]);

        $segment = EmailSegment::create(['name' => 'Customers', 'code' => 'customers', 'segment_type' => 'dynamic', 'conditions' => ['source_type' => 'general', 'customer_status' => 'customer']]);
        $members = app(EmailSegmentService::class)->members($segment)->pluck('email');

        $this->assertSame(['customer@example.com'], $members->all());
    }

    public function test_static_segment_keeps_a_snapshot_after_source_changes(): void
    {
        $subscriber = EmailSubscriber::create(['email' => 'snapshot@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'snapshot')]);
        $segment = EmailSegment::create(['name' => 'Snapshot', 'code' => 'snapshot', 'segment_type' => 'static', 'conditions' => ['subscription_status' => 'subscribed']]);
        $count = app(EmailSegmentService::class)->refreshStatic($segment);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('email_segment_memberships', ['email_segment_id' => $segment->id, 'email_subscriber_id' => $subscriber->id, 'is_snapshot' => 1]);
    }
}
