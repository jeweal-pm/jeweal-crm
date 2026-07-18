<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', [
                'created',
                'role_changed',
                'updated',
                'deactivated',
                'reactivated',
                'password_reset_sent',
                'invitation_resent',
            ]);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_audit_logs');
    }
};
