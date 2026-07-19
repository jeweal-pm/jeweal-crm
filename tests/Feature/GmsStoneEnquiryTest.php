<?php

namespace Tests\Feature;

use App\Models\GmsStoneEnquiry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GmsStoneEnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(storage_path('app/rate_limit'));
    }

    public function test_root_user_can_open_gms_enquiry_page_from_menu(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $rootRole->id]);

        GmsStoneEnquiry::factory()->create([
            'full_name' => 'GMS Lead',
            'email' => 'gms-lead@example.com',
        ]);

        $this->actingAs($user)
            ->get('/gms-enquiry')
            ->assertOk()
            ->assertSee('GMS Enquiries')
            ->assertSee('GMS Lead')
            ->assertSee(route('gms-enquiries.index'));
    }

    public function test_gms_stone_enquiry_api_crud_soft_deletes_and_restores(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $rootRole->id]);
        Sanctum::actingAs($user);

        $payload = [
            'full_name' => 'Stone Buyer',
            'email' => 'buyer@example.com',
            'phone_number' => '+66812345678',
            'country_code' => 'TH',
            'account_type' => 'business',
            'business_name' => 'Stone Buyer Co',
            'company_name' => 'Stone Buyer Co., Ltd.',
            'tax_id' => '0105550000000',
            'mailing_name' => 'Stone Buyer',
            'website' => 'https://example.com',
            'office_type' => 'Head Office',
            'branch_code' => '00000',
            'address' => 'Bangkok',
            'country' => 'Thailand',
            'city' => 'Bangkok',
            'province' => 'Bangkok',
            'postcode' => '10110',
            'contact_name' => 'Stone Contact',
            'contact_email' => 'contact@example.com',
            'contact_phone' => '+66887654321',
            'is_seen' => true,
            'is_approved' => false,
            'privacy_policy_accepted' => true,
            'terms_conditions_accepted' => true,
        ];

        $createResponse = $this->postJson('/api/gms-stone-enquiries', $payload)
            ->assertCreated()
            ->assertJsonPath('data.email', 'buyer@example.com');

        $id = $createResponse->json('data.id');

        $this->getJson('/api/gms-stone-enquiries')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->getJson("/api/gms-stone-enquiries/{$id}")
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Stone Buyer');

        $this->putJson("/api/gms-stone-enquiries/{$id}", array_merge($payload, [
            'full_name' => 'Updated Stone Buyer',
            'is_approved' => true,
            'terms_conditions_accepted' => false,
        ]))
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Updated Stone Buyer')
            ->assertJsonPath('data.is_approved', true)
            ->assertJsonPath('data.privacy_policy_accepted', true)
            ->assertJsonPath('data.terms_conditions_accepted', false);

        $this->deleteJson("/api/gms-stone-enquiries/{$id}")
            ->assertOk()
            ->assertJsonPath('status', 'complete');

        $this->assertSoftDeleted('gms_stone_enquiries', ['id' => $id]);

        $this->postJson("/api/gms-stone-enquiries/{$id}/restore")
            ->assertOk()
            ->assertJsonPath('data.deleted_at', null);

        $this->assertDatabaseHas('gms_stone_enquiries', [
            'id' => $id,
            'deleted_at' => null,
        ]);
    }

    public function test_public_gms_submit_returns_json_without_accept_header(): void
    {
        $this->post('/api/gms-stone-enquiry', [
            'full_name' => 'External Submitter',
            'email' => 'external@example.com',
            'phone_number' => '+66812345678',
            'country_code' => 'TH',
            'account_type' => 'personal',
            'privacy_policy_accepted' => true,
            'terms_conditions_accepted' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('status', 'complete')
            ->assertJsonPath('data.email', 'external@example.com')
            ->assertJsonPath('data.privacy_policy_accepted', true)
            ->assertJsonPath('data.terms_conditions_accepted', true);

        $this->assertDatabaseHas('gms_stone_enquiries', [
            'email' => 'external@example.com',
            'privacy_policy_accepted' => true,
            'terms_conditions_accepted' => true,
        ]);
    }

    public function test_public_gms_submit_validation_returns_json_without_accept_header(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->post('/api/gms-stone-enquiry', [
            'full_name' => 'External Submitter',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'phone_number', 'country_code', 'account_type']);
    }

    public function test_public_gms_submit_blocks_same_ip_during_cooldown(): void
    {
        $payload = [
            'full_name' => 'Cooldown Submitter',
            'email' => 'cooldown@example.com',
            'phone_number' => '+66812345678',
            'country_code' => 'TH',
            'account_type' => 'personal',
            'privacy_policy_accepted' => true,
            'terms_conditions_accepted' => true,
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->post('/api/gms-stone-enquiry', $payload)
            ->assertCreated();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->post('/api/gms-stone-enquiry', array_merge($payload, [
            'email' => 'cooldown-second@example.com',
        ]))
            ->assertStatus(429)
            ->assertJsonPath('error', 'กรุณารอ 10 วินาที แล้วส่งอีกครั้ง');
    }

    public function test_gms_web_assignment_updates_assignee_and_clears_assignee_filter(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('general_manager');

        $oldAssignee = User::factory()->create();
        $oldAssignee->assignRole('sale');

        $newAssignee = User::factory()->create(['name' => 'GMS New Assignee']);
        $newAssignee->assignRole('sale');

        $enquiry = GmsStoneEnquiry::factory()->create(['assigned_to' => $oldAssignee->id]);

        $this->actingAs($manager)
            ->from(route('gms-enquiries.index', [
                'assigned_to' => $oldAssignee->id,
                'account_type' => 'business',
                'page' => 2,
            ]))
            ->post(route('gms-enquiries.assign', $enquiry->id), ['user_id' => $newAssignee->id])
            ->assertRedirect(route('gms-enquiries.index', ['account_type' => 'business']));

        $this->assertDatabaseHas('gms_stone_enquiries', [
            'id' => $enquiry->id,
            'assigned_to' => $newAssignee->id,
            'assigned_by' => $manager->id,
        ]);
    }

    public function test_gms_api_assignment_returns_updated_assignee(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('general_manager');

        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $enquiry = GmsStoneEnquiry::factory()->create();

        Sanctum::actingAs($manager);

        $this->postJson("/api/gms-stone-enquiries/{$enquiry->id}/assign", ['user_id' => $sale->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $sale->id)
            ->assertJsonPath('data.assigned_by', $manager->id);
    }
}
