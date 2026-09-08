<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->boolean('privacy_policy_accepted')->default(false)->after('interest_in');
            $table->boolean('marketing_consent')->default(false)->after('privacy_policy_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_policy_accepted',
                'marketing_consent',
            ]);
        });
    }
};
