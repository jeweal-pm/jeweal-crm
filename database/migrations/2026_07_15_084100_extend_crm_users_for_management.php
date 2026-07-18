<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('password');
            }

            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('remember_token')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('created_by');
            }

            if (! Schema::hasColumn('users', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('invited_at');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            foreach (['is_active', 'invited_at', 'activated_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
