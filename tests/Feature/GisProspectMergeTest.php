<?php

namespace Tests\Feature;

use App\Mail\EnquiryReply;
use App\Models\GisEnquiry;
use App\Models\GisFairCampaign;
use App\Models\GisFairLead;
use App\Models\User;
use App\Services\GisFair\GisFairDashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GisProspectMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_gis_workspace_combines_direct_enquiries_with_case_insensitive_gis_fair_prefix(): void
    {
        $actor = $this->rootUser();
        GisEnquiry::factory()->create([
            'first_name' => 'Direct',
            'last_name' => 'Prospect',
            'email' => 'direct@example.com',
            'status' => 'lead_mql',
        ]);
        $included = $this->fairLead($this->campaign('gis', 'included-event'), 'Fair', 'Included');
        $excluded = $this->fairLead($this->campaign('OTHER', 'excluded-event'), 'Fair', 'Excluded');

        $response = $this->actingAs($actor)->get(route('gisEnquiry'));

        $response->assertOk()
            ->assertSee('Direct Prospect')
            ->assertSee('Fair Included')
            ->assertSee('GIS-Enquiry')
            ->assertSee('fair-funnel')
            ->assertSee($included->fair_code)
            ->assertDontSee('Fair Excluded')
            ->assertDontSee($excluded->fair_code);
    }

    public function test_gis_workspace_source_filter_and_mixed_bulk_action_target_the_original_models(): void
    {
        $actor = $this->rootUser();
        $enquiry = GisEnquiry::factory()->create([
            'first_name' => 'DirectOnly',
            'email' => 'direct-filter@example.com',
            'status' => 'lead_mql',
        ]);
        $fairLead = $this->fairLead($this->campaign('GIS', 'mixed-event'), 'FairOnly', 'Lead');

        $this->actingAs($actor)
            ->get(route('gisEnquiry', ['record_source' => 'fair_funnel']))
            ->assertOk()
            ->assertSee('FairOnly Lead')
            ->assertDontSee('DirectOnly');

        $this->actingAs($actor)
            ->post(route('gis-enquiries.bulk-action'), [
                'records' => ['gis_enquiry:'.$enquiry->id, 'fair_funnel:'.$fairLead->id],
                'action' => 'status',
                'status' => 'sql',
            ])
            ->assertRedirect();

        $this->assertSame('sql', $enquiry->fresh()->status);
        $this->assertSame('sql', $fairLead->fresh()->status);
    }

    public function test_mixed_bulk_action_cannot_target_a_fair_lead_outside_the_gis_prefix(): void
    {
        $actor = $this->rootUser();
        $fairLead = $this->fairLead($this->campaign('GMS', 'outside-event'), 'Outside', 'Lead');

        $this->actingAs($actor)
            ->from(route('gisEnquiry'))
            ->post(route('gis-enquiries.bulk-action'), [
                'records' => ['fair_funnel:'.$fairLead->id],
                'action' => 'status',
                'status' => 'customer',
            ])
            ->assertRedirect(route('gisEnquiry'))
            ->assertSessionHasErrors('ids');

        $this->assertSame('lead_mql', $fairLead->fresh()->status);
    }

    public function test_fair_prospect_supports_gis_reply_and_spam_review_workflows(): void
    {
        Mail::fake();
        $actor = $this->rootUser();
        $fairLead = $this->fairLead($this->campaign('GIS', 'workflow-event'), 'Workflow', 'Lead');

        $this->actingAs($actor)
            ->get(route('gis-fair.leads.reply', $fairLead))
            ->assertOk()
            ->assertSee($fairLead->email)
            ->assertSee('Workflow Event');

        $this->actingAs($actor)
            ->post(route('gis-fair.leads.reply.send', $fairLead), [
                'subject' => 'Re: workflow event registration',
                'message' => 'Thank you. Our team will contact you shortly.',
            ])
            ->assertRedirect(route('gisEnquiry'));

        Mail::assertSent(EnquiryReply::class, fn (EnquiryReply $mail) => $mail->hasTo($fairLead->email));

        $this->actingAs($actor)
            ->patch(route('gis-fair.leads.spam-status', $fairLead), ['spam_status' => 'suspected'])
            ->assertRedirect();

        $this->assertSame('suspected', $fairLead->fresh()->spam_status);
    }

    public function test_dashboard_metrics_and_charts_follow_the_selected_event_filter(): void
    {
        $actor = $this->rootUser();
        $selectedCampaign = $this->campaign('GIS', 'dashboard-selected');
        $otherCampaign = $this->campaign('OTHER', 'dashboard-other');
        $selectedLead = $this->fairLead($selectedCampaign, 'Selected', 'Customer', [
            'status' => 'customer',
            'marketing_consent' => true,
        ]);
        $otherLead = $this->fairLead($otherCampaign, 'Other', 'Prospect');
        $this->submission($selectedLead, 'facebook');
        $this->submission($selectedLead, 'facebook');
        $this->submission($otherLead, 'direct');

        $data = app(GisFairDashboardService::class)->data(['campaign_id' => $selectedCampaign->id]);

        $this->assertSame(2, $data['summary']['registrations']);
        $this->assertSame(1, $data['summary']['prospects']);
        $this->assertSame(1, $data['summary']['customers']);
        $this->assertSame(1, $data['summary']['repeat_submissions']);
        $this->assertSame(100.0, $data['summary']['conversion_rate']);

        $this->actingAs($actor)
            ->get(route('gis-fair.dashboard', ['campaign_id' => $selectedCampaign->id]))
            ->assertOk()
            ->assertSee('GIS Fair Dashboard')
            ->assertSee('registration-trend')
            ->assertSee('status-pipeline')
            ->assertSee('dashboard-selected')
            ->assertSee('2')
            ->assertSee('100%');
    }

    private function rootUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');

        return User::factory()->create(['primary_role_id' => $role->id]);
    }

    private function campaign(string $prefix, string $code): GisFairCampaign
    {
        return GisFairCampaign::create([
            'name' => str($code)->headline(),
            'code' => $code,
            'status' => 'active',
            'landing_url' => 'https://gis247.net/fair',
            'timezone' => 'Asia/Bangkok',
            'code_prefix' => $prefix,
            'privacy_notice_version' => '2026-09-02',
            'accepting_submissions' => true,
        ]);
    }

    private function fairLead(GisFairCampaign $campaign, string $firstName, string $lastName, array $overrides = []): GisFairLead
    {
        $lead = GisFairLead::create(array_merge([
            'campaign_id' => $campaign->id,
            'fair_code' => strtoupper($campaign->code).'-'.strtoupper(str()->random(6)),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName).str()->random(5).'@example.com',
            'company' => 'Example Company',
            'business_type' => 'Retail',
            'stores' => 1,
            'country' => 'Thailand',
            'phone_iso' => 'TH',
            'phone_local' => '0812345678',
            'phone_e164' => '+66812345678',
            'phone_dial_code' => '+66',
            'current_system' => 'None',
            'interests' => ['POS', 'CRM'],
            'source' => 'design_1',
            'marketing_consent' => false,
            'privacy_agreed' => true,
            'privacy_agreed_at' => now(),
            'privacy_notice_version' => '2026-09-02',
            'submission_count' => 1,
            'last_submitted_at' => now(),
        ], $overrides));

        $workflow = array_intersect_key($overrides, array_flip(['status', 'marketing_consent']));
        if ($workflow) {
            $lead->forceFill($workflow)->save();
        }

        return $lead;
    }

    private function submission(GisFairLead $lead, string $source): void
    {
        $lead->submissions()->create([
            'campaign_id' => $lead->campaign_id,
            'source' => $source,
            'privacy_agreed' => true,
            'privacy_notice_version' => '2026-09-02',
            'marketing_consent' => $lead->marketing_consent,
            'submitted_at' => now(),
        ]);
    }
}
