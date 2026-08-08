@extends('layouts.UserLayout')

@section('title', 'Email Campaigns')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>Email Campaigns</h2><div class="crm-subtitle">Approval-controlled single sends and sequence launches.</div></div><a class="btn btn-primary" href="{{ route('email.campaigns.create') }}"><i class="fas fa-plus"></i> New campaign</a></div>
            <section class="crm-panel">
                <div class="email-panel-head"><div><h3 class="crm-panel-title">Campaign queue</h3><div class="email-panel-copy">Review audience, approval and send status before launch.</div></div><div class="crm-result-count">{{ number_format($campaigns->total()) }} campaigns</div></div>
                <div class="email-table-wrap"><table class="table table-hover crm-table"><thead><tr><th>Campaign</th><th>Type</th><th>Audience</th><th>Approval</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td><div class="crm-primary">{{ $campaign->name }}</div><div class="crm-muted">{{ optional($campaign->scheduled_at)->format('d/m/Y H:i') ?: 'No schedule' }}</div></td>
                        <td>{{ ucfirst($campaign->campaign_type) }}</td>
                        <td>{{ optional($campaign->segment)->name ?: '-' }}</td>
                        <td><span class="email-status email-status-{{ str_replace('_', '-', $campaign->approval_status) }}">{{ ucfirst($campaign->approval_status) }}</span></td>
                        <td><span class="email-status email-status-{{ str_replace('_', '-', $campaign->status) }}">{{ ucfirst($campaign->status) }}</span><div class="crm-muted mt-1">{{ $campaign->variants->count() }} A/B variants</div></td>
                        <td>
                            <div class="email-inline-actions">
                                @if($campaign->approval_status !== 'approved')<form class="d-inline" method="post" action="{{ route('email.campaigns.approve', $campaign->id) }}">@csrf<button class="btn btn-sm btn-outline-success" title="Approve"><i class="fas fa-check"></i></button></form>@endif
                                @if($campaign->approval_status === 'approved' && in_array($campaign->status, ['scheduled', 'draft'], true))<form class="d-inline" method="post" action="{{ route('email.campaigns.run', $campaign->id) }}">@csrf<button class="btn btn-sm btn-primary" title="Send"><i class="fas fa-paper-plane"></i></button></form>@endif
                                <details><summary class="btn btn-sm btn-outline-secondary"><i class="fas fa-flask"></i> A/B</summary><form class="mt-2" method="post" action="{{ route('email.campaigns.variants.store', $campaign->id) }}">@csrf<div class="d-flex" style="gap:4px"><input class="form-control form-control-sm" name="variant_key" placeholder="A" required><select class="form-control form-control-sm" name="email_template_id"><option value="">Base</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select><input class="form-control form-control-sm" name="allocation" type="number" min="1" max="100" value="50" required><input type="hidden" name="success_metric" value="click_rate"><input type="hidden" name="minimum_sample_size" value="100"><button class="btn btn-sm btn-outline-primary" title="Save variant"><i class="fas fa-save"></i></button></div></form></details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="crm-empty"><i class="fas fa-paper-plane email-empty-icon"></i>No campaigns found.</td></tr>
                @endforelse
                </tbody></table></div><div class="crm-pagination">{{ $campaigns->links() }}</div>
            </section>
        </div>
    </section>
</div>
@endsection
