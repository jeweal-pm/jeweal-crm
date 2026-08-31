<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ip_rate_limit_configs', function (Blueprint $table) {
            $table->id();
            $table->string('module', 64)->unique();
            $table->string('label');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->unsignedInteger('window_seconds')->default(86400);
            $table->unsignedInteger('cooldown_seconds')->default(10);
            $table->timestamps();
        });

        Schema::create('ip_blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('ip_hash', 64)->unique();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('blocked_until')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ip_rate_limit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->string('module', 64)->index();
            $table->string('ip_address', 45)->index();
            $table->string('ip_hash', 64)->index();
            $table->string('endpoint')->nullable();
            $table->string('decision', 32)->index();
            $table->unsignedSmallInteger('http_status');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['module', 'ip_hash', 'decision', 'occurred_at'], 'ip_rate_lookup_index');
        });

        Schema::create('twilio_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('twilio')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->text('account_sid')->nullable();
            $table->text('api_key_sid')->nullable();
            $table->text('api_key_secret')->nullable();
            $table->string('whatsapp_from', 32)->nullable();
            $table->unsignedInteger('daily_limit')->default(100);
            $table->unsignedInteger('max_retry_attempts')->default(3);
            $table->json('retry_delays_seconds')->nullable();
            $table->string('timezone', 64)->default('Asia/Bangkok');
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('recipient', 32);
            $table->string('recipient_normalized', 24)->unique();
            $table->text('body');
            $table->string('source_module', 64)->default('whatsapp')->index();
            $table->string('source_reference')->nullable();
            $table->string('status', 24)->default('waiting')->index();
            $table->string('wait_reason', 32)->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->string('provider_message_sid')->nullable()->index();
            $table->string('provider_status', 64)->nullable();
            $table->string('provider_error_code', 64)->nullable();
            $table->text('provider_error_message')->nullable();
            $table->string('source_ip', 45);
            $table->string('source_ip_hash', 64)->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at'], 'whatsapp_due_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('twilio_configurations');
        Schema::dropIfExists('ip_rate_limit_logs');
        Schema::dropIfExists('ip_blacklists');
        Schema::dropIfExists('ip_rate_limit_configs');
    }
};
