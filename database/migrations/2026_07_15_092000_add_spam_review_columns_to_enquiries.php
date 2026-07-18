<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'spam_status')) {
                    $table->string('spam_status', 20)->default('clean')->index()->after('counts_for_sale_kpi');
                }

                if (! Schema::hasColumn($tableName, 'spam_score')) {
                    $table->unsignedTinyInteger('spam_score')->default(0)->after('spam_status');
                }

                if (! Schema::hasColumn($tableName, 'spam_reasons')) {
                    $table->json('spam_reasons')->nullable()->after('spam_score');
                }

                if (! Schema::hasColumn($tableName, 'spam_checked_at')) {
                    $table->timestamp('spam_checked_at')->nullable()->after('spam_reasons');
                }

                if (! Schema::hasColumn($tableName, 'spam_reviewed_by')) {
                    $table->foreignId('spam_reviewed_by')
                        ->nullable()
                        ->after('spam_checked_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'spam_reviewed_at')) {
                    $table->timestamp('spam_reviewed_at')->nullable()->after('spam_reviewed_by');
                }

                $table->index(['spam_status', 'deleted_at'], $tableName.'_spam_deleted_idx');
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
                $table->dropIndex($tableName.'_spam_deleted_idx');

                if (Schema::hasColumn($tableName, 'spam_reviewed_by')) {
                    $table->dropConstrainedForeignId('spam_reviewed_by');
                }

                foreach ([
                    'spam_status',
                    'spam_score',
                    'spam_reasons',
                    'spam_checked_at',
                    'spam_reviewed_at',
                ] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
