<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'primary_role_id')) {
                $table->foreignId('primary_role_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('roles')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_type', 'user')
                ->orWhere('model_type', App\Models\User::class)
                ->orderBy('role_id')
                ->get()
                ->each(function ($row) {
                    DB::table('users')
                        ->where('id', $row->model_id)
                        ->whereNull('primary_role_id')
                        ->update(['primary_role_id' => $row->role_id]);
                });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'primary_role_id')) {
                $table->dropConstrainedForeignId('primary_role_id');
            }
        });
    }
};
