@extends('layouts.UserLayout')

@section('title', 'Template Preview')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar">
                <div class="crm-title"><h2>{{ $template->name }}</h2><div class="crm-subtitle">Preview the rendered message and send a controlled test.</div></div>
                <div class="crm-topbar-actions"><a class="btn btn-light" href="{{ route('email.templates.edit', $template->id) }}"><i class="fas fa-edit"></i> Edit template</a><a class="btn btn-outline-secondary" href="{{ route('email.templates') }}"><i class="fas fa-arrow-left"></i> Templates</a></div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-3">
                    <section class="crm-panel h-100">
                        <div class="email-panel-head"><div><h3 class="crm-panel-title">Rendered preview</h3><div class="email-panel-copy">Demo values are used for the preview only.</div></div><span class="email-status email-status-{{ str_replace('_', '-', $template->status) }}">{{ ucfirst($template->status) }}</span></div>
                        <div class="email-panel-body"><div class="email-preview"><div class="email-preview-subject">{{ $rendered['subject'] }}</div><div>{!! $rendered['html_content'] !!}</div></div></div>
                    </section>
                </div>
                <div class="col-lg-4 mb-3">
                    <section class="crm-panel">
                        <div class="email-panel-head"><div><h3 class="crm-panel-title">Send test</h3><div class="email-panel-copy">Send only to a controlled recipient.</div></div></div>
                        <div class="email-panel-body">
                            <form method="post" action="{{ route('email.templates.test-send', $template->id) }}">
                                @csrf
                                <div class="form-group"><label>Test recipient</label><input class="form-control" type="email" name="email" required placeholder="you@example.com"></div>
                                <div class="form-group"><label>Sender type</label><select class="form-control" name="enquiry_type"><option value="general">General</option><option value="gis">GIS</option><option value="gms">GMS</option></select></div>
                                <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-paper-plane"></i> Send test email</button>
                            </form>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
