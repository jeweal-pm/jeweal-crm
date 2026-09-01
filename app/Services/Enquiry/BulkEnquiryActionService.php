<?php

namespace App\Services\Enquiry;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BulkEnquiryActionService
{
    public function execute(string $modelClass, User $actor, array $data): int
    {
        return DB::transaction(function () use ($modelClass, $actor, $data) {
            if ($data['action'] === 'delete') {
                Gate::forUser($actor)->authorize('bulkDelete', $modelClass);
            }

            $ids = array_values(array_unique(array_map('intval', $data['ids'])));
            $records = $modelClass::withTrashed()
                ->visibleTo($actor)
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            if ($records->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'ids' => 'One or more selected records are unavailable or outside your access scope.',
                ]);
            }

            $target = ($data['action'] ?? null) === 'assign'
                ? $this->assignmentTarget($actor, (int) $data['user_id'])
                : null;

            $this->apply($records, $actor, $data, $target);

            return $records->count();
        });
    }

    public function successMessage(string $action, int $count): string
    {
        $label = match ($action) {
            'delete' => 'moved to deleted records',
            'restore' => 'restored',
            'assign' => 'assigned',
            'status' => 'updated',
            default => 'processed',
        };

        return sprintf('%d selected record%s %s successfully.', $count, $count === 1 ? '' : 's', $label);
    }

    private function apply(Collection $records, User $actor, array $data, ?User $target): void
    {
        foreach ($records as $record) {
            match ($data['action']) {
                'delete' => $this->delete($record, $actor),
                'restore' => $this->restore($record, $actor),
                'assign' => $this->assign($record, $actor, $target),
                'status' => $this->updateStatus($record, $actor, $data['status']),
            };
        }
    }

    private function delete(Model $record, User $actor): void
    {
        $this->requireState(! $record->trashed(), 'Deleted records cannot be deleted again.');
        Gate::forUser($actor)->authorize('delete', $record);

        if (Schema::hasColumn($record->getTable(), 'deleted_by')) {
            $record->softDeleteBy($actor);

            return;
        }

        if (Schema::hasColumn($record->getTable(), 'last_updated_by')) {
            $record->forceFill(['last_updated_by' => $actor->id])->save();
        }
        $record->delete();
        $record->recordActivity('deleted', $actor);
    }

    private function restore(Model $record, User $actor): void
    {
        $this->requireState($record->trashed(), 'Only deleted records can be restored.');
        Gate::forUser($actor)->authorize('restore', $record);

        if (Schema::hasColumn($record->getTable(), 'deleted_by')) {
            $record->restoreBy($actor);

            return;
        }

        $record->restore();
        if (Schema::hasColumn($record->getTable(), 'last_updated_by')) {
            $record->forceFill(['last_updated_by' => $actor->id])->save();
        }
        $record->recordActivity('restored', $actor);
    }

    private function assign(Model $record, User $actor, ?User $target): void
    {
        $this->requireState(! $record->trashed(), 'Deleted records cannot be assigned.');
        Gate::forUser($actor)->authorize('assign', $record);
        $record->assignTo($target, $actor);
    }

    private function updateStatus(Model $record, User $actor, string $status): void
    {
        $this->requireState(! $record->trashed(), 'Deleted records cannot change status.');
        Gate::forUser($actor)->authorize('updateStatus', $record);
        $record->changeStatus($status, $actor);
    }

    private function assignmentTarget(User $actor, int $targetId): User
    {
        $target = User::query()
            ->whereKey($targetId)
            ->where('is_active', true)
            ->first();

        if (! $target || ! in_array($target->primaryRoleName(), ['sale', 'sale_manager'], true)) {
            throw ValidationException::withMessages(['user_id' => 'Select an active sales team member.']);
        }

        $permission = $target->primaryRoleName() === 'sale'
            ? 'enquiry.assign.to_sale'
            : 'enquiry.assign.to_sale_manager';
        abort_unless($actor->hasCrmPermission($permission), 403);

        return $target;
    }

    private function requireState(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['ids' => $message]);
        }
    }
}
