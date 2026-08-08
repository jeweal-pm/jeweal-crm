<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('subscription_status', 32)->default('subscribed')->index();
            $table->json('preferences')->nullable();
            $table->string('unsubscribe_token_hash', 64)->unique();
            $table->string('consent_source')->nullable();
            $table->string('lawful_basis')->nullable();
            $table->string('consent_version')->nullable();
            $table->string('privacy_notice_version')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('email_suppression_lists', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('category', 32)->default('all_marketing');
            $table->string('reason')->nullable();
            $table->string('source')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('suppressed_at')->index();
            $table->timestamps();
            $table->unique(['email', 'category']);
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('email_type', 32)->index();
            $table->string('category', 64)->index();
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('html_content');
            $table->longText('plain_text_content')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->json('variables')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('email_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('html_content');
            $table->longText('plain_text_content')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->json('variables')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->index();
            $table->unique(['email_template_id', 'version']);
        });

        Schema::create('email_automation_configs', function (Blueprint $table) {
            $table->id();
            $table->string('enquiry_type', 32)->unique();
            $table->boolean('customer_enabled')->default(true);
            $table->foreignId('customer_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->unsignedInteger('customer_delay_seconds')->default(0);
            $table->boolean('internal_enabled')->default(true);
            $table->foreignId('internal_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->json('internal_to')->nullable();
            $table->json('internal_cc')->nullable();
            $table->json('internal_bcc')->nullable();
            $table->string('internal_assignment_mode', 32)->default('config');
            $table->unsignedInteger('reminder_after_minutes')->nullable();
            $table->boolean('welcome_enabled')->default(false);
            $table->foreignId('welcome_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->unsignedInteger('welcome_delay_seconds')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('email_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('segment_type', 16)->default('dynamic');
            $table->json('conditions')->nullable();
            $table->string('status', 16)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('email_segment_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_segment_id')->constrained('email_segments')->cascadeOnDelete();
            $table->foreignId('email_subscriber_id')->constrained('email_subscribers')->cascadeOnDelete();
            $table->boolean('is_snapshot')->default(false);
            $table->timestamp('added_at')->index();
            $table->unique(['email_segment_id', 'email_subscriber_id'], 'email_segment_membership_unique');
        });

        Schema::create('email_sequence_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('priority')->default(100);
            $table->json('entry_conditions')->nullable();
            $table->json('exit_conditions')->nullable();
            $table->string('timezone', 64)->default('Asia/Bangkok');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_sequence_template_id')->constrained('email_sequence_templates')->cascadeOnDelete();
            $table->unsignedInteger('step_number');
            $table->foreignId('email_template_id')->constrained('email_templates')->restrictOnDelete();
            $table->unsignedInteger('delay_value')->default(0);
            $table->string('delay_unit', 16)->default('minutes');
            $table->string('timezone', 64)->default('Asia/Bangkok');
            $table->boolean('business_days_only')->default(false);
            $table->json('conditions')->nullable();
            $table->json('skip_conditions')->nullable();
            $table->json('actions')->nullable();
            $table->timestamps();
            $table->unique(['email_sequence_template_id', 'step_number'], 'email_sequence_step_unique');
        });

        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('campaign_type', 16)->default('single');
            $table->foreignId('email_segment_id')->nullable()->constrained('email_segments')->nullOnDelete();
            $table->foreignId('excluded_segment_id')->nullable()->constrained('email_segments')->nullOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('email_sequence_template_id')->nullable()->constrained('email_sequence_templates')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('timezone', 64)->default('Asia/Bangkok');
            $table->unsignedInteger('sending_limit')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('approval_status', 32)->default('draft')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->json('ab_config')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_subscriber_id')->constrained('email_subscribers')->cascadeOnDelete();
            $table->foreignId('email_sequence_template_id')->constrained('email_sequence_templates')->cascadeOnDelete();
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->string('sequence_version', 32)->default('1');
            $table->unsignedInteger('current_step')->default(1);
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('enrolled_at')->index();
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamp('next_scheduled_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->string('exit_reason')->nullable();
            $table->string('ab_variant', 16)->nullable();
            $table->timestamps();
            $table->unique(['email_subscriber_id', 'email_sequence_template_id'], 'email_enrollment_unique');
        });

        Schema::create('email_campaign_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->string('variant_key', 16);
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->string('sender_name')->nullable();
            $table->unsignedInteger('allocation')->default(100);
            $table->string('success_metric', 32)->default('click_rate');
            $table->unsignedInteger('minimum_sample_size')->default(100);
            $table->timestamps();
            $table->unique(['email_campaign_id', 'variant_key']);
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id')->unique();
            $table->string('idempotency_key')->unique();
            $table->foreignId('email_subscriber_id')->nullable()->constrained('email_subscribers')->nullOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->foreignId('email_enrollment_id')->nullable()->constrained('email_enrollments')->nullOnDelete();
            $table->foreignId('email_sequence_step_id')->nullable()->constrained('email_sequence_steps')->nullOnDelete();
            $table->string('message_type', 32)->default('marketing');
            $table->string('to_email');
            $table->json('to_data')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject');
            $table->longText('html_content');
            $table->longText('plain_text_content')->nullable();
            $table->string('status', 32)->default('queued')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();
            $table->index(['to_email', 'created_at']);
        });

        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained('email_messages')->cascadeOnDelete();
            $table->string('event_type', 32)->index();
            $table->string('url')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->unique(['email_message_id', 'event_type', 'url']);
        });

        Schema::create('email_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_audit_logs');
        Schema::dropIfExists('email_events');
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_campaign_variants');
        Schema::dropIfExists('email_enrollments');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('email_sequence_steps');
        Schema::dropIfExists('email_sequence_templates');
        Schema::dropIfExists('email_segment_memberships');
        Schema::dropIfExists('email_segments');
        Schema::dropIfExists('email_automation_configs');
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_suppression_lists');
        Schema::dropIfExists('email_subscribers');
    }
};
