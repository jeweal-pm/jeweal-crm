<?php

namespace App\Services\Enquiry;

use App\Models\GisEnquiry;
use App\Models\GisFairLead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GisProspectBulkActionService
{
    public function __construct(private BulkEnquiryActionService $bulkActions)
    {
    }

    public function execute(User $actor, array $data): int
    {
        $ids = collect($data['records'])->groupBy(fn (string $record) => explode(':', $record, 2)[0])
            ->map(fn ($records) => $records->map(fn (string $record) => (int) explode(':', $record, 2)[1])->all());

        return DB::transaction(function () use ($actor, $data, $ids) {
            $count = 0;

            if ($ids->has(GisProspectIndexService::SOURCE_ENQUIRY)) {
                $count += $this->bulkActions->execute(GisEnquiry::class, $actor, array_merge($data, [
                    'ids' => $ids->get(GisProspectIndexService::SOURCE_ENQUIRY),
                ]));
            }

            if ($ids->has(GisProspectIndexService::SOURCE_FAIR)) {
                $count += $this->bulkActions->execute(GisFairLead::class, $actor, array_merge($data, [
                    'ids' => $ids->get(GisProspectIndexService::SOURCE_FAIR),
                ]), fn ($query) => GisProspectIndexService::scopeFairPrefix($query));
            }

            return $count;
        });
    }

    public function successMessage(string $action, int $count): string
    {
        return $this->bulkActions->successMessage($action, $count);
    }
}
