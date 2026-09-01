@extends('layouts.main')

@section('title', 'GIS Fair Events')

@section('head')
    @include('administrator.gis-fair.partials.styles')
@endsection

@section('content')
<section class="funnel-page">
    <header class="funnel-heading">
        <div><h1>GIS Fair Events</h1><p>Control funnel availability, event details and measurable redirect URLs.</p></div>
        <div class="funnel-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('gis-fair.leads.index') }}"><i class="fas fa-users"></i> Leads</a><a class="btn btn-primary btn-sm" href="{{ route('gis-fair.campaigns.create') }}"><i class="fas fa-plus"></i> New event</a></div>
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <section class="funnel-panel">
        <div class="funnel-table-wrap">
            <table class="table table-hover funnel-table">
                <thead><tr><th>Event</th><th>Status</th><th>Dates</th><th>Location</th><th>Leads</th><th>Tracking URLs</th><th>Deadline</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td><strong>{{ $campaign->name }}</strong><div class="funnel-meta">{{ $campaign->code }}{{ $campaign->edition ? ' / '.$campaign->edition : '' }}</div></td>
                        <td><span class="funnel-badge funnel-badge-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span><div class="funnel-meta">{{ $campaign->accepting_submissions ? 'Submissions enabled' : 'Submissions paused' }}</div></td>
                        <td>{{ $campaign->dates_display ?: '-' }}</td>
                        <td>{{ $campaign->hall ?: '-' }} / {{ $campaign->booth ?: '-' }}</td>
                        <td>{{ number_format($campaign->leads_count) }}</td>
                        <td>{{ number_format($campaign->tracking_links_count) }}</td>
                        <td>{{ optional($campaign->offer_deadline)->format('d M Y H:i') ?: '-' }}</td>
                        <td><div class="funnel-inline"><a class="btn btn-sm btn-outline-primary" href="{{ route('gis-fair.campaigns.show', $campaign) }}" title="Manage event"><i class="fas fa-link"></i></a><a class="btn btn-sm btn-outline-secondary" href="{{ route('gis-fair.campaigns.edit', $campaign) }}" title="Edit event"><i class="fas fa-edit"></i></a>@if($campaign->leads_count === 0)<form method="post" action="{{ route('gis-fair.campaigns.destroy', $campaign) }}" data-confirm="Delete this event?" data-confirm-title="Delete event" data-confirm-tone="danger" data-confirm-button="Delete">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit" title="Delete event"><i class="fas fa-trash"></i></button></form>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="funnel-empty"><i class="fas fa-calendar-plus"></i>No fair events configured.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $campaigns->links() }}
    </section>
</section>
@endsection
