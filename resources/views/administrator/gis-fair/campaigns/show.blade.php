@extends('layouts.main')

@section('title', $campaign->name)

@section('head')
    @include('administrator.gis-fair.partials.styles')
@endsection

@section('content')
<section class="funnel-page">
    <header class="funnel-heading">
        <div><h1>{{ $campaign->name }}</h1><p>{{ $campaign->edition ?: $campaign->code }} / {{ $campaign->dates_display ?: 'Dates not set' }}</p></div>
        <div class="funnel-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('gis-fair.campaigns.index') }}"><i class="fas fa-arrow-left"></i> Events</a><a class="btn btn-outline-primary btn-sm" href="{{ route('gis-fair.campaigns.edit', $campaign) }}"><i class="fas fa-edit"></i> Edit event</a></div>
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="funnel-stats">
        <div class="funnel-stat"><span>Event status</span><strong style="font-size:18px">{{ ucfirst($campaign->status) }}</strong></div>
        <div class="funnel-stat"><span>Leads</span><strong>{{ number_format($campaign->leads_count) }}</strong></div>
        <div class="funnel-stat"><span>Redirect clicks</span><strong>{{ number_format($campaign->trackingLinks->sum('click_count')) }}</strong></div>
        <div class="funnel-stat"><span>Attributed leads</span><strong>{{ number_format($campaign->trackingLinks->sum('lead_count')) }}</strong></div>
    </div>

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Public integration</h2><div class="funnel-panel-copy">Use these endpoints in the static funnel page.</div></div></div>
        <div class="funnel-panel-body funnel-form-grid">
            <div class="full"><label>Config API</label><div class="funnel-copy-field"><input id="config-api" class="form-control" readonly value="{{ url('/api/gis-fair-config?event='.$campaign->code) }}"><button class="btn btn-outline-secondary" type="button" onclick="copyFunnelValue('config-api')" title="Copy config API"><i class="fas fa-copy"></i></button></div></div>
            <div class="full"><label>Lead API</label><div class="funnel-copy-field"><input id="lead-api" class="form-control" readonly value="{{ url('/api/gis-fair-lead') }}"><button class="btn btn-outline-secondary" type="button" onclick="copyFunnelValue('lead-api')" title="Copy lead API"><i class="fas fa-copy"></i></button></div></div>
        </div>
    </section>

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Tracking URLs</h2><div class="funnel-panel-copy">Each URL records a privacy-minimised visit and passes event attribution to the funnel.</div></div><span class="funnel-meta">{{ $campaign->trackingLinks->count() }} URLs</span></div>
        @forelse($campaign->trackingLinks as $link)
            <form class="funnel-link-row" method="post" action="{{ route('gis-fair.links.update', [$campaign, $link]) }}">
                @csrf @method('put')
                <div><label>Name</label><input class="form-control" name="name" value="{{ $link->name }}" required><div class="funnel-meta">{{ number_format($link->click_count) }} clicks / {{ number_format($link->lead_count) }} leads</div></div>
                <div><label>Tracking URL</label><div class="funnel-copy-field"><input id="tracking-url-{{ $link->id }}" class="form-control" readonly value="{{ route('gis-fair.redirect', $link->code) }}"><button class="btn btn-outline-secondary" type="button" onclick="copyFunnelValue('tracking-url-{{ $link->id }}')" title="Copy tracking URL"><i class="fas fa-copy"></i></button></div><label class="mt-1">Destination URL</label><input class="form-control" type="url" name="destination_url" value="{{ $link->destination_url }}" placeholder="Event default destination"><input type="hidden" name="code" value="{{ $link->code }}"></div>
                <div><label>Source</label><input class="form-control" name="source" value="{{ $link->source }}" placeholder="facebook"></div>
                <div><label>Medium</label><input class="form-control" name="medium" value="{{ $link->medium }}" placeholder="social"></div>
                <div><label>Content</label><input class="form-control" name="content" value="{{ $link->content }}" placeholder="hero-banner"></div>
                <div><label>Expires at</label><input class="form-control" type="datetime-local" name="expires_at" value="{{ optional($link->expires_at)->format('Y-m-d\TH:i') }}"><label class="mt-1">Expired redirect URL</label><input class="form-control" type="url" name="expired_redirect_url" value="{{ $link->expired_redirect_url }}" placeholder="https://jeweal.com"><label class="funnel-check"><input type="checkbox" name="is_active" value="1" @checked($link->is_active)> Active</label></div>
                <div class="funnel-inline"><button class="btn btn-sm btn-outline-primary" type="submit" title="Save tracking URL"><i class="fas fa-save"></i></button><button class="btn btn-sm btn-outline-danger" type="submit" form="delete-link-{{ $link->id }}" title="Delete tracking URL"><i class="fas fa-trash"></i></button></div>
            </form>
            <form id="delete-link-{{ $link->id }}" method="post" action="{{ route('gis-fair.links.destroy', [$campaign, $link]) }}" data-confirm="Delete this tracking URL? Existing attribution records will remain attached to the event." data-confirm-title="Delete tracking URL" data-confirm-tone="danger" data-confirm-button="Delete">@csrf @method('delete')</form>
        @empty
            <div class="funnel-empty"><i class="fas fa-link"></i>No tracking URLs yet.</div>
        @endforelse
    </section>

    <form class="funnel-panel" method="post" action="{{ route('gis-fair.links.store', $campaign) }}">
        @csrf
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Create tracking URL</h2><div class="funnel-panel-copy">Create one URL per channel, placement or partner for useful attribution.</div></div></div>
        <div class="funnel-panel-body funnel-form-grid">
            <div><label for="link-name">Name</label><input id="link-name" class="form-control" name="name" value="{{ old('name') }}" placeholder="Facebook launch post" required></div>
            <div><label for="link-code">Short code</label><input id="link-code" class="form-control" name="code" value="{{ old('code') }}" placeholder="bgjf74-facebook" required></div>
            <div><label for="link-destination">Destination override</label><input id="link-destination" class="form-control" type="url" name="destination_url" value="{{ old('destination_url') }}" placeholder="Uses event funnel URL"></div>
            <div><label for="link-source">Source</label><input id="link-source" class="form-control" name="source" value="{{ old('source') }}" placeholder="facebook"></div>
            <div><label for="link-medium">Medium</label><input id="link-medium" class="form-control" name="medium" value="{{ old('medium') }}" placeholder="social"></div>
            <div><label for="link-content">Content</label><input id="link-content" class="form-control" name="content" value="{{ old('content') }}" placeholder="launch-post"></div>
            <div><label for="link-expires">Expires at</label><input id="link-expires" class="form-control" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"></div>
            <div class="span-2"><label for="link-expired-redirect">Expired redirect URL</label><input id="link-expired-redirect" class="form-control" type="url" name="expired_redirect_url" value="{{ old('expired_redirect_url') }}" placeholder="https://jeweal.com"><div class="funnel-help">Used when this link or its event is no longer available. Defaults to https://jeweal.com.</div></div>
            <div><label class="funnel-check"><input type="checkbox" name="is_active" value="1" checked> Active immediately</label></div>
            <div class="full"><button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Create tracking URL</button></div>
        </div>
    </form>
</section>

<script>
function copyFunnelValue(id) {
    var input = document.getElementById(id);
    if (!input) return;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value);
    } else {
        input.select();
        document.execCommand('copy');
    }
}
</script>
@endsection
