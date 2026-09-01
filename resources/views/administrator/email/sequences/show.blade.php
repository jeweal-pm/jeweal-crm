@extends('layouts.UserLayout')

@section('title', $sequence->name)

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>{{ $sequence->name }}</h2><div class="crm-subtitle"><span class="email-code">{{ $sequence->code }}</span> <span class="ml-2">v{{ $sequence->version }}</span></div></div><div class="crm-topbar-actions"><span class="email-status email-status-{{ str_replace('_', '-', $sequence->status) }}">{{ ucfirst($sequence->status) }}</span><a class="btn btn-light" href="{{ route('email.sequences') }}"><i class="fas fa-arrow-left"></i> Back to sequences</a></div></div>
            @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="crm-panel mb-3">
                <div class="email-panel-head"><div><h3 class="crm-panel-title">Sequence settings</h3><div class="email-panel-copy">Publish only after the steps have been reviewed.</div></div></div>
                <form method="post" action="{{ route('email.sequences.update', $sequence->id) }}" class="email-panel-body">@csrf @method('put')<div class="row"><div class="col-md-5 form-group"><label>Name</label><input class="form-control" name="name" value="{{ old('name', $sequence->name) }}" required></div><div class="col-md-3 form-group"><label>Code</label><input class="form-control" name="code" value="{{ old('code', $sequence->code) }}" required></div><div class="col-md-4 form-group"><label>Status</label><select class="form-control" name="status"><option value="draft" @selected($sequence->status === 'draft')>Draft</option><option value="published" @selected($sequence->status === 'published')>Published</option><option value="paused" @selected($sequence->status === 'paused')>Paused</option><option value="archived" @selected($sequence->status === 'archived')>Archived</option></select></div><div class="col-12 form-group"><label>Description</label><textarea class="form-control" name="description" rows="3">{{ old('description', $sequence->description) }}</textarea></div></div><div class="email-form-actions"><button class="btn btn-outline-primary" type="submit"><i class="fas fa-save"></i> Save settings</button></div></form>
            </section>

            <div class="email-section-label">Sequence steps</div>
            @forelse($sequence->steps as $step)
                <section class="crm-panel mb-3">
                    <div class="email-panel-head"><div><h3 class="crm-panel-title">Step {{ $step->step_number }}</h3><div class="email-panel-copy">Sent {{ $step->delay_value }} {{ $step->delay_unit }} after the previous step.</div></div><form method="post" action="{{ route('email.sequences.steps.destroy', [$sequence->id, $step->id]) }}" data-confirm="Remove Step {{ $step->step_number }}?" data-confirm-title="Remove sequence step" data-confirm-tone="danger" data-confirm-button="Remove">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit" title="Remove step"><i class="fas fa-trash"></i></button></form></div>
                    <form method="post" action="{{ route('email.sequences.steps.update', [$sequence->id, $step->id]) }}" class="email-panel-body">@csrf @method('put')<input type="hidden" name="step_number" value="{{ $step->step_number }}"><div class="row"><div class="col-md-5 form-group"><label>Template</label><select class="form-control" name="email_template_id" required>@foreach($templates as $template)<option value="{{ $template->id }}" @selected($template->id === $step->email_template_id)>{{ $template->name }}</option>@endforeach</select></div><div class="col-md-3 form-group"><label>Delay after previous step</label><input class="form-control" type="number" min="0" name="delay_value" value="{{ $step->delay_value }}" required></div><div class="col-md-2 form-group"><label>Unit</label><select class="form-control" name="delay_unit"><option value="minutes" @selected($step->delay_unit === 'minutes')>Minutes</option><option value="hours" @selected($step->delay_unit === 'hours')>Hours</option><option value="days" @selected($step->delay_unit === 'days')>Days</option></select></div><div class="col-md-2 form-group d-flex align-items-end"><button class="btn btn-outline-primary btn-block" type="submit"><i class="fas fa-save"></i> Save step</button></div></div></form>
                </section>
            @empty
                <section class="crm-panel crm-empty mb-3"><i class="fas fa-stream email-empty-icon"></i>No steps added yet.</section>
            @endforelse

            <section class="crm-panel">
                <div class="email-panel-head"><div><h3 class="crm-panel-title">Add step {{ $nextStepNumber }}</h3><div class="email-panel-copy">The next step number is assigned automatically to preserve the sending order.</div></div></div>
                @if($templates->isEmpty())
                    <div class="email-panel-body"><div class="alert alert-warning mb-0">Publish an email template before adding a sequence step.</div></div>
                @else
                    <form method="post" action="{{ route('email.sequences.steps.store', $sequence->id) }}" class="email-panel-body">@csrf<input type="hidden" name="step_number" value="{{ $nextStepNumber }}"><div class="row"><div class="col-md-2 form-group"><label>Step</label><input class="form-control" value="{{ $nextStepNumber }}" readonly></div><div class="col-md-5 form-group"><label>Template</label><select class="form-control" name="email_template_id" required>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div><div class="col-md-2 form-group"><label>Delay</label><input class="form-control" type="number" min="0" name="delay_value" value="0" required></div><div class="col-md-2 form-group"><label>Unit</label><select class="form-control" name="delay_unit"><option value="minutes">Minutes</option><option value="hours">Hours</option><option value="days">Days</option></select></div><div class="col-md-1 form-group d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit" title="Add step"><i class="fas fa-plus"></i></button></div></div></form>
                @endif
            </section>
        </div>
    </section>
</div>
@endsection
