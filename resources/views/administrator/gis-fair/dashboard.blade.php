@extends('layouts.main')

@section('title', 'GIS Fair Dashboard')

@section('head')
@include('administrator.gis-fair.partials.styles')
<style>
    .funnel-dashboard-filter { display: grid; grid-template-columns: repeat(4, minmax(145px, 1fr)); gap: 10px; align-items: end; }
    .funnel-dashboard-filter .wide { grid-column: span 2; }
    .funnel-dashboard-filter .filter-actions { display: flex; gap: 8px; }
    .funnel-dashboard-stats { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .funnel-stat-note { display: block; margin-top: 6px; color: #728096; font-size: 11px; }
    .funnel-chart-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(300px, 1fr); gap: 16px; }
    .funnel-chart-grid.equal { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .funnel-chart-body { height: 300px; padding: 16px; position: relative; }
    .funnel-breakdown { display: grid; gap: 12px; padding: 16px; }
    .funnel-breakdown-row { display: grid; grid-template-columns: minmax(95px, 1fr) minmax(120px, 2fr) 44px; gap: 10px; align-items: center; }
    .funnel-breakdown-label { color: #344054; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .funnel-breakdown-track { height: 8px; overflow: hidden; background: #e9edf3; border-radius: 4px; }
    .funnel-breakdown-bar { height: 100%; background: #137f78; border-radius: 4px; }
    .funnel-breakdown-value { color: #172033; font-size: 12px; font-weight: 700; text-align: right; }
    .funnel-dashboard-table { min-width: 760px; }
    @media (max-width: 1200px) {
        .funnel-dashboard-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .funnel-dashboard-filter { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 900px) {
        .funnel-chart-grid, .funnel-chart-grid.equal { grid-template-columns: 1fr; }
        .funnel-dashboard-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575px) {
        .funnel-dashboard-stats, .funnel-dashboard-filter { grid-template-columns: 1fr; }
        .funnel-dashboard-filter .wide { grid-column: auto; }
        .funnel-dashboard-filter .filter-actions { flex-direction: column; }
    }
</style>
@endsection

@section('content')
<section class="funnel-page">
    <header class="funnel-heading">
        <div><h1>GIS Fair Dashboard</h1><p>Registration volume, prospect quality and campaign attribution across every fair event.</p></div>
        <div class="funnel-actions">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('gis-fair.leads.index') }}"><i class="fas fa-users"></i> Prospects</a>
            @if(auth()->user()->hasCrmPermission('funnel.config.manage'))
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('gis-fair.campaigns.index') }}"><i class="fas fa-calendar-alt"></i> Events</a>
            @endif
        </div>
    </header>

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Report filters</h2><div class="funnel-panel-copy">Every metric and chart below uses this same audience and submission period.</div></div></div>
        <form class="funnel-panel-body funnel-dashboard-filter" method="get" action="{{ route('gis-fair.dashboard') }}">
            <div class="wide"><label for="campaign_id">Event</label><select id="campaign_id" class="form-control" name="campaign_id"><option value="">All events</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}" @selected((string) ($filters['campaign_id'] ?? '') === (string) $campaign->id)>{{ $campaign->name }} ({{ $campaign->code }})</option>@endforeach</select></div>
            <div><label for="date_from">From</label><input id="date_from" class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div><label for="date_to">To</label><input id="date_to" class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div><label for="source">Source</label><select id="source" class="form-control" name="source"><option value="">All sources</option>@foreach($sourceOptions as $source)<option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ str($source)->headline() }}</option>@endforeach</select></div>
            <div><label for="status">Lifecycle status</label><select id="status" class="form-control" name="status"><option value="">All statuses</option>@foreach(['lead_mql' => 'Lead / MQL', 'sql' => 'SQL', 'prospect' => 'Prospect', 'customer' => 'Customer'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="marketing_consent">Marketing consent</label><select id="marketing_consent" class="form-control" name="marketing_consent"><option value="">Any consent</option><option value="yes" @selected(($filters['marketing_consent'] ?? '') === 'yes')>Consented</option><option value="no" @selected(($filters['marketing_consent'] ?? '') === 'no')>Not consented</option></select></div>
            <div><label for="business_type">Business type</label><select id="business_type" class="form-control" name="business_type"><option value="">All business types</option>@foreach($businessTypes as $type)<option value="{{ $type }}" @selected(($filters['business_type'] ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div><label for="country">Country</label><select id="country" class="form-control" name="country"><option value="">All countries</option>@foreach($countries as $country)<option value="{{ $country }}" @selected(($filters['country'] ?? '') === $country)>{{ $country }}</option>@endforeach</select></div>
            <div class="filter-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Apply filters</button><a class="btn btn-outline-secondary" href="{{ route('gis-fair.dashboard') }}"><i class="fas fa-times"></i> Clear</a></div>
        </form>
    </section>

    <div class="funnel-stats funnel-dashboard-stats">
        <div class="funnel-stat"><span>Registrations</span><strong>{{ number_format($summary['registrations']) }}</strong><small class="funnel-stat-note">All accepted submissions</small></div>
        <div class="funnel-stat"><span>Unique prospects</span><strong>{{ number_format($summary['prospects']) }}</strong><small class="funnel-stat-note">Deduplicated leads</small></div>
        <div class="funnel-stat"><span>Qualified</span><strong>{{ number_format($summary['qualified']) }}</strong><small class="funnel-stat-note">{{ $summary['qualification_rate'] }}% of prospects</small></div>
        <div class="funnel-stat"><span>Customers</span><strong>{{ number_format($summary['customers']) }}</strong><small class="funnel-stat-note">{{ $summary['conversion_rate'] }}% conversion</small></div>
        <div class="funnel-stat"><span>Repeat submissions</span><strong>{{ number_format($summary['repeat_submissions']) }}</strong><small class="funnel-stat-note">Registration minus unique</small></div>
        <div class="funnel-stat"><span>Marketing consent</span><strong>{{ $summary['marketing_consent_rate'] }}%</strong><small class="funnel-stat-note">Current consent state</small></div>
    </div>

    <div class="funnel-chart-grid">
        <section class="funnel-panel">
            <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Registration trend</h2><div class="funnel-panel-copy">Daily registrations compared with unique prospects.</div></div></div>
            <div class="funnel-chart-body"><canvas id="registration-trend" aria-label="Registration trend chart"></canvas></div>
        </section>
        <section class="funnel-panel">
            <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Lifecycle pipeline</h2><div class="funnel-panel-copy">Current stage of prospects in the filtered audience.</div></div></div>
            <div class="funnel-chart-body"><canvas id="status-pipeline" aria-label="Lifecycle pipeline chart"></canvas></div>
        </section>
    </div>

    <div class="funnel-chart-grid equal">
        <section class="funnel-panel">
            <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Top acquisition sources</h2><div class="funnel-panel-copy">Tracking-link source is used first, then the submitted funnel source.</div></div></div>
            @php $sourceMax = max(1, (int) ($sourcePerformance->max('registrations') ?? 0)); @endphp
            <div class="funnel-breakdown">@forelse($sourcePerformance as $source)<div class="funnel-breakdown-row"><div class="funnel-breakdown-label" title="{{ $source->source_name }}">{{ str($source->source_name)->headline() }}</div><div class="funnel-breakdown-track"><div class="funnel-breakdown-bar" style="width: {{ round(((int) $source->registrations / $sourceMax) * 100, 1) }}%"></div></div><div class="funnel-breakdown-value">{{ number_format($source->registrations) }}</div></div>@empty<div class="funnel-empty"><i class="fas fa-chart-bar"></i>No source data for these filters.</div>@endforelse</div>
        </section>
        <section class="funnel-panel">
            <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Top prospect countries</h2><div class="funnel-panel-copy">Unique prospects grouped by submitted country.</div></div></div>
            @php $countryMax = max(1, (int) ($countryPerformance->max('total') ?? 0)); @endphp
            <div class="funnel-breakdown">@forelse($countryPerformance as $country)<div class="funnel-breakdown-row"><div class="funnel-breakdown-label" title="{{ $country->country }}">{{ $country->country }}</div><div class="funnel-breakdown-track"><div class="funnel-breakdown-bar" style="width: {{ round(((int) $country->total / $countryMax) * 100, 1) }}%"></div></div><div class="funnel-breakdown-value">{{ number_format($country->total) }}</div></div>@empty<div class="funnel-empty"><i class="fas fa-globe"></i>No country data for these filters.</div>@endforelse</div>
        </section>
    </div>

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Event performance</h2><div class="funnel-panel-copy">Volume and current customer conversion for each event represented by the filters.</div></div><span class="funnel-meta">{{ $eventPerformance->count() }} events</span></div>
        <div class="funnel-table-wrap"><table class="table table-hover funnel-table funnel-dashboard-table"><thead><tr><th>Event</th><th>Status</th><th>Registrations</th><th>Unique prospects</th><th>Customers</th><th>Conversion</th></tr></thead><tbody>@forelse($eventPerformance as $event)<tr><td><strong>{{ $event->name }}</strong><div class="funnel-meta">{{ $event->code }}</div></td><td><span class="funnel-badge funnel-badge-{{ $event->status }}">{{ ucfirst($event->status) }}</span></td><td>{{ number_format($event->registrations) }}</td><td>{{ number_format($event->prospects) }}</td><td>{{ number_format($event->customers) }}</td><td>{{ $event->conversion_rate }}%</td></tr>@empty<tr><td colspan="6"><div class="funnel-empty"><i class="fas fa-calendar"></i>No registrations match the selected filters.</div></td></tr>@endforelse</tbody></table></div>
    </section>
</section>
@endsection

@section('footer_script')
@php
    $pipelineChart = [
        'Lead / MQL' => (int) ($statusPipeline['lead_mql'] ?? 0),
        'SQL' => (int) ($statusPipeline['sql'] ?? 0),
        'Prospect' => (int) ($statusPipeline['prospect'] ?? 0),
        'Customer' => (int) ($statusPipeline['customer'] ?? 0),
    ];
@endphp
<script src="{{ URL::asset('/plugins/chart.js/Chart.bundle.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var trendElement = document.getElementById('registration-trend');
        var pipelineElement = document.getElementById('status-pipeline');
        var trend = @json($trend);
        var pipeline = @json($pipelineChart);

        if (trendElement) {
            new Chart(trendElement, {
                type: 'line',
                data: {
                    labels: trend.map(function (item) { return item.period; }),
                    datasets: [
                        { label: 'Registrations', data: trend.map(function (item) { return item.registrations; }), borderColor: '#137f78', backgroundColor: 'rgba(19, 127, 120, .12)', borderWidth: 2, pointRadius: 3, fill: true },
                        { label: 'Unique prospects', data: trend.map(function (item) { return item.prospects; }), borderColor: '#315b96', backgroundColor: 'transparent', borderWidth: 2, pointRadius: 3, fill: false }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' }, scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] } }
            });
        }

        if (pipelineElement) {
            new Chart(pipelineElement, {
                type: 'doughnut',
                data: { labels: Object.keys(pipeline), datasets: [{ data: Object.values(pipeline), backgroundColor: ['#d9e8fb', '#a8d8c0', '#f4d89a', '#4b9b91'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' }, cutoutPercentage: 66 }
            });
        }
    });
</script>
@endsection
