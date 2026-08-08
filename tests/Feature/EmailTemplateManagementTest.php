<?php

namespace Tests\Feature;

use App\Mail\ManagedEmailMailable;
use App\Models\EmailTemplate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_can_create_publish_preview_duplicate_and_restore_template_versions(): void
    {
        $user = $this->rootUser();
        $payload = [
            'name' => 'Welcome Template', 'code' => 'welcome-template', 'email_type' => 'transactional',
            'category' => 'welcome', 'subject' => 'Hello {{first_name}}', 'preview_text' => 'Welcome',
            'html_content' => '<p>Hello {{first_name}}</p>', 'plain_text_content' => 'Hello {{first_name}}',
            'sender_name' => 'CRM', 'sender_email' => 'noreply@example.com', 'reply_to_email' => 'sales@example.com',
            'language' => 'en', 'status' => 'draft', 'variables' => ['first_name'],
        ];

        $this->actingAs($user)->post(route('email.templates.store'), $payload)->assertRedirect(route('email.templates'));
        $template = EmailTemplate::where('code', 'welcome-template')->firstOrFail();
        $this->assertDatabaseHas('email_template_versions', ['email_template_id' => $template->id, 'version' => 1]);

        $this->actingAs($user)->post(route('email.templates.publish', $template->id))->assertRedirect();
        $this->actingAs($user)->get(route('email.templates.preview', $template->id))->assertOk()->assertSee('Hello Demo');

        $this->actingAs($user)->post(route('email.templates.duplicate', $template->id))->assertRedirect();
        $this->assertDatabaseCount('email_templates', 2);

        $this->actingAs($user)->put(route('email.templates.update', $template->id), array_merge($payload, [
            'subject' => 'Updated {{first_name}}', 'html_content' => '<p>Updated</p>',
        ]))->assertRedirect();
        $this->actingAs($user)->post(route('email.templates.versions.restore', [$template->id, 1]))->assertRedirect();
        $this->assertDatabaseHas('email_templates', ['id' => $template->id, 'status' => 'draft']);
    }

    public function test_test_send_uses_managed_mailable_without_external_provider_call(): void
    {
        Mail::fake();
        config(['email_management.sender_addresses.gis' => 'gis@jeweal.co.th']);
        $user = $this->rootUser();
        $template = EmailTemplate::create([
            'name' => 'Test send', 'code' => 'test-send-template', 'email_type' => 'transactional', 'category' => 'welcome',
            'subject' => 'Test {{first_name}}', 'html_content' => '<p>Hello {{first_name}}</p>', 'status' => 'draft',
        ]);

        $this->actingAs($user)->post(route('email.templates.test-send', $template->id), ['email' => 'qa@example.com', 'enquiry_type' => 'gis'])->assertRedirect();
        Mail::assertSent(ManagedEmailMailable::class, fn ($mail) => $mail->hasTo('qa@example.com') && $mail->senderEmail === 'gis@jeweal.co.th');
    }

    public function test_template_with_unknown_variable_cannot_be_published(): void
    {
        $user = $this->rootUser();
        $template = EmailTemplate::create([
            'name' => 'Invalid', 'code' => 'invalid-template', 'email_type' => 'transactional', 'category' => 'welcome',
            'subject' => 'Hello {{not_supported}}', 'html_content' => '<p>Hi</p>', 'status' => 'draft',
        ]);

        $this->actingAs($user)->post(route('email.templates.publish', $template->id))->assertStatus(422);
        $this->assertDatabaseHas('email_templates', ['id' => $template->id, 'status' => 'draft']);
    }

    private function rootUser()
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findByName('root');
        $user = \App\Models\User::factory()->create(['primary_role_id' => $role->id]);
        $user->assignRole($role);

        return $user;
    }
}
