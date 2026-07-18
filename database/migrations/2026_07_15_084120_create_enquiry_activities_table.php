<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('enquiry_activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('enquirable');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_role')->nullable();
            $table->enum('action', ['created', 'assigned', 'reassigned', 'status_changed', 'deleted', 'restored']);
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enquiry_activities');
    }
};
