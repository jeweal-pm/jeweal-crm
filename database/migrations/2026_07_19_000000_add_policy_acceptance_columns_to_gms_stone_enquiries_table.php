<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('gms_stone_enquiries', function (Blueprint $table) {
            $table->boolean('privacy_policy_accepted')->default(false)->after('is_approved');
            $table->boolean('terms_conditions_accepted')->default(false)->after('privacy_policy_accepted');
        });
    }

    public function down()
    {
        Schema::table('gms_stone_enquiries', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_policy_accepted',
                'terms_conditions_accepted',
            ]);
        });
    }
};
