<?php

namespace App\Services\Enquiry;

use App\Models\GisEnquiry;
use App\Models\GisFairLead;
use App\Models\User;
use App\Services\Spam\EnquirySpamScorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GisProspectIndexService
{
    public const SOURCE_ENQUIRY = 'gis_enquiry';

    public const SOURCE_FAIR = 'fair_funnel';

    public function paginate(User $user, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $queries = [];
        $source = $filters['record_source'] ?? null;

        if (! $source || $source === self::SOURCE_ENQUIRY) {
            $queries[] = $this->referenceQuery(
                $this->applyFilters(GisEnquiry::query()->visibleTo($user), $filters, false),
                self::SOURCE_ENQUIRY
            );
        }

        if (! $source || $source === self::SOURCE_FAIR) {
            $queries[] = $this->referenceQuery(
                $this->applyFilters($this->fairQuery($user), $filters, true),
                self::SOURCE_FAIR
            );
        }

        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = [
            'created_at' => 'sort_created_at',
            'assigned_at' => 'sort_assigned_at',
            'status' => 'sort_status',
        ][ltrim($sort, '-')];

        $paginator = DB::query()
            ->fromSub($union, 'gis_prospect_references')
            ->orderBy($sortColumn, $direction)
            ->orderBy('record_id', $direction)
            ->paginate($perPage)
            ->appends($filters);

        $paginator->setCollection($this->hydrate($paginator->getCollection()));

        return $paginator;
    }

    public function summary(User $user): array
    {
        $enquiries = GisEnquiry::query()->visibleTo($user);
        $fairLeads = $this->fairQuery($user);

        return [
            'total' => $this->sumCounts($enquiries, $fairLeads, fn (Builder $query) => $query->where('spam_status', EnquirySpamScorer::STATUS_CLEAN)),
            'unassigned' => $this->sumCounts($enquiries, $fairLeads, fn (Builder $query) => $query->where('spam_status', EnquirySpamScorer::STATUS_CLEAN)->whereNull('assigned_to')),
            'customers' => $this->sumCounts($enquiries, $fairLeads, fn (Builder $query) => $query->where('spam_status', EnquirySpamScorer::STATUS_CLEAN)->where('status', 'customer')),
            'deleted' => $this->sumCounts($enquiries, $fairLeads, fn (Builder $query) => $query->onlyTrashed()),
            'suspected_spam' => $this->sumCounts($enquiries, $fairLeads, fn (Builder $query) => $query->where('spam_status', EnquirySpamScorer::STATUS_SUSPECTED)),
            'confirmed_spam' => $this->sumCounts($enquiries, $fairLeads, fn (Builder $query) => $query->where('spam_status', EnquirySpamScorer::STATUS_CONFIRMED)),
        ];
    }

    public static function scopeFairPrefix(Builder $query): Builder
    {
        return $query->whereHas('campaign', function (Builder $query) {
            $query->whereRaw('LOWER(code_prefix) = ?', ['gis']);
        });
    }

    private function fairQuery(User $user): Builder
    {
        return self::scopeFairPrefix(GisFairLead::query()->visibleTo($user));
    }

    private function applyFilters(Builder $query, array $filters, bool $isFair): Builder
    {
        if (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        } elseif (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        }

        $spam = $filters['spam'] ?? 'inbox';
        $spamStatus = [
            'inbox' => EnquirySpamScorer::STATUS_CLEAN,
            'suspected' => EnquirySpamScorer::STATUS_SUSPECTED,
            'confirmed' => EnquirySpamScorer::STATUS_CONFIRMED,
            'not_spam' => EnquirySpamScorer::STATUS_NOT_SPAM,
        ][$spam];

        $table = $query->getModel()->getTable();
        $query
            ->where($table.'.spam_status', $spamStatus)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where($table.'.status', $status))
            ->when($filters['assigned_to'] ?? null, fn (Builder $query, int $userId) => $query->where($table.'.assigned_to', $userId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate($table.'.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate($table.'.created_at', '<=', $date));

        return $query->when($filters['q'] ?? null, function (Builder $query, string $keyword) use ($isFair, $table) {
            $query->where(function (Builder $query) use ($keyword, $isFair, $table) {
                $query->where($table.'.first_name', 'like', "%{$keyword}%")
                    ->orWhere($table.'.last_name', 'like', "%{$keyword}%")
                    ->orWhere($table.'.email', 'like', "%{$keyword}%");

                if ($isFair) {
                    $query->orWhere($table.'.company', 'like', "%{$keyword}%")
                        ->orWhere($table.'.fair_code', 'like', "%{$keyword}%");
                }
            });
        });
    }

    private function referenceQuery(Builder $query, string $source)
    {
        $table = $query->getModel()->getTable();

        return $query->selectRaw(
            "'{$source}' as record_type, {$table}.id as record_id, {$table}.created_at as sort_created_at, {$table}.assigned_at as sort_assigned_at, {$table}.status as sort_status"
        )->toBase();
    }

    private function hydrate(Collection $references): Collection
    {
        $idsByType = $references->groupBy('record_type')
            ->map(fn (Collection $items) => $items->pluck('record_id')->map(fn ($id) => (int) $id)->all());

        $enquiries = GisEnquiry::withTrashed()
            ->with(['assignedTo', 'deletedBy'])
            ->whereIn('id', $idsByType->get(self::SOURCE_ENQUIRY, []))
            ->get()
            ->keyBy('id');
        $fairLeads = GisFairLead::withTrashed()
            ->with(['campaign', 'trackingLink', 'assignedTo', 'deletedBy'])
            ->whereIn('id', $idsByType->get(self::SOURCE_FAIR, []))
            ->get()
            ->keyBy('id');

        return $references->map(function (object $reference) use ($enquiries, $fairLeads) {
            $model = $reference->record_type === self::SOURCE_FAIR
                ? $fairLeads->get($reference->record_id)
                : $enquiries->get($reference->record_id);

            if ($model) {
                $model->setAttribute('record_type', $reference->record_type);
                $model->setAttribute('record_key', $reference->record_type.':'.$reference->record_id);
            }

            return $model;
        })->filter()->values();
    }

    private function sumCounts(Builder $enquiries, Builder $fairLeads, callable $scope): int
    {
        return $scope(clone $enquiries)->count() + $scope(clone $fairLeads)->count();
    }
}
