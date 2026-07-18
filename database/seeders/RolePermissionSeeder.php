<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private array $permissions = [
        'enquiry.view.all',
        'enquiry.view.assigned',
        'enquiry.filter',
        'enquiry.assign.to_sale_manager',
        'enquiry.assign.to_sale',
        'enquiry.update_status',
        'enquiry.delete',
        'enquiry.bulk_delete',
        'enquiry.restore',
        'tracking.view',
        'user.view',
        'user.create',
        'user.update',
        'user.deactivate',
    ];

    private array $matrix = [
        'root' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.assign.to_sale_manager',
            'enquiry.assign.to_sale',
            'enquiry.update_status',
            'enquiry.delete',
            'enquiry.bulk_delete',
            'enquiry.restore',
            'tracking.view',
            'user.view',
            'user.create',
            'user.update',
            'user.deactivate',
        ],
        'ceo' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.assign.to_sale_manager',
            'enquiry.assign.to_sale',
            'enquiry.update_status',
            'enquiry.delete',
            'enquiry.bulk_delete',
            'enquiry.restore',
            'tracking.view',
            'user.view',
            'user.create',
            'user.update',
            'user.deactivate',
        ],
        'general_manager' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.assign.to_sale_manager',
            'enquiry.assign.to_sale',
            'enquiry.update_status',
            'enquiry.delete',
            'enquiry.bulk_delete',
            'enquiry.restore',
            'tracking.view',
            'user.view',
            'user.create',
            'user.update',
            'user.deactivate',
        ],
        'admin' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
        ],
        'sale_manager' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.assign.to_sale',
            'enquiry.update_status',
            'enquiry.delete',
            'enquiry.bulk_delete',
        ],
        'sale' => [
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.update_status',
        ],
    ];

    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach ($this->matrix as $roleName => $permissions) {
            Role::findOrCreate($roleName)->syncPermissions($permissions);
        }
    }
}
