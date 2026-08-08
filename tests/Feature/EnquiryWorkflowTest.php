<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Models\User;
use App\Services\Spam\EnquirySpamScorer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnquiryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(storage_path('app/rate_limit'));
    }

    public function test_public_enquiry_submission_ignores_client_status(): void
    {
        $payload = [
            'name' => 'Jane Lead',
            'business_type' => ['retail'],
            'email' => 'jane@example.com',
            'country' => 'Thailand',
            'phone' => '+66812345678',
            'company' => 'Example Co',
            'company_website' => 'https://example.com',
            'description' => 'Interested in CRM',
            'interest_in' => ['crm'],
            'status' => 'customer',
        ];

        $this->postJson('/api/enquiry', $payload)->assertCreated();

        $this->assertDatabaseHas('enquiries', [
            'email' => 'jane@example.com',
            'status' => 'lead_mql',
        ]);
    }

    public function test_sale_filter_only_returns_assigned_enquiries(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $assigned = Enquiry::factory()->create(['assigned_to' => $sale->id]);
        Enquiry::factory()->create();

        Sanctum::actingAs($sale);

        $this->getJson('/api/enquiries')
            ->assertOk()
            ->assertJsonPath('data.0.id', $assigned->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_inbox_only_returns_clean_enquiries(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $clean = Enquiry::factory()->create([
            'assigned_to' => $sale->id,
            'spam_status' => EnquirySpamScorer::STATUS_CLEAN,
        ]);

        Enquiry::factory()->create([
            'assigned_to' => $sale->id,
            'spam_status' => EnquirySpamScorer::STATUS_NOT_SPAM,
        ]);
        Enquiry::factory()->create([
            'assigned_to' => $sale->id,
            'spam_status' => EnquirySpamScorer::STATUS_SUSPECTED,
        ]);
        Enquiry::factory()->create([
            'assigned_to' => $sale->id,
            'spam_status' => EnquirySpamScorer::STATUS_CONFIRMED,
        ]);

        Sanctum::actingAs($sale);

        $this->getJson('/api/enquiries')
            ->assertOk()
            ->assertJsonPath('data.0.id', $clean->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_legacy_all_spam_filter_does_not_bypass_inbox(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $clean = Enquiry::factory()->create([
            'assigned_to' => $sale->id,
            'spam_status' => EnquirySpamScorer::STATUS_CLEAN,
        ]);

        Enquiry::factory()->create([
            'assigned_to' => $sale->id,
            'spam_status' => EnquirySpamScorer::STATUS_SUSPECTED,
        ]);

        Sanctum::actingAs($sale);

        $this->getJson('/api/enquiries?spam=all')
            ->assertOk()
            ->assertJsonPath('data.0.id', $clean->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_assignment_rejects_inactive_or_non_sales_targets(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('general_manager');

        $inactiveSale = User::factory()->create(['is_active' => false]);
        $inactiveSale->assignRole('sale');

        $enquiry = Enquiry::factory()->create();

        $this->actingAs($manager)
            ->postJson("/enquiries/{$enquiry->id}/assign", ['user_id' => $inactiveSale->id])
            ->assertNotFound();
    }

    public function test_web_assignment_updates_assignee_and_clears_assignee_filter(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('general_manager');

        $oldAssignee = User::factory()->create();
        $oldAssignee->assignRole('sale');

        $newAssignee = User::factory()->create(['name' => 'New Assignee']);
        $newAssignee->assignRole('sale');

        $enquiry = Enquiry::factory()->create(['assigned_to' => $oldAssignee->id]);

        $this->actingAs($manager)
            ->from(route('enquiry.index', [
                'assigned_to' => $oldAssignee->id,
                'spam' => 'inbox',
                'page' => 2,
            ]))
            ->post(route('enquiries.assign', $enquiry->id), ['user_id' => $newAssignee->id])
            ->assertRedirect(route('enquiry.index', ['spam' => 'inbox']));

        $this->assertDatabaseHas('enquiries', [
            'id' => $enquiry->id,
            'assigned_to' => $newAssignee->id,
            'assigned_by' => $manager->id,
        ]);
    }

    public function test_user_with_primary_root_role_can_open_enquiry_without_pivot_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $rootRole->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Jeweal CRM');

        $this->actingAs($user)
            ->get('/enquiry')
            ->assertOk()
            ->assertSee('Assignee')
            ->assertDontSee('Owner');
    }

    public function test_user_management_menu_links_to_existing_page_for_authorized_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $rootRole->id]);

        $this->actingAs($user)
            ->get('/enquiry')
            ->assertOk()
            ->assertSee(route('users.index'))
            ->assertSee('User Management');

        $this->actingAs($user)
            ->get('/users')
            ->assertOk();

        $this->actingAs($user)
            ->get('/add-user')
            ->assertOk()
            ->assertSee('Create User');
    }

    public function test_user_without_user_view_permission_cannot_open_user_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $this->actingAs($sale)
            ->get('/users')
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_user_with_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $actor = User::factory()->create(['primary_role_id' => $rootRole->id]);

        $this->actingAs($actor)
            ->post('/users', [
                'name' => 'New Sale',
                'email' => 'new-sale@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'sale',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'new-sale@example.com')->firstOrFail();

        $this->assertSame('New Sale', $user->name);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame('sale', $user->primaryRoleName());
        $this->assertTrue($user->hasRole('sale'));
        $this->assertTrue($user->is_active);
    }

    public function test_authorized_user_can_open_and_send_enquiry_reply_email(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $rootRole->id]);
        $enquiry = Enquiry::factory()->create([
            'name' => 'Reply Enquiry Lead',
            'email' => 'reply-enquiry@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('enquiries.reply', $enquiry->id))
            ->assertOk()
            ->assertSee('Reply to Reply Enquiry Lead')
            ->assertSee('reply-enquiry@example.com');

        $this->actingAs($user)
            ->post(route('enquiries.reply.send', $enquiry->id), [
                'subject' => 'CRM follow up',
                'message' => 'Thank you for contacting us.',
            ])
            ->assertRedirect(route('enquiry.index'));

        Mail::assertSent(\App\Mail\EnquiryReply::class, function ($mail) {
            return $mail->hasTo('reply-enquiry@example.com')
                && $mail->enquiryType === 'CRM enquiry'
                && $mail->replySubject === 'CRM follow up'
                && $mail->replyMessage === 'Thank you for contacting us.';
        });
    }

    public function test_authorized_user_can_open_and_send_gis_reply_email(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);

        $rootRole = Role::findByName('root');
        $user = User::factory()->create(['primary_role_id' => $rootRole->id]);
        $enquiry = GisEnquiry::factory()->create([
            'first_name' => 'GIS',
            'last_name' => 'Lead',
            'email' => 'reply-gis@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('gis-enquiries.reply', $enquiry->id))
            ->assertOk()
            ->assertSee('Reply to GIS Lead')
            ->assertSee('reply-gis@example.com');

        $this->actingAs($user)
            ->post(route('gis-enquiries.reply.send', $enquiry->id), [
                'subject' => 'GIS follow up',
                'message' => 'Thank you for contacting us about GIS.',
            ])
            ->assertRedirect(route('gisEnquiry'));

        Mail::assertSent(\App\Mail\EnquiryReply::class, function ($mail) {
            return $mail->hasTo('reply-gis@example.com')
                && $mail->enquiryType === 'GIS enquiry'
                && $mail->replySubject === 'GIS follow up'
                && $mail->replyMessage === 'Thank you for contacting us about GIS.';
        });
    }
}
