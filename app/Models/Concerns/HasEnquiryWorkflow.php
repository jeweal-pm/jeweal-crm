<?php

namespace App\Models\Concerns;

use App\Enums\EnquiryStatus;
use App\Models\EnquiryActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEnquiryWorkflow
{
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(EnquiryActivity::class, 'enquirable');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasCrmPermission('enquiry.view.all')) {
            return $query;
        }

        return $query->where($this->getTable().'.assigned_to', $user->id);
    }

    public function assignTo(User $target, User $actor): void
    {
        $previousAssignee = $this->assigned_to;

        $this->forceFill([
            'assigned_to' => $target->id,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
            'last_updated_by' => $actor->id,
        ])->save();

        $this->recordActivity($previousAssignee ? 'reassigned' : 'assigned', $actor, [
            'from_user_id' => $previousAssignee,
            'to_user_id' => $target->id,
        ]);
    }

    public function changeStatus(string $status, User $actor): void
    {
        $oldStatus = $this->status;
        $actorRole = $actor->primaryRoleName();

        $attributes = [
            'status' => $status,
            'last_updated_by' => $actor->id,
        ];

        if ($status === EnquiryStatus::CUSTOMER) {
            $attributes['closed_at'] = now();
            $attributes['closed_by'] = $actor->id;
            $attributes['closed_by_role'] = $actorRole;
            $attributes['counts_for_sale_kpi'] = in_array($actorRole, ['sale', 'sale_manager'], true);
        }

        $this->forceFill($attributes)->save();

        $this->recordActivity('status_changed', $actor, [], $oldStatus, $status);
    }

    public function softDeleteBy(User $actor): void
    {
        $this->forceFill([
            'deleted_by' => $actor->id,
            'last_updated_by' => $actor->id,
        ])->save();

        $this->delete();
        $this->recordActivity('deleted', $actor);
    }

    public function restoreBy(User $actor): void
    {
        $this->restore();

        $this->forceFill([
            'deleted_by' => null,
            'last_updated_by' => $actor->id,
        ])->save();

        $this->recordActivity('restored', $actor);
    }

    public function recordActivity(
        string $action,
        ?User $actor = null,
        array $meta = [],
        ?string $oldStatus = null,
        ?string $newStatus = null
    ): void {
        $this->activities()->create([
            'user_id' => $actor?->id,
            'user_role' => $actor?->primaryRoleName(),
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
