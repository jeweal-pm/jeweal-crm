<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gis_fair_leads')) {
            return;
        }

        DB::table('gis_fair_leads')
            ->where('status', 'lead_mql')
            ->update(['status' => 'prospect']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `gis_fair_leads` ALTER COLUMN `status` SET DEFAULT 'prospect'");
        }
    }

    public function down(): void
    {
        // The data normalization is intentionally not reversed because old and new
        // prospect records cannot be distinguished safely after deployment.
    }
};
