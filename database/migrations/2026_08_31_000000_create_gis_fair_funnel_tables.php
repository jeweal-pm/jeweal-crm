<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('gis_fair_campaigns')) {
            Schema::create('gis_fair_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 64)->unique();
                $table->string('edition', 80)->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->text('landing_url');
                $table->string('hall', 80)->nullable();
                $table->string('booth', 80)->nullable();
                $table->string('dates_display', 150)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('offer_deadline')->nullable();
                $table->string('timezone', 64)->default('Asia/Bangkok');
                $table->string('code_prefix', 12)->default('GIS');
                $table->string('privacy_notice_version', 40);
                $table->text('privacy_notice_url')->nullable();
                $table->string('contact_email', 100)->nullable();
                $table->boolean('accepting_submissions')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gis_fair_tracking_links')) {
            Schema::create('gis_fair_tracking_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('gis_fair_campaigns')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('code', 64)->unique();
                $table->text('destination_url')->nullable();
                $table->string('source', 80)->nullable();
                $table->string('medium', 80)->nullable();
                $table->string('content', 120)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedBigInteger('click_count')->default(0);
                $table->unsignedBigInteger('lead_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['campaign_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('gis_fair_tracking_visits')) {
            Schema::create('gis_fair_tracking_visits', function (Blueprint $table) {
                $table->id();
                $table->uuid('token')->unique();
                $table->foreignId('campaign_id')->constrained('gis_fair_campaigns')->cascadeOnDelete();
                $table->foreignId('tracking_link_id')->constrained('gis_fair_tracking_links')->cascadeOnDelete();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->string('ip_hash', 64)->nullable()->index();
                $table->string('user_agent_hash', 64)->nullable();
                $table->text('referrer')->nullable();
                $table->text('destination_url');
                $table->json('query_parameters')->nullable();
                $table->timestamp('visited_at')->index();
                $table->timestamp('converted_at')->nullable()->index();
            });
        }

        if (! Schema::hasTable('gis_fair_leads')) {
            Schema::create('gis_fair_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('gis_fair_campaigns')->restrictOnDelete();
                $table->foreignId('tracking_link_id')->nullable()->constrained('gis_fair_tracking_links')->nullOnDelete();
                $table->uuid('tracking_visit_token')->nullable()->index();
                $table->string('fair_code', 24)->unique();
                $table->string('first_name', 50);
                $table->string('last_name', 50);
                $table->string('email', 100);
                $table->string('company', 150);
                $table->string('business_type', 40);
                $table->unsignedInteger('stores');
                $table->string('country', 80);
                $table->char('phone_iso', 2);
                $table->string('phone_local', 20);
                $table->string('phone_e164', 20)->index();
                $table->string('phone_dial_code', 6);
                $table->string('current_system', 80);
                $table->json('interests');
                $table->string('source', 40)->index();
                $table->boolean('marketing_consent')->default(false);
                $table->timestamp('marketing_consent_at')->nullable();
                $table->timestamp('marketing_consent_withdrawn_at')->nullable();
                $table->boolean('privacy_agreed')->default(true);
                $table->dateTime('privacy_agreed_at');
                $table->string('privacy_notice_version', 40);
                $table->string('consent_ip', 45)->nullable();
                $table->text('consent_user_agent')->nullable();
                $table->unsignedInteger('submission_count')->default(1);
                $table->dateTime('last_submitted_at')->index();
                $table->timestamp('confirmation_sent_at')->nullable();
                $table->unsignedInteger('confirmation_send_count')->default(0);
                $table->string('status', 32)->default('lead_mql')->index();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('closed_by_role')->nullable();
                $table->boolean('counts_for_sale_kpi')->default(true);
                $table->string('spam_status', 20)->default('clean')->index();
                $table->unsignedTinyInteger('spam_score')->default(0);
                $table->json('spam_reasons')->nullable();
                $table->timestamp('spam_checked_at')->nullable();
                $table->foreignId('spam_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('spam_reviewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unique(['campaign_id', 'email'], 'gis_fair_campaign_email_unique');
                $table->index(['campaign_id', 'status', 'deleted_at'], 'gis_fair_campaign_status_idx');
                $table->index(['assigned_to', 'deleted_at'], 'gis_fair_assigned_deleted_idx');
            });
        }

        if (! Schema::hasTable('gis_fair_lead_submissions')) {
            Schema::create('gis_fair_lead_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('gis_fair_leads')->cascadeOnDelete();
                $table->foreignId('campaign_id')->constrained('gis_fair_campaigns')->cascadeOnDelete();
                $table->foreignId('tracking_link_id')->nullable()->constrained('gis_fair_tracking_links')->nullOnDelete();
                $table->uuid('tracking_visit_token')->nullable()->index();
                $table->string('source', 40);
                $table->boolean('privacy_agreed');
                $table->string('privacy_notice_version', 40);
                $table->boolean('marketing_consent')->default(false);
                $table->string('consent_ip', 45)->nullable();
                $table->text('consent_user_agent')->nullable();
                $table->dateTime('submitted_at')->index();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('gis_fair_lead_submissions');
        Schema::dropIfExists('gis_fair_leads');
        Schema::dropIfExists('gis_fair_tracking_visits');
        Schema::dropIfExists('gis_fair_tracking_links');
        Schema::dropIfExists('gis_fair_campaigns');
    }
};
