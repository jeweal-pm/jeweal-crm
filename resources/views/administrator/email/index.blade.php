@extends('layouts.UserLayout')

@section('title', 'Email Management')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar">
                <div class="crm-title">
                    <h2>Email Management</h2>
                    <div class="crm-subtitle">Templates, automation, campaigns and delivery health.</div>
                </div>
                <div class="crm-topbar-actions">
                    <a class="btn btn-primary" href="{{ route('email.templates.create') }}">
                        <i class="fas fa-plus"></i> New template
                    </a>
                    <a class="btn btn-outline-secondary" href="{{ route('email.logs') }}">
                        <i class="fas fa-list"></i> View logs
                    </a>
                </div>
            </div>

            <div class="crm-metrics">
                @foreach(['templates' => 'Templates', 'subscribers' => 'Subscribers', 'queued' => 'Queued', 'sent' => 'Sent', 'opens' => 'Tracked opens', 'clicks' => 'Tracked clicks'] as $key => $label)
                    <div class="crm-metric email-kpi">
                        <div class="crm-metric-label">{{ $label }}</div>
                        <div class="crm-metric-value">{{ number_format($stats[$key]) }}</div>
                    </div>
                @endforeach
            </div>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <section class="crm-panel">
                <div class="email-panel-head">
                    <div>
                        <h3 class="crm-panel-title">Email workspace</h3>
                        <div class="email-panel-copy">Manage the building blocks and delivery operations of your CRM email program.</div>
                    </div>
                </div>
                <div class="email-panel-body">
                    <div class="email-nav-grid">
                        @foreach([['email.templates','fa-file-alt','Templates','Create and version email content'],['email.config','fa-sliders-h','Automation config','Route enquiry notifications'],['email.segments','fa-users','Segments','Build reusable audiences'],['email.campaigns','fa-paper-plane','Campaigns','Approve and send broadcasts'],['email.sequences','fa-stream','Sequences','Manage timed journeys'],['email.enrollments','fa-user-plus','Enrollments','Review sequence members'],['email.logs','fa-list','Delivery logs','Inspect provider events']] as [$route,$icon,$label,$description])
                            <a class="email-nav-item" href="{{ route($route) }}">
                                <i class="fas {{ $icon }}"></i>
                                <span><strong>{{ $label }}</strong><small class="d-block crm-muted">{{ $description }}</small></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>
@endsection
