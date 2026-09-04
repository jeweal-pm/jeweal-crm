<?php

namespace Tests\Feature;

use App\Models\EmailAutomationConfig;
use App\Models\EmailMessage;
use App\Models\GisFairCampaign;
use App\Models\GisFairLead;
use App\Models\GisFairTrackingLink;
use App\Models\User;
use Database\Seeders\GisFairFunnelSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GisFairFunnelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_redirect_attributes_a_valid_registration_to_the_event_and_link(): void
    {
        $campaign = $this->campaign();
        $link = GisFairTrackingLink::create([
            'campaign_id' => $campaign->id,
            'name' => 'Facebook launch',
            'code' => 'bgjf74-facebook',
            'source' => 'facebook',
            'medium' => 'social',
            'is_active' => true,
        ]);

        $redirect = $this->withHeader('referer', 'https://facebook.com/example')
            ->get(route('gis-fair.redirect', $link->code))
            ->assertRedirect();

        parse_str((string) parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame($campaign->code, $query['event']);
        $this->assertNotEmpty($query['ref']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.40'])
            ->postJson('/api/gis-fair-lead', $this->payload([
                'eventCode' => $query['event'],
                'trackingToken' => $query['ref'],
            ]))
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('data.eventCode', 'bgjf-74')
            ->assertJsonPath('data.status', 'prospect')
            ->assertJsonPath('data.remark', 'Interested in a product demonstration.');

        $lead = GisFairLead::firstOrFail();
        $this->assertSame($campaign->id, $lead->campaign_id);
        $this->assertSame($link->id, $lead->tracking_link_id);
        $this->assertSame($response->json('fairCode'), $lead->fair_code);
        $this->assertSame('prospect', $lead->status);
        $this->assertSame('Interested in a product demonstration.', $lead->remark);
        $this->assertDatabaseHas('gis_fair_tracking_visits', [
            'token' => $query['ref'],
            'lead_id' => $lead->id,
        ]);
        $this->assertDatabaseHas('gis_fair_lead_submissions', [
            'lead_id' => $lead->id,
            'privacy_notice_version' => '2026-08-31',
            'marketing_consent' => true,
        ]);
        $this->assertSame(1, $link->fresh()->lead_count);
    }

    public function test_expired_event_uses_the_tracking_link_fallback_without_recording_a_visit(): void
    {
        $campaign = $this->campaign([
            'ends_at' => now()->subMinute(),
            'offer_deadline' => now()->addWeek(),
        ]);
        $link = GisFairTrackingLink::create([
            'campaign_id' => $campaign->id,
            'name' => 'Expired campaign link',
            'code' => 'expired-campaign-link',
            'expired_redirect_url' => 'https://gis247.net/events/bgjf-74',
            'is_active' => true,
            'expires_at' => now()->addMonth(),
        ]);

        $this->get(route('gis-fair.redirect', $link->code))
            ->assertRedirect('https://gis247.net/events/bgjf-74')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertDatabaseCount('gis_fair_tracking_visits', 0);
        $this->assertSame(0, $link->fresh()->click_count);
    }

    public function test_unavailable_event_link_redirects_to_jeweal_when_no_main_website_is_configured(): void
    {
        $campaign = $this->campaign();
        $link = GisFairTrackingLink::create([
            'campaign_id' => $campaign->id,
            'name' => 'Inactive link',
            'code' => 'inactive-campaign-link',
            'is_active' => false,
        ]);

        $this->get(route('gis-fair.redirect', $link->code))
            ->assertRedirect('https://jeweal.com');

        $this->assertDatabaseCount('gis_fair_tracking_visits', 0);
        $this->assertSame(0, $link->fresh()->click_count);
    }

    public function test_same_email_is_deduplicated_within_an_event_and_keeps_consent_evidence(): void
    {
        $this->campaign();

        $first = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.41'])
            ->postJson('/api/gis-fair-lead', $this->payload())
            ->assertCreated();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->postJson('/api/gis-fair-lead', $this->payload([
                'company' => 'Updated Company',
                'remark' => 'Please contact after the fair.',
                'consent' => false,
            ]))
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('fairCode', $first->json('fairCode'));

        $lead = GisFairLead::firstOrFail();
        $this->assertSame(2, $lead->submission_count);
        $this->assertSame('Updated Company', $lead->company);
        $this->assertSame('Please contact after the fair.', $lead->remark);
        $this->assertFalse($lead->marketing_consent);
        $this->assertNotNull($lead->marketing_consent_withdrawn_at);
        $this->assertSame(2, $lead->submissions()->count());
    }

    public function test_same_email_can_register_for_different_events(): void
    {
        $firstCampaign = $this->campaign(['code' => 'event-one']);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.43'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $firstCampaign->code]))
            ->assertCreated();

        $firstCampaign->update(['status' => 'closed']);
        $secondCampaign = $this->campaign(['code' => 'event-two', 'name' => 'Second Event']);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $secondCampaign->code]))
            ->assertCreated();

        $this->assertSame(2, GisFairLead::where('email', 'somchai@example.com')->count());
    }

    public function test_privacy_acknowledgement_is_required_and_honeypot_is_silently_accepted(): void
    {
        $this->campaign();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.45'])
            ->postJson('/api/gis-fair-lead', $this->payload(['privacyAgree' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('privacyAgree');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.46'])
            ->postJson('/api/gis-fair-lead', ['website' => 'https://bot.example'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseCount('gis_fair_leads', 0);
    }

    public function test_closed_event_rejects_registration_and_public_config_identifies_the_event(): void
    {
        $campaign = $this->campaign();

        $this->getJson('/api/gis-fair-config?event='.$campaign->code)
            ->assertOk()
            ->assertJsonPath('eventCode', $campaign->code)
            ->assertJsonPath('acceptingSubmissions', true);

        $campaign->update(['accepting_submissions' => false]);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.47'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $campaign->code]))
            ->assertStatus(410);
    }

    public function test_invalid_or_mismatched_tracking_token_is_rejected(): void
    {
        $campaign = $this->campaign();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.48'])
            ->postJson('/api/gis-fair-lead', $this->payload([
                'eventCode' => $campaign->code,
                'trackingToken' => '2af53bd0-7fc2-4b35-8ab6-99db85af7b60',
            ]))
            ->assertStatus(422);

        $this->assertDatabaseCount('gis_fair_leads', 0);
    }

    public function test_funnel_seeder_and_submission_queue_the_fair_code_email(): void
    {
        Mail::fake();
        $this->seed(GisFairFunnelSeeder::class);
        $campaign = GisFairCampaign::where('code', 'bgjf-74')->firstOrFail();
        $campaign->update(['status' => 'active', 'offer_deadline' => now()->addWeek()]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.49'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $campaign->code]))
            ->assertCreated();

        $this->assertDatabaseHas('email_templates', ['code' => 'gis-fair-registration-confirmation', 'status' => 'published']);
        $this->assertDatabaseHas('email_automation_configs', ['enquiry_type' => 'gis_fair', 'customer_enabled' => true]);
        $this->assertDatabaseHas('email_messages', [
            'to_email' => 'somchai@example.com',
            'subject' => 'Your The 74th Bangkok Gems & Jewelry Fair fair code - '.$response->json('fairCode'),
            'status' => 'sent',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.52'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $campaign->code]))
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(2, EmailMessage::where('message_type', 'transactional')->count());
        $this->assertSame(2, GisFairLead::firstOrFail()->confirmation_send_count);
    }

    public function test_public_funnel_api_handles_cors_preflight(): void
    {
        $this->call('OPTIONS', '/api/gis-fair-lead', [], [], [], [
            'HTTP_ORIGIN' => 'https://funnel.gis247.net',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ])
            ->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin');
    }

    public function test_root_can_manage_events_and_open_the_lead_workspace(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $role->id]);
        $campaign = $this->campaign();

        $this->actingAs($user)
            ->get(route('gis-fair.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Tracking URLs')
            ->assertSee('Expired redirect URL')
            ->assertSee('/api/gis-fair-lead');

        $this->actingAs($user)
            ->post(route('gis-fair.links.store', $campaign), [
                'name' => 'Partner campaign',
                'code' => 'partner-campaign',
                'destination_url' => 'https://gis247.net/fair',
                'expired_redirect_url' => 'https://gis247.net/events',
                'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('gis_fair_tracking_links', [
            'campaign_id' => $campaign->id,
            'code' => 'partner-campaign',
            'expired_redirect_url' => 'https://gis247.net/events',
        ]);

        $this->actingAs($user)
            ->get(route('gis-fair.leads.index'))
            ->assertOk()
            ->assertSee('GIS Fair Leads')
            ->assertSee('GIS Fair Funnel');
    }

    public function test_resending_a_fair_code_does_not_repeat_the_internal_notification(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(GisFairFunnelSeeder::class);
        $role = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $role->id]);
        $campaign = GisFairCampaign::where('code', 'bgjf-74')->firstOrFail();
        $campaign->update(['status' => 'active', 'offer_deadline' => now()->addWeek()]);
        EmailAutomationConfig::where('enquiry_type', 'gis_fair')->update([
            'internal_enabled' => true,
            'internal_to' => ['fair-team@example.com'],
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $campaign->code]))
            ->assertCreated();

        $lead = GisFairLead::firstOrFail();
        $this->actingAs($user)
            ->post(route('gis-fair.leads.resend', $lead))
            ->assertRedirect();

        $this->assertSame(2, EmailMessage::where('message_type', 'transactional')->count());
        $this->assertSame(1, EmailMessage::where('message_type', 'internal')->count());
        $this->assertSame(2, $lead->fresh()->confirmation_send_count);
    }

    public function test_authorized_user_can_open_a_soft_deleted_lead_record(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $role->id]);
        $campaign = $this->campaign();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.51'])
            ->postJson('/api/gis-fair-lead', $this->payload(['eventCode' => $campaign->code]))
            ->assertCreated();

        $lead = GisFairLead::firstOrFail();
        $lead->softDeleteBy($user);

        $this->actingAs($user)
            ->get(route('gis-fair.leads.show', $lead->id))
            ->assertOk()
            ->assertSee($lead->fair_code);
    }

    private function campaign(array $overrides = []): GisFairCampaign
    {
        return GisFairCampaign::create(array_merge([
            'name' => 'The 74th Bangkok Gems & Jewelry Fair',
            'code' => 'bgjf-74',
            'status' => 'active',
            'landing_url' => 'https://gis247.net/fair/',
            'dates_display' => '10-14 September 2026',
            'offer_deadline' => now()->addWeek(),
            'timezone' => 'Asia/Bangkok',
            'code_prefix' => 'GIS74',
            'privacy_notice_version' => '2026-08-31',
            'accepting_submissions' => true,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Somchai',
            'lastName' => 'Jaidee',
            'email' => 'Somchai@Example.com',
            'company' => 'Jaidee Gold Co., Ltd.',
            'businessType' => 'Retail',
            'stores' => 3,
            'phoneIso' => 'TH',
            'phone' => '0961234567',
            'country' => 'Thailand',
            'currentSystem' => 'None — spreadsheets & paper',
            'interests' => ['POS', 'Inventory', 'CRM'],
            'remark' => 'Interested in a product demonstration.',
            'consent' => true,
            'privacyAgree' => true,
            'source' => 'design_2',
        ], $overrides);
    }
}
