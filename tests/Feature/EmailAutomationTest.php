<?php

namespace Tests\Feature;

use App\Mail\ManagedEmailMailable;
use App\Models\EmailAutomationConfig;
use App\Models\EmailMessage;
use App\Models\EmailSubscriber;
use Database\Seeders\EmailManagementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_general_enquiry_queues_customer_and_internal_messages(): void
    {
        Mail::fake();
        config(['email_management.internal_recipients' => ['ops@example.com']]);
        $this->seed(EmailManagementSeeder::class);

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.21'])->postJson('/api/enquiry', [
            'name' => 'Automation Lead', 'business_type' => ['retail'], 'email' => 'automation@example.com',
            'country' => 'Thailand', 'phone' => '+66810000000', 'company' => 'Automation Co',
            'company_website' => 'https://example.com', 'description' => 'Need a demo', 'interest_in' => ['crm'],
        ])->assertCreated();

        $subscriber = EmailSubscriber::where('email', 'automation@example.com')->firstOrFail();
        $this->assertSame('pending_confirmation', $subscriber->subscription_status);
        $this->assertDatabaseCount('email_messages', 2);
        $this->assertDatabaseHas('email_messages', ['message_type' => 'transactional', 'to_email' => 'automation@example.com']);
        $this->assertDatabaseHas('email_messages', ['message_type' => 'internal', 'to_email' => 'ops@example.com']);
        Mail::assertSent(ManagedEmailMailable::class, 2);
    }

    public function test_transactional_message_is_idempotent_when_service_is_called_again(): void
    {
        config(['email_management.internal_recipients' => []]);
        $this->seed(EmailManagementSeeder::class);
        $enquiry = \App\Models\Enquiry::factory()->create(['email' => 'repeat@example.com']);
        $service = app(\App\Services\Email\EnquiryEmailAutomationService::class);

        Mail::fake();
        $service->dispatchFor($enquiry, 'general');
        $service->dispatchFor($enquiry, 'general');

        $this->assertSame(1, EmailMessage::where('message_type', 'transactional')->count());
    }

    public function test_internal_message_uses_environment_recipients_when_config_recipients_are_empty(): void
    {
        Mail::fake();
        config(['email_management.internal_recipients' => []]);
        $this->seed(EmailManagementSeeder::class);
        config(['email_management.internal_recipients' => ['fallback-ops@example.com']]);

        EmailAutomationConfig::where('enquiry_type', 'general')->update([
            'internal_enabled' => true,
            'internal_to' => [],
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.22'])->postJson('/api/enquiry', [
            'name' => 'Fallback Lead', 'business_type' => ['retail'], 'email' => 'fallback@example.com',
            'country' => 'Thailand', 'phone' => '+66810000000', 'company' => 'Fallback Co',
            'company_website' => 'https://example.com', 'description' => 'Need a demo', 'interest_in' => ['crm'],
        ])->assertCreated();

        $this->assertDatabaseHas('email_messages', [
            'message_type' => 'internal',
            'to_email' => 'fallback-ops@example.com',
        ]);
    }

    public function test_new_gis_enquiry_queues_branded_customer_and_internal_messages(): void
    {
        Mail::fake();
        config([
            'email_management.internal_recipients' => ['gis-team@example.com'],
            'email_management.sender_addresses.gis' => 'gis@example.com',
        ]);
        $this->seed(EmailManagementSeeder::class);
        EmailAutomationConfig::where('enquiry_type', 'gis')->update(['internal_cc' => ['gis-cc@example.com']]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.20'])
            ->postJson('/api/gis-enquiry', [
                'first_name' => 'GIS', 'last_name' => 'Requester', 'email' => 'gis-requester@example.com',
                'phone_number' => '+66810000000', 'inquiry' => 'Request quotation', 'message' => 'Please contact me.',
            ])->assertCreated();

        $this->assertDatabaseCount('email_messages', 2);
        $this->assertDatabaseHas('email_messages', ['message_type' => 'transactional', 'to_email' => 'gis-requester@example.com']);
        $this->assertDatabaseHas('email_messages', ['message_type' => 'internal', 'to_email' => 'gis-team@example.com']);

        $customerMessage = EmailMessage::where('message_type', 'transactional')->firstOrFail();
        $internalMessage = EmailMessage::where('message_type', 'internal')->firstOrFail();
        $this->assertStringContainsString('GIS Manage Pro', $customerMessage->html_content);
        $this->assertStringContainsString('GIS Manage Pro', $internalMessage->html_content);
        $this->assertSame(['gis-cc@example.com'], $internalMessage->cc);
        Mail::assertSent(ManagedEmailMailable::class, fn ($mail) => $mail->senderEmail === 'gis@example.com');
        Mail::assertSent(ManagedEmailMailable::class, 2);
    }
}
