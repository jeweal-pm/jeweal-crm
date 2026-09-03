<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gis_fair_tracking_links', function (Blueprint $table) {
            $table->text('expired_redirect_url')->nullable()->after('destination_url');
        });
    }

    public function down(): void
    {
        Schema::table('gis_fair_tracking_links', function (Blueprint $table) {
            $table->dropColumn('expired_redirect_url');
        });
    }
};
