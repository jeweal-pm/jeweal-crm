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
        'email.view',
        'email.template.manage',
        'email.template.publish',
        'email.campaign.manage',
        'email.campaign.approve',
        'email.campaign.send',
        'email.sequence.manage',
        'email.segment.manage',
        'email.config.manage',
        'email.analytics.view',
        'email.export',
        'whatsapp.view',
        'whatsapp.message.manage',
        'whatsapp.config.manage',
        'security.ip.view',
        'security.ip.manage',
        'funnel.config.manage',
        'funnel.message.manage',
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
            'email.view', 'email.template.manage', 'email.template.publish', 'email.campaign.manage',
            'email.campaign.approve', 'email.campaign.send', 'email.sequence.manage', 'email.segment.manage',
            'email.config.manage', 'email.analytics.view', 'email.export',
            'whatsapp.view', 'whatsapp.message.manage', 'whatsapp.config.manage',
            'security.ip.view', 'security.ip.manage',
            'funnel.config.manage', 'funnel.message.manage',
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
            'email.view', 'email.template.manage', 'email.template.publish', 'email.campaign.manage',
            'email.campaign.approve', 'email.campaign.send', 'email.sequence.manage', 'email.segment.manage',
            'email.config.manage', 'email.analytics.view', 'email.export',
            'whatsapp.view', 'whatsapp.message.manage', 'whatsapp.config.manage',
            'security.ip.view', 'security.ip.manage',
            'funnel.config.manage', 'funnel.message.manage',
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
            'email.view', 'email.template.manage', 'email.template.publish', 'email.campaign.manage',
            'email.campaign.approve', 'email.campaign.send', 'email.sequence.manage', 'email.segment.manage',
            'email.config.manage', 'email.analytics.view', 'email.export',
            'whatsapp.view', 'whatsapp.message.manage', 'whatsapp.config.manage',
            'security.ip.view', 'security.ip.manage',
            'funnel.config.manage', 'funnel.message.manage',
        ],
        'admin' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
            'email.view', 'email.analytics.view',
            'whatsapp.view', 'security.ip.view',
        ],
        'sale_manager' => [
            'enquiry.view.all',
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.assign.to_sale',
            'enquiry.update_status',
            'enquiry.delete',
            'enquiry.bulk_delete',
            'funnel.message.manage',
        ],
        'sale' => [
            'enquiry.view.assigned',
            'enquiry.filter',
            'enquiry.update_status',
            'funnel.message.manage',
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
