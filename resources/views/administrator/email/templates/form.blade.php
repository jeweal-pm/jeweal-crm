@extends('layouts.UserLayout')

@section('title', $template->exists ? 'Edit Email Template' : 'New Email Template')

@section('head')
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar">
                <div class="crm-title">
                    <h2>{{ $template->exists ? 'Edit Email Template' : 'New Email Template' }}</h2>
                    <div class="crm-subtitle">Define the message, sender and supported variables in one controlled workspace.</div>
                </div>
                <a class="btn btn-light" href="{{ route('email.templates') }}"><i class="fas fa-arrow-left"></i> Back to templates</a>
            </div>

            <form method="post" action="{{ $template->exists ? route('email.templates.update', $template->id) : route('email.templates.store') }}" class="email-form-panel">
                @csrf
                @if($template->exists) @method('put') @endif
                <section class="crm-panel">
                    <div class="email-panel-head"><div><h3 class="crm-panel-title">Template identity</h3><div class="email-panel-copy">Use stable names and codes so automation rules remain readable.</div></div></div>
                    <div class="email-panel-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Name</label><input class="form-control" name="name" value="{{ old('name', $template->name) }}" required></div>
                            <div class="col-md-6 form-group"><label>Code</label><input class="form-control" name="code" value="{{ old('code', $template->code) }}" required></div>
                            <div class="col-md-4 form-group"><label>Email type</label><select class="form-control" name="email_type"><option value="transactional" @selected($template->email_type === 'transactional')>Transactional</option><option value="marketing" @selected($template->email_type === 'marketing')>Marketing</option><option value="internal" @selected($template->email_type === 'internal')>Internal notification</option></select></div>
                            <div class="col-md-4 form-group"><label>Category</label><input class="form-control" name="category" value="{{ old('category', $template->category) }}" required></div>
                            <div class="col-md-4 form-group"><label>Status</label><select class="form-control" name="status"><option value="draft" @selected($template->status === 'draft')>Draft</option><option value="published" @selected($template->status === 'published')>Published</option><option value="archived" @selected($template->status === 'archived')>Archived</option></select></div>
                        </div>
                    </div>
                </section>

                <section class="crm-panel">
                    <div class="email-panel-head"><div><h3 class="crm-panel-title">Message content</h3><div class="email-panel-copy">Variables are rendered when a message is sent.</div></div></div>
                    <div class="email-panel-body">
                        <div class="row">
                            <div class="col-md-8 form-group"><label>Subject</label><input class="form-control" name="subject" value="{{ old('subject', $template->subject) }}" required></div>
                            <div class="col-md-4 form-group"><label>Preview text</label><input class="form-control" name="preview_text" value="{{ old('preview_text', $template->preview_text) }}"></div>
                            <div class="col-12 form-group"><label>HTML content</label><textarea id="html_content" class="form-control" name="html_content" rows="16" required>{{ old('html_content', $template->html_content) }}</textarea></div>
                            <div class="col-12 form-group"><label>Plain-text content</label><textarea class="form-control" name="plain_text_content" rows="6">{{ old('plain_text_content', $template->plain_text_content) }}</textarea></div>
                        </div>
                    </div>
                </section>

                <section class="crm-panel">
                    <div class="email-panel-head"><div><h3 class="crm-panel-title">Sender and variables</h3><div class="email-panel-copy">Leave sender email blank to use the enquiry-type sender configuration.</div></div></div>
                    <div class="email-panel-body">
                        <div class="row">
                            <div class="col-md-4 form-group"><label>Sender name</label><input class="form-control" name="sender_name" value="{{ old('sender_name', $template->sender_name) }}"></div>
                            <div class="col-md-4 form-group"><label>Sender email</label><input class="form-control" name="sender_email" value="{{ old('sender_email', $template->sender_email) }}"></div>
                            <div class="col-md-4 form-group"><label>Reply-to email</label><input class="form-control" name="reply_to_email" value="{{ old('reply_to_email', $template->reply_to_email) }}"></div>
                            <div class="col-12 form-group"><label>Supported variables</label><div class="row"><div class="col-md-4"><input class="form-control" name="variables[]" value="first_name"></div><div class="col-md-4"><input class="form-control" name="variables[]" value="email"></div><div class="col-md-4"><input class="form-control" name="variables[]" value="enquiry_number"></div></div><div class="email-helper mt-2">Use variables in double braces, for example <code>{{ '{{first_name}}' }}</code>.</div></div>
                        </div>
                        <div class="email-form-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save template</button><a class="btn btn-light" href="{{ route('email.templates') }}">Cancel</a></div>
                    </div>
                </section>
            </form>
        </div>
    </section>
</div>
<script>tinymce.init({selector:'#html_content',height:480,menubar:false,plugins:'link lists table code',toolbar:'undo redo | bold italic underline | bullist numlist | link table | code'});</script>
@endsection
