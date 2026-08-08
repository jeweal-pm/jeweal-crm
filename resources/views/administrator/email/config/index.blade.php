@extends('layouts.UserLayout')

@section('title', 'Email Automation Config')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar">
                <div class="crm-title"><h2>Email Automation Config</h2><div class="crm-subtitle">Configure customer confirmation, internal notification and welcome email per enquiry type.</div></div>
                <a class="btn btn-outline-secondary" href="{{ route('email.templates') }}"><i class="fas fa-file-alt"></i> Template library</a>
            </div>

            <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> Each enquiry type has its own automation rules. When Recipients is empty, internal email uses <code>EMAIL_INTERNAL_RECIPIENTS</code>.</div>

            @foreach($configs as $config)
                @php($internalRecipients = $config->internal_to ?: config('email_management.internal_recipients'))
                <form class="crm-panel mb-3" method="post" action="{{ route('email.config.update', $config->enquiry_type) }}">
                    @csrf @method('put')
                    <div class="email-panel-head">
                        <div><h3 class="crm-panel-title text-uppercase">{{ $config->enquiry_type }} enquiry</h3><div class="email-panel-copy">Customer-facing and internal routing for this enquiry stream.</div></div>
                        <span class="email-status {{ $config->customer_enabled || $config->internal_enabled ? 'email-status-active' : 'email-status-paused' }}">{{ $config->customer_enabled || $config->internal_enabled ? 'Active' : 'Paused' }}</span>
                    </div>
                    <div class="email-panel-body">
                        <div class="email-section-label">Customer confirmation</div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label class="d-flex align-items-center"><input class="mr-2" type="checkbox" name="customer_enabled" value="1" @checked($config->customer_enabled)> Enable customer email</label><select class="form-control" name="customer_template_id"><option value="">Select template</option>@foreach($templates as $t)<option value="{{ $t->id }}" @selected($config->customer_template_id === $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                            <div class="col-md-6 form-group"><label>Customer delay (seconds)</label><input class="form-control" type="number" min="0" name="customer_delay_seconds" value="{{ $config->customer_delay_seconds }}"><div class="email-helper mt-1">Use 0 to dispatch immediately.</div></div>
                        </div>

                        <div class="email-section-label mt-3">Internal notification</div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label class="d-flex align-items-center"><input class="mr-2" type="checkbox" name="internal_enabled" value="1" @checked($config->internal_enabled)> Enable internal email</label><select class="form-control" name="internal_template_id"><option value="">Select template</option>@foreach($templates as $t)<option value="{{ $t->id }}" @selected($config->internal_template_id === $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                            <div class="col-md-6 form-group"><label>Recipients</label><textarea class="form-control" name="internal_to" rows="3" placeholder="one@example.com&#10;two@example.com">{{ implode("\n", $internalRecipients) }}</textarea><div class="email-helper mt-1">One email per line. Clear this field to use the environment default.</div></div>
                            <div class="col-md-4 form-group"><label>CC</label><textarea class="form-control" name="internal_cc" rows="3" placeholder="email@example.com">{{ implode("\n", $config->internal_cc ?: []) }}</textarea></div>
                            <div class="col-md-4 form-group"><label>BCC</label><textarea class="form-control" name="internal_bcc" rows="3" placeholder="email@example.com">{{ implode("\n", $config->internal_bcc ?: []) }}</textarea></div>
                            <div class="col-md-4 form-group"><label>Assignment routing</label><select class="form-control" name="internal_assignment_mode"><option value="config" @selected($config->internal_assignment_mode === 'config')>Config recipients</option><option value="assigned" @selected($config->internal_assignment_mode === 'assigned')>Assigned owner</option><option value="config_and_assigned" @selected($config->internal_assignment_mode === 'config_and_assigned')>Both</option></select></div>
                        </div>

                        <div class="email-section-label mt-3">Follow-up</div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Reminder after minutes</label><input class="form-control" type="number" min="0" name="reminder_after_minutes" value="{{ $config->reminder_after_minutes }}"></div>
                            <div class="col-md-6 form-group"><label class="d-flex align-items-center"><input class="mr-2" type="checkbox" name="welcome_enabled" value="1" @checked($config->welcome_enabled)> Enable welcome email</label><select class="form-control" name="welcome_template_id"><option value="">Select template</option>@foreach($templates as $t)<option value="{{ $t->id }}" @selected($config->welcome_template_id === $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                        </div>
                        <div class="email-form-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save {{ $config->enquiry_type }} config</button></div>
                    </div>
                </form>
            @endforeach
        </div>
    </section>
</div>
@endsection
