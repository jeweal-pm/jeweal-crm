<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gis_fair_tracking_links') || Schema::hasColumn('gis_fair_tracking_links', 'fair_code_prefix')) {
            return;
        }

        Schema::table('gis_fair_tracking_links', function (Blueprint $table) {
            $table->string('fair_code_prefix', 12)->nullable()->after('expired_redirect_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gis_fair_tracking_links') || ! Schema::hasColumn('gis_fair_tracking_links', 'fair_code_prefix')) {
            return;
        }

        Schema::table('gis_fair_tracking_links', function (Blueprint $table) {
            $table->dropColumn('fair_code_prefix');
        });
    }
};
