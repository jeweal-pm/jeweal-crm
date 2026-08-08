@extends('layouts.UserLayout')

@section('title', 'Email Templates')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar">
                <div class="crm-title">
                    <h2>Email Templates</h2>
                    <div class="crm-subtitle">Versioned content for transactional, internal and marketing messages.</div>
                </div>
                <a class="btn btn-primary" href="{{ route('email.templates.create') }}"><i class="fas fa-plus"></i> New template</a>
            </div>

            <section class="crm-panel">
                <div class="email-panel-head">
                    <div>
                        <h3 class="crm-panel-title">Template library</h3>
                        <div class="email-panel-copy">Publish only reviewed content. Drafts can be previewed and tested before release.</div>
                    </div>
                    <div class="crm-result-count">{{ number_format($templates->total()) }} templates</div>
                </div>
                <div class="email-table-wrap">
                    <table class="table table-hover crm-table">
                        <thead>
                        <tr><th>Name</th><th>Code</th><th>Type</th><th>Status</th><th>Version</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td><div class="crm-primary">{{ $template->name }}</div><div class="crm-muted">{{ $template->category }}</div></td>
                                <td><span class="email-code">{{ $template->code }}</span></td>
                                <td>{{ ucfirst($template->email_type) }}</td>
                                <td><span class="email-status email-status-{{ str_replace('_', '-', $template->status) }}">{{ ucfirst($template->status) }}</span></td>
                                <td>v{{ $template->version }}</td>
                                <td>
                                    <div class="email-inline-actions">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('email.templates.edit', $template->id) }}" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('email.templates.preview', $template->id) }}" title="Preview"><i class="fas fa-eye"></i></a>
                                        <form class="d-inline" method="post" action="{{ route('email.templates.duplicate', $template->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary" title="Duplicate"><i class="fas fa-copy"></i></button></form>
                                        <form class="d-inline" method="post" action="{{ route('email.templates.publish', $template->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary" title="Publish or unpublish"><i class="fas fa-toggle-on"></i></button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="crm-empty"><i class="fas fa-file-alt email-empty-icon"></i>No templates found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="crm-pagination">{{ $templates->links() }}</div>
            </section>
        </div>
    </section>
</div>
@endsection
