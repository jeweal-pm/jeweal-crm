<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Models\GisFairCampaign;
use App\Models\GisFairLead;
use App\Models\GmsStoneEnquiry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkEnquiryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_controls_and_centered_confirmation_are_available_on_every_enquiry_workspace(): void
    {
        $actor = $this->rootUser();
        Enquiry::factory()->create();
        GisEnquiry::factory()->create();
        GmsStoneEnquiry::factory()->create();
        $this->fairLead();

        foreach (['enquiry.index', 'gisEnquiry', 'gms-enquiries.index', 'gis-fair.leads.index'] as $route) {
            $this->actingAs($actor)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-bulk-select-all', false)
                ->assertSee('data-bulk-item', false)
                ->assertSee('data-app-confirm-overlay', false);
        }
    }

    public function test_root_can_apply_bulk_actions_across_all_enquiry_modules(): void
    {
        $actor = $this->rootUser();
        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $enquiries = Enquiry::factory()->count(2)->create();
        $gisEnquiries = GisEnquiry::factory()->count(2)->create();
        $gmsEnquiries = GmsStoneEnquiry::factory()->count(2)->create();
        $fairLead = $this->fairLead();

        $this->actingAs($actor)
            ->post(route('enquiries.bulk-action'), [
                'ids' => $enquiries->modelKeys(),
                'action' => 'status',
                'status' => 'sql',
            ])
            ->assertRedirect();

        $this->assertSame(2, Enquiry::where('status', 'sql')->count());

        $this->actingAs($actor)
            ->post(route('gis-enquiries.bulk-action'), [
                'ids' => $gisEnquiries->modelKeys(),
                'action' => 'assign',
                'user_id' => $sale->id,
            ])
            ->assertRedirect();

        $this->assertSame(2, GisEnquiry::where('assigned_to', $sale->id)->count());

        $this->actingAs($actor)
            ->post(route('gms-enquiries.bulk-action'), [
                'ids' => $gmsEnquiries->modelKeys(),
                'action' => 'delete',
            ])
            ->assertRedirect();

        foreach ($gmsEnquiries as $enquiry) {
            $this->assertSoftDeleted('gms_stone_enquiries', ['id' => $enquiry->id]);
        }

        $this->actingAs($actor)
            ->post(route('gms-enquiries.bulk-action'), [
                'ids' => $gmsEnquiries->modelKeys(),
                'action' => 'restore',
            ])
            ->assertRedirect();

        $this->assertSame(2, GmsStoneEnquiry::whereIn('id', $gmsEnquiries->modelKeys())->count());

        $this->actingAs($actor)
            ->post(route('gis-fair.leads.bulk-action'), [
                'ids' => [$fairLead->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('gis_fair_leads', ['id' => $fairLead->id]);
    }

    public function test_bulk_action_is_atomic_when_a_selected_record_is_unavailable(): void
    {
        $actor = $this->rootUser();
        $enquiry = Enquiry::factory()->create(['status' => 'lead_mql']);

        $this->actingAs($actor)
            ->from(route('enquiry.index'))
            ->post(route('enquiries.bulk-action'), [
                'ids' => [$enquiry->id, 999999],
                'action' => 'status',
                'status' => 'customer',
            ])
            ->assertRedirect(route('enquiry.index'))
            ->assertSessionHasErrors('ids');

        $this->assertSame('lead_mql', $enquiry->fresh()->status);
    }

    public function test_bulk_delete_requires_the_bulk_delete_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $sale = User::factory()->create();
        $sale->assignRole('sale');
        $enquiry = Enquiry::factory()->create(['assigned_to' => $sale->id]);

        $this->actingAs($sale)
            ->post(route('enquiries.bulk-action'), [
                'ids' => [$enquiry->id],
                'action' => 'delete',
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted('enquiries', ['id' => $enquiry->id]);
    }

    private function rootUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');

        return User::factory()->create(['primary_role_id' => $role->id]);
    }

    private function fairLead(): GisFairLead
    {
        $campaign = GisFairCampaign::query()->firstOrCreate([
            'code' => 'bulk-test-event',
        ], [
            'name' => 'Bulk Test Event',
            'status' => 'active',
            'landing_url' => 'https://gis247.net/fair',
            'timezone' => 'Asia/Bangkok',
            'code_prefix' => 'BULK',
            'privacy_notice_version' => '2026-09-01',
            'accepting_submissions' => true,
        ]);

        return GisFairLead::create([
            'campaign_id' => $campaign->id,
            'fair_code' => 'BULK-'.strtoupper(str()->random(8)),
            'first_name' => 'Bulk',
            'last_name' => 'Lead',
            'email' => str()->random(8).'@example.com',
            'company' => '',
            'business_type' => 'Retail',
            'stores' => 1,
            'country' => 'Thailand',
            'phone_iso' => 'TH',
            'phone_local' => '0812345678',
            'phone_e164' => '+66812345678',
            'phone_dial_code' => '+66',
            'current_system' => 'None',
            'interests' => ['POS'],
            'source' => 'design_1',
            'marketing_consent' => false,
            'privacy_agreed' => true,
            'privacy_agreed_at' => now(),
            'privacy_notice_version' => '2026-09-01',
            'submission_count' => 1,
            'last_submitted_at' => now(),
        ]);
    }
}
