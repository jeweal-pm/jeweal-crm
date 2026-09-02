<?php

namespace App\Services\GisFair;

use App\Models\GisFairCampaign;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GisFairDashboardService
{
    private const SOURCE_EXPRESSION = "COALESCE(NULLIF(tracking_links.source, ''), NULLIF(submissions.source, ''), 'direct')";

    public function data(array $filters): array
    {
        $base = $this->filteredSubmissions($filters);
        $registrations = (clone $base)->count('submissions.id');
        $uniqueProspects = (clone $base)->distinct()->count('submissions.lead_id');
        $qualified = (clone $base)->whereIn('leads.status', ['sql', 'prospect', 'customer'])
            ->distinct()->count('submissions.lead_id');
        $customers = (clone $base)->where('leads.status', 'customer')
            ->distinct()->count('submissions.lead_id');
        $marketingConsents = (clone $base)->where('leads.marketing_consent', true)
            ->distinct()->count('submissions.lead_id');

        $trend = (clone $base)
            ->selectRaw('DATE(submissions.submitted_at) as period')
            ->selectRaw('COUNT(submissions.id) as registrations')
            ->selectRaw('COUNT(DISTINCT submissions.lead_id) as prospects')
            ->groupBy(DB::raw('DATE(submissions.submitted_at)'))
            ->orderBy('period')
            ->get();

        $eventPerformance = (clone $base)
            ->selectRaw('campaigns.id, campaigns.name, campaigns.code, campaigns.status')
            ->selectRaw('COUNT(submissions.id) as registrations')
            ->selectRaw('COUNT(DISTINCT submissions.lead_id) as prospects')
            ->selectRaw("COUNT(DISTINCT CASE WHEN leads.status = 'customer' THEN submissions.lead_id END) as customers")
            ->groupBy('campaigns.id', 'campaigns.name', 'campaigns.code', 'campaigns.status')
            ->orderByDesc('registrations')
            ->get()
            ->map(function ($event) {
                $event->conversion_rate = $this->rate((int) $event->customers, (int) $event->prospects);

                return $event;
            });

        $sourceRows = (clone $base)
            ->selectRaw(self::SOURCE_EXPRESSION.' as source_name')
            ->addSelect([
                'submissions.id as submission_id',
                'submissions.lead_id',
            ]);

        $sourcePerformance = DB::query()
            ->fromSub($sourceRows, 'filtered_sources')
            ->select('source_name')
            ->selectRaw('COUNT(submission_id) as registrations')
            ->selectRaw('COUNT(DISTINCT lead_id) as prospects')
            ->groupBy('source_name')
            ->orderByDesc('registrations')
            ->limit(8)
            ->get();

        $leadIds = (clone $base)->select('submissions.lead_id')->distinct();
        $statusPipeline = DB::table('gis_fair_leads as leads')
            ->joinSub($leadIds, 'filtered_leads', fn ($join) => $join->on('filtered_leads.lead_id', '=', 'leads.id'))
            ->select('leads.status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('leads.status')
            ->pluck('total', 'status');

        $countryPerformance = DB::table('gis_fair_leads as leads')
            ->joinSub(clone $leadIds, 'filtered_leads', fn ($join) => $join->on('filtered_leads.lead_id', '=', 'leads.id'))
            ->select('leads.country')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('leads.country')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'filters' => $filters,
            'campaigns' => GisFairCampaign::query()->orderByDesc('starts_at')->orderByDesc('id')->get(['id', 'name', 'code']),
            'sourceOptions' => $this->sourceOptions(),
            'businessTypes' => $this->leadOptions('business_type'),
            'countries' => $this->leadOptions('country'),
            'summary' => [
                'registrations' => $registrations,
                'prospects' => $uniqueProspects,
                'qualified' => $qualified,
                'customers' => $customers,
                'repeat_submissions' => max(0, $registrations - $uniqueProspects),
                'qualification_rate' => $this->rate($qualified, $uniqueProspects),
                'conversion_rate' => $this->rate($customers, $uniqueProspects),
                'marketing_consent_rate' => $this->rate($marketingConsents, $uniqueProspects),
            ],
            'trend' => $trend,
            'eventPerformance' => $eventPerformance,
            'sourcePerformance' => $sourcePerformance,
            'statusPipeline' => $statusPipeline,
            'countryPerformance' => $countryPerformance,
        ];
    }

    private function filteredSubmissions(array $filters): Builder
    {
        return DB::table('gis_fair_lead_submissions as submissions')
            ->join('gis_fair_leads as leads', 'leads.id', '=', 'submissions.lead_id')
            ->join('gis_fair_campaigns as campaigns', 'campaigns.id', '=', 'submissions.campaign_id')
            ->leftJoin('gis_fair_tracking_links as tracking_links', 'tracking_links.id', '=', 'submissions.tracking_link_id')
            ->whereNull('leads.deleted_at')
            ->whereNull('campaigns.deleted_at')
            ->whereIn('leads.spam_status', ['clean', 'not_spam'])
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, int $id) => $query->where('submissions.campaign_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('submissions.submitted_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('submissions.submitted_at', '<=', $date))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->whereRaw(self::SOURCE_EXPRESSION.' = ?', [$source]))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('leads.status', $status))
            ->when(($filters['marketing_consent'] ?? null) === 'yes', fn (Builder $query) => $query->where('leads.marketing_consent', true))
            ->when(($filters['marketing_consent'] ?? null) === 'no', fn (Builder $query) => $query->where('leads.marketing_consent', false))
            ->when($filters['business_type'] ?? null, fn (Builder $query, string $type) => $query->where('leads.business_type', $type))
            ->when($filters['country'] ?? null, fn (Builder $query, string $country) => $query->where('leads.country', $country));
    }

    private function sourceOptions(): array
    {
        return DB::table('gis_fair_lead_submissions as submissions')
            ->leftJoin('gis_fair_tracking_links as tracking_links', 'tracking_links.id', '=', 'submissions.tracking_link_id')
            ->selectRaw(self::SOURCE_EXPRESSION.' as source_name')
            ->distinct()
            ->orderBy('source_name')
            ->pluck('source_name')
            ->filter()
            ->values()
            ->all();
    }

    private function leadOptions(string $column): array
    {
        return DB::table('gis_fair_leads')
            ->whereNull('deleted_at')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    private function rate(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
    }
}
