<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BackfillPrimaryUserRolesSeeder extends Seeder
{
    public function run()
    {
        $rootRole = Role::findByName('root');

        User::query()
            ->orderBy('id')
            ->get()
            ->each(function (User $user) use ($rootRole) {
                if (! $user->primary_role_id) {
                    $user->forceFill(['primary_role_id' => $rootRole->id])->save();
                }

                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $user->primary_role_id,
                    'model_type' => $user->getMorphClass(),
                    'model_id' => $user->id,
                ], []);
            });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
