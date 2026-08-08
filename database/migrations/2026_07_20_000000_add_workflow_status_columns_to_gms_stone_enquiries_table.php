<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('gms_stone_enquiries')) {
            return;
        }

        Schema::table('gms_stone_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('gms_stone_enquiries', 'status')) {
                $table->string('status', 32)->default('lead_mql')->after('account_type')->index();
            }

            if (! Schema::hasColumn('gms_stone_enquiries', 'last_updated_by')) {
                $table->foreignId('last_updated_by')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('gms_stone_enquiries', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('last_updated_by');
            }

            if (! Schema::hasColumn('gms_stone_enquiries', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('gms_stone_enquiries', 'closed_by_role')) {
                $table->string('closed_by_role')->nullable()->after('closed_by');
            }

            if (! Schema::hasColumn('gms_stone_enquiries', 'counts_for_sale_kpi')) {
                $table->boolean('counts_for_sale_kpi')->default(true)->after('closed_by_role');
            }
        });

        DB::table('gms_stone_enquiries')
            ->where('is_approved', true)
            ->where('status', 'lead_mql')
            ->update(['status' => 'customer']);
    }

    public function down()
    {
        if (! Schema::hasTable('gms_stone_enquiries')) {
            return;
        }

        Schema::table('gms_stone_enquiries', function (Blueprint $table) {
            foreach (['last_updated_by', 'closed_by'] as $column) {
                if (Schema::hasColumn('gms_stone_enquiries', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['status', 'closed_at', 'closed_by_role', 'counts_for_sale_kpi'] as $column) {
                if (Schema::hasColumn('gms_stone_enquiries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
