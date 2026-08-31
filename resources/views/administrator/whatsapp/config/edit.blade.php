@extends('layouts.main')

@section('title', 'Twilio Configuration')

@section('head')
    @include('administrator.whatsapp.partials.styles')
@endsection

@section('content')
<section class="communication-page">
    <header class="page-heading">
        <div><h1>Twilio Configuration</h1><p>Credentials and delivery controls for outbound WhatsApp messages.</p></div>
        <div class="page-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('whatsapp.messages.index') }}"><i class="fas fa-arrow-left"></i> Messages</a></div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Configuration was not saved.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('whatsapp.config.update') }}" class="comm-panel">
        @csrf @method('put')
        <div class="comm-panel-head"><div><h2 class="comm-panel-title">Provider credentials</h2><div class="comm-panel-copy">API secrets are encrypted before they are stored.</div></div><span class="comm-status {{ $config->isComplete() ? 'comm-status-sent' : 'comm-status-failed' }}">{{ $config->isComplete() ? 'Configured' : 'Incomplete' }}</span></div>
        <div class="comm-panel-body comm-form-grid">
            <div class="full"><label class="comm-checkbox"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $config->is_enabled) ? 'checked' : '' }}> Enable Twilio WhatsApp delivery</label></div>
            <div><label for="account_sid">Account SID</label><input id="account_sid" class="form-control" name="account_sid" autocomplete="off" placeholder="{{ $config->maskedValue('account_sid') ?: 'AC...' }}"><div class="comm-help">Leave blank to retain the saved value.</div></div>
            <div><label for="api_key_sid">API Key SID</label><input id="api_key_sid" class="form-control" name="api_key_sid" autocomplete="off" placeholder="{{ $config->maskedValue('api_key_sid') ?: 'SK...' }}"><div class="comm-help">Use a Twilio Standard or Restricted API key.</div></div>
            <div><label for="api_key_secret">API Key Secret</label><input id="api_key_secret" class="form-control" type="password" name="api_key_secret" autocomplete="new-password" placeholder="{{ $config->api_key_secret ? 'Saved secret' : 'Enter API key secret' }}"><div class="comm-help">The existing secret is never shown.</div></div>
            <div><label for="whatsapp_from">WhatsApp sender</label><input id="whatsapp_from" class="form-control" name="whatsapp_from" value="{{ old('whatsapp_from', $config->whatsapp_from) }}" placeholder="+14155238886" required></div>
        </div>
        <div class="comm-panel-head"><div><h2 class="comm-panel-title">Delivery controls</h2><div class="comm-panel-copy">Daily quota and retry timing apply to all WhatsApp form submissions.</div></div></div>
        <div class="comm-panel-body comm-form-grid">
            <div><label for="daily_limit">Daily send limit</label><input id="daily_limit" class="form-control" type="number" min="1" name="daily_limit" value="{{ old('daily_limit', $config->daily_limit) }}" required></div>
            <div><label for="max_retry_attempts">Maximum attempts</label><input id="max_retry_attempts" class="form-control" type="number" min="1" max="10" name="max_retry_attempts" value="{{ old('max_retry_attempts', $config->max_retry_attempts) }}" required></div>
            @php($retryDelays = old('retry_delays_seconds', $config->retry_delays_seconds ?: [60, 300, 900]))
            <div><label for="retry_delays_seconds">Retry delays (seconds)</label><input id="retry_delays_seconds" class="form-control" name="retry_delays_seconds" value="{{ is_array($retryDelays) ? implode(',', $retryDelays) : $retryDelays }}" required><div class="comm-help">Comma-separated delays, one value for each retry stage.</div></div>
            <div><label for="timezone">Quota timezone</label><input id="timezone" class="form-control" name="timezone" value="{{ old('timezone', $config->timezone) }}" required></div>
            <div class="full"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save configuration</button></div>
        </div>
    </form>
</section>
@endsection
