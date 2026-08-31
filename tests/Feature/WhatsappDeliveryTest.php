<?php

namespace Tests\Feature;

use App\Models\IpRateLimitConfig;
use App\Models\TwilioConfiguration;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_sends_message_through_twilio_and_returns_200(): void
    {
        $this->configureDelivery();
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/whatsapp/messages', [
                'phone_number' => '+66 91 234 5678',
                'message' => 'Your quotation is ready.',
                'reference_id' => 'QUOTE-1001',
            ]);

        $response->assertOk()->assertJsonPath('status', 'sent');
        $this->assertDatabaseHas('whatsapp_messages', [
            'recipient_normalized' => '+66912345678',
            'status' => WhatsappMessage::STATUS_SENT,
            'provider_message_sid' => 'SM123',
        ]);
        Http::assertSent(fn ($request) => $request['To'] === 'whatsapp:+66912345678'
            && $request['From'] === 'whatsapp:+14155238886');
    }

    public function test_duplicate_number_is_rejected_with_409(): void
    {
        $this->configureDelivery();
        $this->message(['recipient_normalized' => '+66912345678']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->postJson('/api/whatsapp/messages', [
                'phone_number' => '+66912345678',
                'message' => 'Duplicate request',
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'recipient_already_exists');
    }

    public function test_daily_limit_queues_message_and_returns_202(): void
    {
        $this->configureDelivery(['daily_limit' => 1]);
        $this->message([
            'recipient_normalized' => '+66900000001',
            'status' => WhatsappMessage::STATUS_SENT,
            'sent_at' => now(),
        ]);
        Http::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12'])
            ->postJson('/api/whatsapp/messages', [
                'phone_number' => '+66900000002',
                'message' => 'Queue this message',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.wait_reason', WhatsappMessage::WAIT_DAILY_LIMIT);

        Http::assertNothingSent();
    }

    public function test_twilio_failure_is_saved_for_retry_and_returns_202(): void
    {
        $this->configureDelivery();
        Http::fake(['api.twilio.com/*' => Http::response(['code' => 63016, 'message' => 'Template required'], 400)]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.13'])
            ->postJson('/api/whatsapp/messages', [
                'phone_number' => '+66900000003',
                'message' => 'Retry this message',
            ])
            ->assertStatus(202)
            ->assertJsonPath('status', WhatsappMessage::STATUS_WAITING);

        $this->assertDatabaseHas('whatsapp_messages', [
            'recipient_normalized' => '+66900000003',
            'wait_reason' => WhatsappMessage::WAIT_PROVIDER_FAILURE,
            'provider_error_code' => '63016',
            'attempts' => 1,
        ]);
    }

    public function test_scheduler_command_sends_due_waiting_message(): void
    {
        $this->configureDelivery();
        $message = $this->message([
            'recipient_normalized' => '+66900000004',
            'status' => WhatsappMessage::STATUS_WAITING,
            'next_attempt_at' => now()->subMinute(),
        ]);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM-RETRY', 'status' => 'queued'], 201)]);

        $this->artisan('whatsapp:process-waiting')->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'status' => WhatsappMessage::STATUS_SENT,
            'provider_message_sid' => 'SM-RETRY',
        ]);
    }

    private function configureDelivery(array $overrides = []): TwilioConfiguration
    {
        IpRateLimitConfig::create([
            'module' => IpRateLimitConfig::MODULE_WHATSAPP,
            'label' => 'WhatsApp form',
            'is_enabled' => true,
            'max_attempts' => 50,
            'window_seconds' => 86400,
            'cooldown_seconds' => 0,
        ]);

        return TwilioConfiguration::create(array_merge([
            'provider' => 'twilio',
            'is_enabled' => true,
            'account_sid' => 'AC123',
            'api_key_sid' => 'SK123',
            'api_key_secret' => 'secret-value',
            'whatsapp_from' => '+14155238886',
            'daily_limit' => 100,
            'max_retry_attempts' => 3,
            'retry_delays_seconds' => [60, 300, 900],
            'timezone' => 'Asia/Bangkok',
        ], $overrides));
    }

    private function message(array $overrides = []): WhatsappMessage
    {
        $recipient = $overrides['recipient_normalized'] ?? '+66999999999';

        return WhatsappMessage::create(array_merge([
            'public_id' => (string) Str::uuid(),
            'recipient' => $recipient,
            'recipient_normalized' => $recipient,
            'body' => 'Test message',
            'source_module' => 'whatsapp',
            'status' => WhatsappMessage::STATUS_WAITING,
            'attempts' => 0,
            'max_attempts' => 3,
            'source_ip' => '203.0.113.1',
            'source_ip_hash' => hash('sha256', '203.0.113.1'),
        ], $overrides));
    }
}
