@extends('layouts.UserLayout')

@section('title', 'Import Email Sequence')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar">
                <div class="crm-title">
                    <h2>Import Custom Email Sequence</h2>
                    <div class="crm-subtitle">Import a complete journey with custom subject, preview text, HTML, plain text and timing for every step.</div>
                </div>
                <a class="btn btn-light" href="{{ route('email.sequences') }}"><i class="fas fa-arrow-left"></i> Back to sequences</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <section class="crm-panel">
                        <div class="email-panel-head">
                            <div>
                                <h3 class="crm-panel-title">Sequence JSON</h3>
                                <div class="email-panel-copy">Upload a JSON file or paste the payload below. Imported sequences are always created as Draft.</div>
                            </div>
                        </div>
                        <form method="post" action="{{ route('email.sequences.import.store') }}" enctype="multipart/form-data" class="email-panel-body">
                            @csrf
                            <div class="form-group">
                                <label for="json_file">JSON file</label>
                                <input id="json_file" class="form-control-file" type="file" name="json_file" accept=".json,application/json,text/plain">
                                <div class="email-helper">Maximum 5 MB. The file takes precedence over pasted content.</div>
                            </div>
                            <div class="form-group">
                                <label for="payload">Or paste JSON payload</label>
                                <textarea id="payload" class="form-control" name="payload" rows="22" spellcheck="false" placeholder="{\n  &quot;sequence&quot;: { ... },\n  &quot;steps&quot;: [ ... ]\n}">{{ old('payload') }}</textarea>
                            </div>
                            <div class="email-form-actions">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-file-import"></i> Import sequence</button>
                                <a class="btn btn-light" href="{{ route('email.sequences') }}">Cancel</a>
                            </div>
                        </form>
                    </section>
                </div>
                <div class="col-lg-4">
                    <section class="crm-panel">
                        <div class="email-panel-head"><div><h3 class="crm-panel-title">Import contract</h3><div class="email-panel-copy">Use this structure for future template packages.</div></div></div>
                        <div class="email-panel-body">
                            <ul class="email-helper pl-3 mb-0">
                                <li>Use a unique sequence code.</li>
                                <li>Include 1 to 14 ordered steps.</li>
                                <li>Each step requires subject and html_content.</li>
                                <li>Supported variables are rendered safely.</li>
                                <li>Existing Email Templates are not required.</li>
                                <li>Review the imported draft before publishing.</li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
