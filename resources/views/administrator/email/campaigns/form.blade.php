@extends('layouts.UserLayout')

@section('title', 'New Email Campaign')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>New Email Campaign</h2><div class="crm-subtitle">Set the audience and delivery plan. Approval is required before launch.</div></div><a class="btn btn-light" href="{{ route('email.campaigns') }}"><i class="fas fa-arrow-left"></i> Back to campaigns</a></div>
            <form method="post" action="{{ route('email.campaigns.store') }}" class="email-form-panel">
                @csrf
                <section class="crm-panel">
                    <div class="email-panel-head"><div><h3 class="crm-panel-title">Campaign setup</h3><div class="email-panel-copy">Choose one audience and one delivery path.</div></div></div>
                    <div class="email-panel-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Name</label><input class="form-control" name="name" required placeholder="Q3 customer follow-up"></div>
                            <div class="col-md-6 form-group"><label>Type</label><select class="form-control" name="campaign_type" id="campaign_type"><option value="single">Single email</option><option value="sequence">Sequence</option></select></div>
                            <div class="col-md-6 form-group"><label>Target segment</label><select class="form-control" name="email_segment_id" required><option value="">Select audience</option>@foreach($segments as $segment)<option value="{{ $segment->id }}">{{ $segment->name }}</option>@endforeach</select></div>
                            <div class="col-md-6 form-group"><label>Template</label><select class="form-control" name="email_template_id"><option value="">Select template</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div>
                            <div class="col-md-6 form-group"><label>Sequence</label><select class="form-control" name="email_sequence_template_id"><option value="">Select sequence</option>@foreach($sequences as $sequence)<option value="{{ $sequence->id }}">{{ $sequence->name }}</option>@endforeach</select></div>
                        </div>
                    </div>
                </section>
                <section class="crm-panel">
                    <div class="email-panel-head"><div><h3 class="crm-panel-title">Delivery controls</h3><div class="email-panel-copy">Leave schedule empty to keep the campaign in draft for approval.</div></div></div>
                    <div class="email-panel-body">
                        <div class="row"><div class="col-md-6 form-group"><label>Scheduled at</label><input class="form-control" type="datetime-local" name="scheduled_at"></div><div class="col-md-6 form-group"><label>Sending limit</label><input class="form-control" type="number" min="1" name="sending_limit" placeholder="Optional"></div></div>
                        <div class="email-form-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save campaign</button><a class="btn btn-light" href="{{ route('email.campaigns') }}">Cancel</a></div>
                    </div>
                </section>
            </form>
        </div>
    </section>
</div>
@endsection
