<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('gis_enquiries')) {
            return;
        }

        Schema::table('gis_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('gis_enquiries', 'privacy_policy_accepted')) {
                $table->boolean('privacy_policy_accepted')->default(false)->after('message');
            }

            if (! Schema::hasColumn('gis_enquiries', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('privacy_policy_accepted');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('gis_enquiries')) {
            return;
        }

        Schema::table('gis_enquiries', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('gis_enquiries', 'marketing_consent') ? 'marketing_consent' : null,
                Schema::hasColumn('gis_enquiries', 'privacy_policy_accepted') ? 'privacy_policy_accepted' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
