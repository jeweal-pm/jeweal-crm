@extends('layouts.UserLayout')

@section('title', 'Email Segments')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>Email Segments</h2><div class="crm-subtitle">Build dynamic audiences or freeze a static snapshot for a campaign.</div></div><a class="btn btn-outline-secondary" href="{{ route('email.campaigns') }}"><i class="fas fa-paper-plane"></i> Campaigns</a></div>
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <section class="crm-panel h-100">
                        <div class="email-panel-head"><div><h3 class="crm-panel-title">Audience library</h3><div class="email-panel-copy">Dynamic segments recalculate; static segments preserve their snapshot.</div></div><div class="crm-result-count">{{ number_format($segments->total()) }} segments</div></div>
                        <div class="email-table-wrap"><table class="table table-hover crm-table"><thead><tr><th>Name</th><th>Code</th><th>Type</th><th>Conditions</th></tr></thead><tbody>
                        @forelse($segments as $segment)
                            <tr><td><div class="crm-primary">{{ $segment->name }}</div><div class="crm-muted">Created {{ optional($segment->created_at)->format('d/m/Y H:i') }}</div></td><td><span class="email-code">{{ $segment->code }}</span></td><td><span class="email-status email-status-{{ $segment->segment_type === 'dynamic' ? 'active' : 'approved' }}">{{ ucfirst($segment->segment_type) }}</span></td><td><code class="email-code">{{ json_encode($segment->conditions ?: []) }}</code></td></tr>
                        @empty
                            <tr><td colspan="4" class="crm-empty"><i class="fas fa-users email-empty-icon"></i>No segments found.</td></tr>
                        @endforelse
                        </tbody></table></div><div class="crm-pagination">{{ $segments->links() }}</div>
                    </section>
                </div>
                <div class="col-lg-4 mb-3">
                    <form class="crm-panel" method="post" action="{{ route('email.segments.store') }}">
                        @csrf
                        <div class="email-panel-head"><div><h3 class="crm-panel-title">New segment</h3><div class="email-panel-copy">Define the audience rule.</div></div></div>
                        <div class="email-panel-body">
                            <div class="form-group"><label>Name</label><input class="form-control" name="name" required></div>
                            <div class="form-group"><label>Code</label><input class="form-control" name="code" required placeholder="qualified-leads"></div>
                            <div class="form-group"><label>Type</label><select class="form-control" name="segment_type"><option value="dynamic">Dynamic</option><option value="static">Static snapshot</option></select></div>
                            <div class="form-group"><label>Subscription status</label><select class="form-control" name="subscription_status"><option value="">Any</option><option value="subscribed">Subscribed</option><option value="unsubscribed">Unsubscribed</option><option value="hard_bounced">Hard bounced</option></select></div>
                            <div class="form-group"><label>Enquiry type</label><select class="form-control" name="source_type"><option value="">Any</option><option value="general">General</option><option value="gis">GIS</option><option value="gms">GMS</option></select></div>
                            <div class="form-group"><label>Customer status</label><select class="form-control" name="customer_status"><option value="">Any</option><option value="lead_mql">Lead / MQL</option><option value="sql">SQL</option><option value="prospect">Prospect</option><option value="customer">Customer</option></select></div>
                            <div class="form-group"><label>Created within days</label><input class="form-control" type="number" min="1" name="created_after_days" placeholder="30"></div>
                            <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-plus"></i> Create segment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
