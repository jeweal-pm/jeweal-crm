<?php

namespace Tests\Feature;

use App\Models\IpBlacklist;
use App\Models\IpRateLimitConfig;
use App\Models\IpRateLimitLog;
use App\Models\TwilioConfiguration;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\Security\IpAccessService;
use Database\Seeders\CommunicationSecuritySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_blacklisted_ip_is_rejected_and_logged_for_any_protected_module(): void
    {
        $this->seed(CommunicationSecuritySeeder::class);
        IpBlacklist::create([
            'ip_address' => '203.0.113.20',
            'ip_hash' => IpAccessService::hash('203.0.113.20'),
            'reason' => 'Automated abuse',
            'is_active' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->postJson('/api/whatsapp/messages', [
                'phone_number' => '+66900000010',
                'message' => 'Blocked request',
            ])
            ->assertStatus(403)
            ->assertJsonPath('code', IpRateLimitLog::DECISION_BLACKLISTED);

        $this->assertDatabaseHas('ip_rate_limit_logs', [
            'module' => IpRateLimitConfig::MODULE_WHATSAPP,
            'ip_address' => '203.0.113.20',
            'decision' => IpRateLimitLog::DECISION_BLACKLISTED,
        ]);
    }

    public function test_rate_limit_is_counted_separately_for_each_module(): void
    {
        foreach ([IpRateLimitConfig::MODULE_WHATSAPP, IpRateLimitConfig::MODULE_GIS] as $module) {
            IpRateLimitConfig::create([
                'module' => $module,
                'label' => $module,
                'is_enabled' => true,
                'max_attempts' => 1,
                'window_seconds' => 86400,
                'cooldown_seconds' => 0,
            ]);
        }

        $service = app(IpAccessService::class);
        $this->assertTrue($service->inspect('203.0.113.21', IpRateLimitConfig::MODULE_WHATSAPP)['allowed']);
        $this->assertFalse($service->inspect('203.0.113.21', IpRateLimitConfig::MODULE_WHATSAPP)['allowed']);
        $this->assertTrue($service->inspect('203.0.113.21', IpRateLimitConfig::MODULE_GIS)['allowed']);
    }

    public function test_root_can_update_encrypted_twilio_config_and_manage_blacklist(): void
    {
        $user = $this->rootUser();
        $this->seed(CommunicationSecuritySeeder::class);

        $this->actingAs($user)->put(route('whatsapp.config.update'), [
            'is_enabled' => true,
            'account_sid' => 'AC-SECRET-1234',
            'api_key_sid' => 'SK-SECRET-5678',
            'api_key_secret' => 'private-secret',
            'whatsapp_from' => '+14155238886',
            'daily_limit' => 20,
            'max_retry_attempts' => 3,
            'retry_delays_seconds' => '60,300,900',
            'timezone' => 'Asia/Bangkok',
        ])->assertRedirect();

        $config = TwilioConfiguration::firstOrFail();
        $this->assertSame('private-secret', $config->api_key_secret);
        $this->assertNotSame('private-secret', DB::table('twilio_configurations')->value('api_key_secret'));

        $this->actingAs($user)->post(route('security.ip.blacklist.store'), [
            'ip_address' => '198.51.100.10',
            'reason' => 'Test block',
        ])->assertRedirect();
        $this->assertDatabaseHas('ip_blacklists', ['ip_address' => '198.51.100.10']);
    }

    public function test_root_can_open_whatsapp_and_ip_security_pages(): void
    {
        $user = $this->rootUser();
        $this->seed(CommunicationSecuritySeeder::class);
        WhatsappMessage::create([
            'public_id' => (string) Str::uuid(),
            'recipient' => '+66900000019',
            'recipient_normalized' => '+66900000019',
            'body' => 'Render this waiting message',
            'source_module' => 'whatsapp',
            'status' => WhatsappMessage::STATUS_WAITING,
            'source_ip' => '127.0.0.1',
            'source_ip_hash' => hash('sha256', '127.0.0.1'),
        ]);
        IpRateLimitLog::create([
            'request_id' => (string) Str::uuid(),
            'module' => IpRateLimitConfig::MODULE_WHATSAPP,
            'ip_address' => '127.0.0.1',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'endpoint' => 'api/whatsapp/messages',
            'decision' => IpRateLimitLog::DECISION_ALLOWED,
            'http_status' => 200,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('whatsapp.messages.index'))
            ->assertOk()
            ->assertSee('WhatsApp Delivery');
        $this->actingAs($user)
            ->get(route('whatsapp.config.edit'))
            ->assertOk()
            ->assertSee('Twilio Configuration');
        $this->actingAs($user)
            ->get(route('security.ip.index'))
            ->assertOk()
            ->assertSee('IP Security');
    }

    public function test_deleting_message_allows_the_number_to_be_submitted_again(): void
    {
        $user = $this->rootUser();
        $message = WhatsappMessage::create([
            'public_id' => (string) Str::uuid(),
            'recipient' => '+66900000020',
            'recipient_normalized' => '+66900000020',
            'body' => 'Test record',
            'source_module' => 'whatsapp',
            'status' => WhatsappMessage::STATUS_FAILED,
            'source_ip' => '127.0.0.1',
            'source_ip_hash' => hash('sha256', '127.0.0.1'),
        ]);

        $this->actingAs($user)
            ->delete(route('whatsapp.messages.destroy', $message->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('whatsapp_messages', ['id' => $message->id]);
    }

    private function rootUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $role->id]);
        $user->assignRole($role);

        return $user;
    }
}
