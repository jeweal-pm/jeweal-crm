@extends('layouts.main')

@section('title', 'Fair Lead '.$lead->fair_code)

@section('head')
    @include('administrator.gis-fair.partials.styles')
@endsection

@section('content')
<section class="funnel-page">
    <header class="funnel-heading">
        <div><h1>{{ $lead->first_name }} {{ $lead->last_name }}</h1><p>{{ $lead->campaign->name }} / <span class="funnel-code">{{ $lead->fair_code }}</span></p></div>
        <div class="funnel-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('gis-fair.leads.index') }}"><i class="fas fa-arrow-left"></i> Leads</a></div>
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Registration profile</h2><div class="funnel-panel-copy">Contact, business and product-fit information supplied by the registrant.</div></div><span class="funnel-badge funnel-badge-{{ $lead->status }}">{{ str_replace('_', ' / ', strtoupper($lead->status)) }}</span></div>
        <div class="funnel-detail-grid">
            @foreach([
                'Email' => $lead->email,
                'Phone' => $lead->phone_e164,
                'Country' => $lead->country,
                'Company' => $lead->company,
                'Business type' => $lead->business_type,
                'Store count' => number_format($lead->stores),
                'Current system' => $lead->current_system,
                'Interests' => implode(', ', $lead->interests ?: []),
                'Remark' => $lead->remark,
                'Assignee' => $lead->assignedTo?->name ?: 'Unassigned',
            ] as $label => $value)
                <div class="funnel-detail"><div class="funnel-detail-label">{{ $label }}</div><div class="funnel-detail-value">{{ $value ?: '-' }}</div></div>
            @endforeach
        </div>
    </section>

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Event attribution</h2><div class="funnel-panel-copy">First-touch event and redirect URL associated with this lead.</div></div></div>
        <div class="funnel-detail-grid">
            <div class="funnel-detail"><div class="funnel-detail-label">Event</div><div class="funnel-detail-value">{{ $lead->campaign->name }} ({{ $lead->campaign->code }})</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Tracking URL</div><div class="funnel-detail-value">{{ $lead->trackingLink?->name ?: 'Direct visit' }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Funnel design</div><div class="funnel-detail-value">{{ $lead->source }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">First submitted</div><div class="funnel-detail-value">{{ $lead->created_at->format('d M Y H:i') }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Last submitted</div><div class="funnel-detail-value">{{ optional($lead->last_submitted_at)->format('d M Y H:i') }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Submission count</div><div class="funnel-detail-value">{{ number_format($lead->submission_count) }}</div></div>
        </div>
    </section>

    <section class="funnel-panel">
        <div class="funnel-panel-head">
            <div><h2 class="funnel-panel-title">Consent &amp; delivery</h2><div class="funnel-panel-copy">Server-recorded notice evidence and fair-code confirmation history.</div></div>
            <div class="funnel-actions">
                @if(auth()->user()->hasCrmPermission('funnel.message.manage'))
                    <form method="post" action="{{ route('gis-fair.leads.resend', $lead) }}" data-confirm="Resend the fair code confirmation to {{ $lead->email }}?" data-confirm-title="Resend fair code" data-confirm-button="Resend">@csrf<button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-paper-plane"></i> Resend fair code</button></form>
                @endif
                @if($lead->marketing_consent && auth()->user()->hasCrmPermission('enquiry.update_status'))
                    <form method="post" action="{{ route('gis-fair.leads.withdraw-marketing', $lead) }}" data-confirm="Record marketing consent as withdrawn?" data-confirm-title="Withdraw marketing consent" data-confirm-tone="danger" data-confirm-button="Withdraw">@csrf<button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-ban"></i> Withdraw marketing</button></form>
                @endif
            </div>
        </div>
        <div class="funnel-detail-grid">
            <div class="funnel-detail"><div class="funnel-detail-label">Privacy notice</div><div class="funnel-detail-value">Accepted {{ optional($lead->privacy_agreed_at)->format('d M Y H:i') }} / version {{ $lead->privacy_notice_version }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Marketing consent</div><div class="funnel-detail-value">{{ $lead->marketing_consent ? 'Opted in' : 'Not opted in' }} @if($lead->marketing_consent_withdrawn_at) / withdrawn {{ $lead->marketing_consent_withdrawn_at->format('d M Y H:i') }} @endif</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Consent IP</div><div class="funnel-detail-value">{{ $lead->consent_ip ?: '-' }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Confirmation</div><div class="funnel-detail-value">{{ $lead->confirmation_send_count }} queued / last {{ optional($lead->confirmation_sent_at)->format('d M Y H:i') ?: 'never' }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Browser</div><div class="funnel-detail-value">{{ Str::limit($lead->consent_user_agent, 110) ?: '-' }}</div></div>
            <div class="funnel-detail"><div class="funnel-detail-label">Fair code</div><div class="funnel-detail-value"><span class="funnel-code">{{ $lead->fair_code }}</span></div></div>
        </div>
    </section>

    <section class="funnel-panel">
        <div class="funnel-panel-head"><div><h2 class="funnel-panel-title">Submission evidence</h2><div class="funnel-panel-copy">Each accepted submission is retained separately even when the lead is deduplicated.</div></div></div>
        <div class="funnel-table-wrap">
            <table class="table funnel-table"><thead><tr><th>Submitted</th><th>Source</th><th>Tracking link</th><th>Notice version</th><th>Marketing</th><th>IP</th></tr></thead><tbody>
            @foreach($lead->submissions as $submission)
                <tr><td>{{ $submission->submitted_at->format('d M Y H:i') }}</td><td>{{ $submission->source }}</td><td>{{ $submission->tracking_link_id ?: 'Direct' }}</td><td>{{ $submission->privacy_notice_version }}</td><td>{{ $submission->marketing_consent ? 'Yes' : 'No' }}</td><td>{{ $submission->consent_ip ?: '-' }}</td></tr>
            @endforeach
            </tbody></table>
        </div>
    </section>
</section>
@endsection
