<?php

namespace App\Services\Email;

use App\Models\EmailSegment;
use App\Models\EmailSubscriber;
use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Models\GmsStoneEnquiry;
use Illuminate\Database\Eloquent\Builder;

class EmailSegmentService
{
    public function members(EmailSegment $segment): Builder
    {
        if ($segment->segment_type === 'static') {
            return $segment->subscribers()->getQuery();
        }

        $conditions = $segment->conditions ?: [];
        $query = EmailSubscriber::query();
        $query->when($conditions['subscription_status'] ?? null, fn (Builder $q, string $value) => $q->where('subscription_status', $value));
        $query->when($conditions['source_type'] ?? null, fn (Builder $q, string $value) => $q->where('source_type', $value));
        $query->when($conditions['created_after_days'] ?? null, fn (Builder $q, int $days) => $q->where('created_at', '>=', now()->subDays($days)));

        if (! empty($conditions['customer_status'])) {
            $sourceType = $conditions['source_type'] ?? 'general';
            $model = match ($sourceType) {
                'gis' => GisEnquiry::class, 'gms' => GmsStoneEnquiry::class, default => Enquiry::class,
            };
            $ids = $model::query()->where('status', $conditions['customer_status'])->pluck('id');
            $query->where('source_type', $sourceType)->whereIn('source_id', $ids);
        }

        return $query;
    }

    public function refreshStatic(EmailSegment $segment): int
    {
        $segment->subscribers()->detach();
        $segment->setAttribute('segment_type', 'dynamic');
        $segment->setAttribute('segment_type', 'dynamic');
        $ids = $this->members($segment)->pluck('id');
        $now = now();
        $segment->subscribers()->attach($ids->mapWithKeys(fn ($id) => [$id => ['is_snapshot' => true, 'added_at' => $now]])->all());

        return $ids->count();
    }
}
