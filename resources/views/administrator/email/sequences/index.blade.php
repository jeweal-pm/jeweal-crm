@extends('layouts.UserLayout')

@section('title', 'Email Sequences')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>Email Sequence Templates</h2><div class="crm-subtitle">Create reusable step-based journeys with delay and sending rules.</div></div><a class="btn btn-outline-secondary" href="{{ route('email.enrollments') }}"><i class="fas fa-user-plus"></i> Enrollments</a></div>
            <div class="row">
                <div class="col-lg-8 mb-3">
                    @forelse($sequences as $sequence)
                        <section class="crm-panel mb-3">
                            <div class="email-panel-head"><div><div class="crm-primary">{{ $sequence->name }}</div><div class="crm-muted"><span class="email-code">{{ $sequence->code }}</span> <span class="ml-2">v{{ $sequence->version }}</span></div></div><div class="email-inline-actions"><span class="email-status email-status-{{ str_replace('_', '-', $sequence->status) }}">{{ ucfirst($sequence->status) }}</span><span class="email-status email-status-approved">{{ $sequence->steps_count }} steps</span></div></div>
                            <div class="email-panel-body">
                                @foreach($sequence->steps as $step)
                                    <div class="d-flex align-items-center border-bottom py-2"><span class="crm-avatar mr-2">{{ $step->step_number }}</span><div><strong>Step {{ $step->step_number }}</strong><div class="crm-muted">{{ $step->template->name }} &middot; {{ $step->delay_value }} {{ $step->delay_unit }} delay</div></div></div>
                                @endforeach
                                <form class="mt-3" method="post" action="{{ route('email.sequences.steps.store', $sequence->id) }}">@csrf<div class="email-section-label">Add sequence step</div><div class="form-row align-items-end"><div class="col-md-2 form-group"><label>Step</label><input class="form-control" type="number" min="1" name="step_number" required></div><div class="col-md-4 form-group"><label>Template</label><select class="form-control" name="email_template_id" required>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div><div class="col-md-2 form-group"><label>Delay</label><input class="form-control" type="number" min="0" name="delay_value" value="0" required></div><div class="col-md-2 form-group"><label>Unit</label><select class="form-control" name="delay_unit"><option value="minutes">Minutes</option><option value="hours">Hours</option><option value="days">Days</option></select></div><div class="col-md-2 form-group"><button class="btn btn-outline-primary btn-block" type="submit" title="Save step"><i class="fas fa-save"></i> Add</button></div></div></form>
                            </div>
                        </section>
                    @empty
                        <div class="crm-panel crm-empty"><i class="fas fa-stream email-empty-icon"></i>No sequences found.</div>
                    @endforelse
                    <div class="crm-pagination">{{ $sequences->links() }}</div>
                </div>
                <div class="col-lg-4 mb-3">
                    <form class="crm-panel" method="post" action="{{ route('email.sequences.store') }}">
                        @csrf
                        <div class="email-panel-head"><div><h3 class="crm-panel-title">New sequence</h3><div class="email-panel-copy">Start with a reusable journey.</div></div></div>
                        <div class="email-panel-body"><div class="form-group"><label>Name</label><input class="form-control" name="name" required></div><div class="form-group"><label>Code</label><input class="form-control" name="code" required placeholder="post-enquiry-follow-up"></div><div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="4"></textarea></div><div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="draft">Draft</option><option value="published">Published</option><option value="paused">Paused</option></select></div><button class="btn btn-primary btn-block" type="submit"><i class="fas fa-plus"></i> Create sequence</button></div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
