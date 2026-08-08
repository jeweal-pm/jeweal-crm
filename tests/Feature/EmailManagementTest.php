<?php

namespace Tests\Feature;

use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use App\Services\Email\EmailMessageService;
use App\Services\Email\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_variables_are_rendered_and_unsafe_html_is_removed(): void
    {
        $template = EmailTemplate::create([
            'name' => 'Test', 'code' => 'test-template', 'email_type' => 'transactional', 'category' => 'welcome',
            'subject' => 'Hello {{first_name}}', 'html_content' => '<p>{{email}}</p><script>alert(1)</script>', 'status' => 'draft',
            'unsubscribe_token_hash' => null,
        ]);

        $rendered = app(EmailTemplateRenderer::class)->render($template, ['first_name' => 'A', 'email' => 'a@example.com']);
        $this->assertSame('Hello A', $rendered['subject']);
        $this->assertStringNotContainsString('<script>', $rendered['html_content']);
        $this->assertStringContainsString('a@example.com', $rendered['html_content']);
    }

    public function test_message_is_idempotent_and_tracking_urls_are_added(): void
    {
        $template = EmailTemplate::create([
            'name' => 'Test', 'code' => 'tracking-template', 'email_type' => 'transactional', 'category' => 'welcome',
            'subject' => 'Hello', 'html_content' => '<a href="https://example.com">Open</a>', 'status' => 'published',
        ]);
        $subscriber = EmailSubscriber::create([
            'email' => 'a@example.com', 'subscription_status' => 'pending_confirmation', 'unsubscribe_token_hash' => hash('sha256', 'token'),
        ]);

        Mail::fake();
        $service = app(EmailMessageService::class);
        $first = $service->queue($subscriber, $template, ['first_name' => 'A'], 'transactional', [], 'same-key');
        $second = $service->queue($subscriber, $template, ['first_name' => 'A'], 'transactional', [], 'same-key');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('email_messages', 1);
    }

    public function test_unsubscribe_link_changes_subscription_and_suppresses_marketing(): void
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'unsubscribe@example.com', 'subscription_status' => 'subscribed', 'unsubscribe_token_hash' => hash('sha256', 'token-1'),
        ]);

        $token = hash('sha256', 'token-1');
        $response = $this->from('/unsubscribe/'.$token)->post('/unsubscribe/'.$token, ['category' => 'all_marketing']);
        $response->assertOk();
        $this->assertDatabaseHas('email_subscribers', ['id' => $subscriber->id, 'subscription_status' => 'unsubscribed']);
        $this->assertDatabaseHas('email_suppression_lists', ['email' => 'unsubscribe@example.com', 'category' => 'all_marketing']);
    }
}
