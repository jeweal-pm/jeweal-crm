@extends('layouts.main')

@section('head')
@section('title', 'Reply Enquiry')
@include('administrator.enquiry.partials.crm-workspace-styles')
@endsection

@section('content')
<section class="content crm-page">
    <div class="container-fluid">
        <div class="crm-topbar">
            <div class="crm-title">
                <h2>Reply to {{ $recipientName }}</h2>
                <div class="crm-subtitle">{{ $recipientEmail }} · {{ $subtitle }}</div>
            </div>
            <div class="crm-switcher">
                <a class="btn btn-outline-secondary btn-sm" href="{{ $backRoute }}">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">Please check the reply details and try again.</div>
        @endif

        <div class="crm-panel">
            <div class="crm-panel-head">
                <h3 class="crm-panel-title">Email Reply</h3>
                <span class="crm-result-count">To: {{ $recipientEmail }}</span>
            </div>
            <form method="post" action="{{ $sendRoute }}" class="crm-toolbar m-0 border-0 rounded-0">
                @csrf

                <div class="form-group">
                    <label>Subject</label>
                    <input name="subject" value="{{ old('subject', $subject) }}" class="form-control @error('subject') is-invalid @enderror" required>
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="12" required>{{ old('message', $body) }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
