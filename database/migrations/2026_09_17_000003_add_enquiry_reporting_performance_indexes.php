<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addIndex('gis_fair_lead_submissions', 'gis_fair_submissions_campaign_submitted_idx', [
            'campaign_id', 'submitted_at', 'lead_id',
        ]);
        $this->addIndex('gis_fair_lead_submissions', 'gis_fair_submissions_source_idx', [
            'source',
        ]);
        $this->addIndex('gis_fair_leads', 'gis_fair_leads_visible_submitted_idx', [
            'deleted_at', 'last_submitted_at', 'id',
        ]);
        $this->addIndex('gis_fair_leads', 'gis_fair_leads_source_deleted_idx', [
            'source', 'deleted_at', 'id',
        ]);

        foreach (['gis_enquiries', 'enquiries'] as $tableName) {
            $this->addIndex($tableName, $tableName.'_inbox_created_idx', [
                'spam_status', 'deleted_at', 'created_at', 'id',
            ]);
            $this->addIndex($tableName, $tableName.'_assigned_inbox_created_idx', [
                'assigned_to', 'spam_status', 'deleted_at', 'created_at', 'id',
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ([
            ['gis_fair_lead_submissions', 'gis_fair_submissions_campaign_submitted_idx'],
            ['gis_fair_lead_submissions', 'gis_fair_submissions_source_idx'],
            ['gis_fair_leads', 'gis_fair_leads_visible_submitted_idx'],
            ['gis_fair_leads', 'gis_fair_leads_source_deleted_idx'],
            ['gis_enquiries', 'gis_enquiries_inbox_created_idx'],
            ['gis_enquiries', 'gis_enquiries_assigned_inbox_created_idx'],
            ['enquiries', 'enquiries_inbox_created_idx'],
            ['enquiries', 'enquiries_assigned_inbox_created_idx'],
        ] as [$tableName, $indexName]) {
            $this->dropIndex($tableName, $indexName);
        }
    }

    private function addIndex(string $tableName, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndex(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$tableName, $indexName]
        );
    }
};
