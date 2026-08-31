@extends('layouts.main')

@section('title', $campaign->exists ? 'Edit Fair Event' : 'New Fair Event')

@section('head')
    @include('administrator.gis-fair.partials.styles')
@endsection

@section('content')
<section class="funnel-page">
    <header class="funnel-heading">
        <div><h1>{{ $campaign->exists ? 'Edit fair event' : 'Create fair event' }}</h1><p>These values drive the public config API, registration availability and fair-code email.</p></div>
        <div class="funnel-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ $campaign->exists ? route('gis-fair.campaigns.show', $campaign) : route('gis-fair.campaigns.index') }}"><i class="fas fa-arrow-left"></i> Back</a></div>
    </header>

    @if($errors->any())<div class="alert alert-danger"><strong>Event was not saved.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form class="funnel-panel" method="post" action="{{ $campaign->exists ? route('gis-fair.campaigns.update', $campaign) : route('gis-fair.campaigns.store') }}">
        @csrf @if($campaign->exists) @method('put') @endif
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Event configuration</h2><div class="funnel-panel-copy">Only one event can be active at a time; activating this event closes the previous active event.</div></div></div>
        <div class="funnel-panel-body funnel-form-grid">
            <div class="span-2"><label for="name">Event name</label><input id="name" class="form-control" name="name" value="{{ old('name', $campaign->name) }}" required></div>
            <div><label for="code">Event code</label><input id="code" class="form-control" name="code" value="{{ old('code', $campaign->code) }}" placeholder="bgjf-74" required><div class="funnel-help">Stable API and attribution identifier.</div></div>
            <div><label for="edition">Edition</label><input id="edition" class="form-control" name="edition" value="{{ old('edition', $campaign->edition) }}" placeholder="BGJF #74"></div>
            <div><label for="status">Status</label><select id="status" class="form-control" name="status" required>@foreach(['draft' => 'Draft', 'active' => 'Active', 'closed' => 'Closed'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $campaign->status) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="code_prefix">Fair code prefix</label><input id="code_prefix" class="form-control" name="code_prefix" value="{{ old('code_prefix', $campaign->code_prefix) }}" required></div>
            <div class="full"><label for="landing_url">Default funnel URL</label><input id="landing_url" class="form-control" type="url" name="landing_url" value="{{ old('landing_url', $campaign->landing_url) }}" placeholder="https://gis247.net/fair/" required></div>
            <div><label for="hall">Hall</label><input id="hall" class="form-control" name="hall" value="{{ old('hall', $campaign->hall) }}"></div>
            <div><label for="booth">Booth</label><input id="booth" class="form-control" name="booth" value="{{ old('booth', $campaign->booth) }}"></div>
            <div><label for="dates_display">Display dates</label><input id="dates_display" class="form-control" name="dates_display" value="{{ old('dates_display', $campaign->dates_display) }}" placeholder="10-14 September 2026"></div>
            <div><label for="starts_at">Starts at</label><input id="starts_at" class="form-control" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($campaign->starts_at)->format('Y-m-d\TH:i')) }}"></div>
            <div><label for="ends_at">Ends at</label><input id="ends_at" class="form-control" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($campaign->ends_at)->format('Y-m-d\TH:i')) }}"></div>
            <div><label for="offer_deadline">Offer deadline</label><input id="offer_deadline" class="form-control" type="datetime-local" name="offer_deadline" value="{{ old('offer_deadline', optional($campaign->offer_deadline)->format('Y-m-d\TH:i')) }}"></div>
            <div><label for="timezone">Timezone</label><input id="timezone" class="form-control" name="timezone" value="{{ old('timezone', $campaign->timezone) }}" required></div>
            <div><label for="privacy_notice_version">Privacy notice version</label><input id="privacy_notice_version" class="form-control" name="privacy_notice_version" value="{{ old('privacy_notice_version', $campaign->privacy_notice_version) }}" required></div>
            <div><label for="contact_email">Privacy contact</label><input id="contact_email" class="form-control" type="email" name="contact_email" value="{{ old('contact_email', $campaign->contact_email) }}"></div>
            <div class="full"><label for="privacy_notice_url">Privacy notice URL</label><input id="privacy_notice_url" class="form-control" type="url" name="privacy_notice_url" value="{{ old('privacy_notice_url', $campaign->privacy_notice_url) }}"></div>
            <div class="full"><label class="funnel-check"><input type="checkbox" name="accepting_submissions" value="1" @checked(old('accepting_submissions', $campaign->accepting_submissions))> Accept public funnel submissions</label></div>
            <div class="full"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save event</button></div>
        </div>
    </form>
</section>
@endsection
