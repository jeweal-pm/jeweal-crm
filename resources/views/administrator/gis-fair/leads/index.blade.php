@extends('layouts.main')

@section('title', 'GIS Fair Leads')

@section('head')
    @include('administrator.gis-fair.partials.styles')
    @include('administrator.enquiry.partials.bulk-action-styles')
@endsection

@section('content')
@php
    $bulkFormId = 'gis-fair-lead-bulk-actions';
    $bulkEnabled = auth()->user()->hasCrmPermission('enquiry.bulk_delete')
        || auth()->user()->hasCrmPermission('enquiry.restore')
        || auth()->user()->hasCrmPermission('enquiry.update_status')
        || $assignableUsers->isNotEmpty();
@endphp
<section class="funnel-page">
    <header class="funnel-heading">
        <div>
            <h1>GIS Fair Leads</h1>
            <p>Manage registrations, event attribution, fair codes and consent records.</p>
        </div>
        <div class="funnel-actions">
            @if(auth()->user()->hasCrmPermission('funnel.config.manage'))
                <a class="btn btn-outline-primary btn-sm" href="{{ route('gis-fair.campaigns.index') }}"><i class="fas fa-calendar-alt"></i> Events &amp; URLs</a>
            @endif
        </div>
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="funnel-stats">
        <div class="funnel-stat"><span>Total leads</span><strong>{{ number_format($summary['total']) }}</strong></div>
        <div class="funnel-stat"><span>Unassigned</span><strong>{{ number_format($summary['unassigned']) }}</strong></div>
        <div class="funnel-stat"><span>Customers</span><strong>{{ number_format($summary['customers']) }}</strong></div>
        <div class="funnel-stat"><span>Marketing opt-ins</span><strong>{{ number_format($summary['marketing']) }}</strong></div>
    </div>

    <section class="funnel-panel">
        <form class="funnel-panel-body funnel-filter" method="get" action="{{ route('gis-fair.leads.index') }}">
            <div><label for="q">Search</label><input id="q" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email, company or fair code"></div>
            <div><label for="campaign_id">Event</label><select id="campaign_id" class="form-control" name="campaign_id"><option value="">All events</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}" @selected(($filters['campaign_id'] ?? null) == $campaign->id)>{{ $campaign->name }}</option>@endforeach</select></div>
            <div><label for="status">Status</label><select id="status" class="form-control" name="status"><option value="">All statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="source">Source</label><select id="source" class="form-control" name="source"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(($filters['source'] ?? null) === $source)>{{ Str::headline($source) }}</option>@endforeach</select></div>
            <div><label for="marketing_consent">Marketing</label><select id="marketing_consent" class="form-control" name="marketing_consent"><option value="">Any</option><option value="yes" @selected(($filters['marketing_consent'] ?? null) === 'yes')>Opted in</option><option value="no" @selected(($filters['marketing_consent'] ?? null) === 'no')>Not opted in</option></select></div>
            <div><label for="trashed">Records</label><select id="trashed" class="form-control" name="trashed"><option value="">Active</option><option value="with" @selected(($filters['trashed'] ?? null) === 'with')>Include deleted</option><option value="only" @selected(($filters['trashed'] ?? null) === 'only')>Deleted only</option></select></div>
            <div class="funnel-inline"><button class="btn btn-primary" type="submit" title="Apply filters"><i class="fas fa-search"></i></button><a class="btn btn-outline-secondary" href="{{ route('gis-fair.leads.index') }}" title="Clear filters"><i class="fas fa-times"></i></a></div>
        </form>

        @if($bulkEnabled)
            @include('administrator.enquiry.partials.bulk-actions', [
                'bulkActionRoute' => route('gis-fair.leads.bulk-action'),
            ])
        @endif

        <div class="funnel-table-wrap">
            <table class="table table-hover funnel-table">
                <thead><tr>@if($bulkEnabled)<th class="crm-select-cell"><input class="crm-select-checkbox" type="checkbox" data-bulk-select-all="{{ $bulkFormId }}" aria-label="Select all GIS fair leads on this page"></th>@endif<th>Lead</th><th>Event / source</th><th>Fair code</th><th>Business</th><th>Status</th><th>Assignee</th><th>Consent</th><th>Submitted</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        @if($bulkEnabled)<td class="crm-select-cell"><input class="crm-select-checkbox" type="checkbox" name="ids[]" value="{{ $lead->id }}" form="{{ $bulkFormId }}" data-bulk-item aria-label="Select GIS fair lead {{ $lead->first_name }} {{ $lead->last_name }}"></td>@endif
                        <td><strong>{{ $lead->first_name }} {{ $lead->last_name }}</strong><div class="funnel-meta">{{ $lead->email }}</div><div class="funnel-meta">{{ $lead->phone_e164 }}</div></td>
                        <td><strong>{{ $lead->campaign->name }}</strong><div class="funnel-meta">{{ $lead->trackingLink?->name ?: 'Direct' }} / {{ $lead->source }}</div></td>
                        <td><span class="funnel-code">{{ $lead->fair_code }}</span><div class="funnel-meta">{{ $lead->submission_count }} submission{{ $lead->submission_count === 1 ? '' : 's' }}</div></td>
                        <td><strong>{{ $lead->company ?: 'Not provided' }}</strong><div class="funnel-meta">{{ $lead->business_type }} / {{ number_format($lead->stores) }} stores</div></td>
                        <td>
                            @if(!$lead->trashed() && auth()->user()->hasCrmPermission('enquiry.update_status'))
                                <form class="funnel-inline" method="post" action="{{ route('gis-fair.leads.status', $lead) }}">@csrf @method('patch')<select class="form-control form-control-sm" name="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-sm btn-outline-success" type="submit" title="Save status"><i class="fas fa-check"></i></button></form>
                            @else
                                <span class="funnel-badge funnel-badge-{{ $lead->status }}">{{ $statusOptions[$lead->status] ?? $lead->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if(!$lead->trashed() && $assignableUsers->isNotEmpty())
                                <form class="funnel-inline" method="post" action="{{ route('gis-fair.leads.assign', $lead) }}">@csrf<select class="form-control form-control-sm" name="user_id" required><option value="">Unassigned</option>@foreach($assignableUsers as $user)<option value="{{ $user->id }}" @selected($lead->assigned_to === $user->id)>{{ $user->name }}</option>@endforeach</select><button class="btn btn-sm btn-outline-primary" type="submit" title="Assign lead"><i class="fas fa-user-check"></i></button></form>
                            @else
                                {{ $lead->assignedTo?->name ?: 'Unassigned' }}
                            @endif
                        </td>
                        <td><span class="funnel-badge funnel-badge-{{ $lead->marketing_consent ? 'yes' : 'no' }}">Marketing {{ $lead->marketing_consent ? 'yes' : 'no' }}</span><div class="funnel-meta">Notice {{ $lead->privacy_notice_version }}</div></td>
                        <td>{{ optional($lead->last_submitted_at)->format('d M Y') }}<div class="funnel-meta">{{ optional($lead->last_submitted_at)->format('H:i') }}</div></td>
                        <td>
                            <div class="funnel-inline">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('gis-fair.leads.show', $lead) }}" title="View lead"><i class="fas fa-eye"></i></a>
                                @if($lead->trashed() && auth()->user()->hasCrmPermission('enquiry.restore'))
                                    <form method="post" action="{{ route('gis-fair.leads.restore', $lead->id) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit" title="Restore lead"><i class="fas fa-undo"></i></button></form>
                                @elseif(!$lead->trashed() && auth()->user()->hasCrmPermission('enquiry.delete'))
                                    <form method="post" action="{{ route('gis-fair.leads.destroy', $lead) }}" data-confirm="Move this fair lead to deleted records?" data-confirm-title="Delete fair lead" data-confirm-tone="danger" data-confirm-button="Delete">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit" title="Delete lead"><i class="fas fa-trash"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $bulkEnabled ? 10 : 9 }}"><div class="funnel-empty"><i class="fas fa-user-tag"></i>No fair leads match these filters.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $leads->links() }}
    </section>
</section>
@endsection
