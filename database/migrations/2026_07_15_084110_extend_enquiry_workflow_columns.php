<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['enquiries', 'gis_enquiries'];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $this->normalizeStatusColumn($tableName);

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'status')) {
                    $table->enum('status', ['lead_mql', 'sql', 'prospect', 'customer'])
                        ->default('lead_mql')
                        ->index()
                        ->after('id');
                }

                if (! Schema::hasColumn($tableName, 'assigned_to')) {
                    $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'assigned_by')) {
                    $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'assigned_at')) {
                    $table->timestamp('assigned_at')->nullable()->after('assigned_by');
                }

                if (! Schema::hasColumn($tableName, 'last_updated_by')) {
                    $table->foreignId('last_updated_by')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'closed_at')) {
                    $table->timestamp('closed_at')->nullable()->after('last_updated_by');
                }

                if (! Schema::hasColumn($tableName, 'closed_by')) {
                    $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'closed_by_role')) {
                    $table->string('closed_by_role')->nullable()->after('closed_by');
                }

                if (! Schema::hasColumn($tableName, 'counts_for_sale_kpi')) {
                    $table->boolean('counts_for_sale_kpi')->default(true)->after('closed_by_role');
                }

                if (! Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }

                if (! Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
                }

                $table->index(['assigned_to', 'deleted_at'], $tableName.'_assigned_deleted_idx');
                $table->index(['status', 'deleted_at'], $tableName.'_status_deleted_idx');
                $table->index('created_at', $tableName.'_created_at_idx');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach ([
                    $tableName.'_assigned_deleted_idx',
                    $tableName.'_status_deleted_idx',
                    $tableName.'_created_at_idx',
                ] as $index) {
                    $table->dropIndex($index);
                }

                foreach (['assigned_to', 'assigned_by', 'last_updated_by', 'closed_by', 'deleted_by'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }

                foreach (['assigned_at', 'closed_at', 'closed_by_role', 'counts_for_sale_kpi'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }

    private function normalizeStatusColumn(string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `{$tableName}` MODIFY `status` VARCHAR(32) NOT NULL DEFAULT 'lead_mql'");
        }

        $statusMap = [
            'pending' => 'lead_mql',
            'answered' => 'sql',
            'quotation' => 'prospect',
            'deal_complete' => 'customer',
            'closed' => 'customer',
            'canceled' => 'prospect',
        ];

        foreach ($statusMap as $oldStatus => $newStatus) {
            DB::table($tableName)->where('status', $oldStatus)->update(['status' => $newStatus]);
        }

        DB::table($tableName)->whereNull('status')->update(['status' => 'lead_mql']);
    }
};
