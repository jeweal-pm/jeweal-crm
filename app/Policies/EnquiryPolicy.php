<?php

namespace App\Policies;

use App\Models\User;

class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCrmPermission('enquiry.view.all')
            || $user->hasCrmPermission('enquiry.view.assigned');
    }

    public function view(User $user, object $enquiry): bool
    {
        return $user->hasCrmPermission('enquiry.view.all')
            || (int) $enquiry->assigned_to === (int) $user->id;
    }

    public function assign(User $user, object $enquiry): bool
    {
        return $this->view($user, $enquiry)
            && (
                $user->hasCrmPermission('enquiry.assign.to_sale')
                || $user->hasCrmPermission('enquiry.assign.to_sale_manager')
            );
    }

    public function updateStatus(User $user, object $enquiry): bool
    {
        return $this->view($user, $enquiry) && $user->hasCrmPermission('enquiry.update_status');
    }

    public function delete(User $user, object $enquiry): bool
    {
        return $this->view($user, $enquiry) && $user->hasCrmPermission('enquiry.delete');
    }

    public function restore(User $user, object $enquiry): bool
    {
        return $user->hasCrmPermission('enquiry.restore');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasCrmPermission('enquiry.bulk_delete');
    }
}
