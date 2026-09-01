@extends('layouts.main')

@section('title', 'IP Security')

@section('head')
    @include('administrator.whatsapp.partials.styles')
@endsection

@section('content')
<section class="communication-page">
    <header class="page-heading">
        <div><h1>IP Security</h1><p>Global blacklist, module limits and submission decisions in one audit view.</p></div>
        @if(auth()->user()->hasCrmPermission('whatsapp.view'))
            <div class="page-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('whatsapp.messages.index') }}"><i class="fab fa-whatsapp"></i> WhatsApp Delivery</a></div>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="comm-panel">
        <div class="comm-panel-head"><div><h2 class="comm-panel-title">Rate limit policies</h2><div class="comm-panel-copy">Each module counts submissions independently for the same IP address.</div></div></div>
        @foreach($configs as $config)
            <form class="comm-rate-row" method="post" action="{{ route('security.ip.rate-limits.update', $config->id) }}">
                @csrf @method('put')
                <div class="comm-rate-name"><strong>{{ $config->label }}</strong><span>{{ $config->module }}</span></div>
                <div><label>Max attempts</label><input class="form-control" type="number" min="1" name="max_attempts" value="{{ $config->max_attempts }}" required></div>
                <div><label>Window (seconds)</label><input class="form-control" type="number" min="60" name="window_seconds" value="{{ $config->window_seconds }}" required></div>
                <div><label>Cooldown (seconds)</label><input class="form-control" type="number" min="0" name="cooldown_seconds" value="{{ $config->cooldown_seconds }}" required></div>
                <label class="comm-checkbox"><input type="checkbox" name="is_enabled" value="1" {{ $config->is_enabled ? 'checked' : '' }}> Enabled</label>
                @if(auth()->user()->hasCrmPermission('security.ip.manage'))<button class="btn btn-outline-primary" type="submit" title="Save {{ $config->label }} limit"><i class="fas fa-save"></i></button>@endif
            </form>
        @endforeach
    </section>

    <div class="comm-grid">
        <section class="comm-panel">
            <div class="comm-panel-head"><div><h2 class="comm-panel-title">Global blacklist</h2><div class="comm-panel-copy">Blocked addresses are rejected across every protected module.</div></div><span class="comm-stat">{{ number_format($blacklists->total()) }} records</span></div>
            <div class="comm-table-wrap">
                <table class="table table-hover comm-table">
                    <thead><tr><th>IP address</th><th>Reason</th><th>Expires</th><th>Added by</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($blacklists as $blocked)
                        <tr>
                            <td><strong>{{ $blocked->ip_address }}</strong><div class="comm-meta">{{ $blocked->is_active ? 'Active' : 'Inactive' }}</div></td>
                            <td>{{ $blocked->reason ?: '-' }}</td>
                            <td>{{ optional($blocked->blocked_until)->format('d M Y H:i') ?: 'Permanent' }}</td>
                            <td>{{ optional($blocked->creator)->name ?: 'System' }}</td>
                            <td>@if(auth()->user()->hasCrmPermission('security.ip.manage'))<form method="post" action="{{ route('security.ip.blacklist.destroy', $blocked->id) }}" data-confirm="Remove this IP from the blacklist?" data-confirm-title="Remove blacklist entry" data-confirm-tone="danger" data-confirm-button="Remove">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit" title="Remove blacklist entry"><i class="fas fa-trash"></i></button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="comm-empty"><i class="fas fa-shield-alt"></i>No blacklisted IP addresses.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $blacklists->links() }}
        </section>

        @if(auth()->user()->hasCrmPermission('security.ip.manage'))
            <form class="comm-panel" method="post" action="{{ route('security.ip.blacklist.store') }}">
                @csrf
                <div class="comm-panel-head"><div><h2 class="comm-panel-title">Block IP address</h2><div class="comm-panel-copy">Leave expiry empty for a permanent block.</div></div></div>
                <div class="comm-panel-body">
                    <div class="form-group"><label for="ip_address">IP address</label><input id="ip_address" class="form-control" name="ip_address" value="{{ old('ip_address') }}" placeholder="203.0.113.10" required></div>
                    <div class="form-group"><label for="reason">Reason</label><textarea id="reason" class="form-control" name="reason" placeholder="Spam or abusive submission pattern">{{ old('reason') }}</textarea></div>
                    <div class="form-group"><label for="blocked_until">Block until</label><input id="blocked_until" class="form-control" type="datetime-local" name="blocked_until" value="{{ old('blocked_until') }}"></div>
                    <button class="btn btn-danger btn-block" type="submit"><i class="fas fa-ban"></i> Add to blacklist</button>
                </div>
            </form>
        @endif
    </div>

    <section class="comm-panel">
        <div class="comm-panel-head"><div><h2 class="comm-panel-title">Rate limit log</h2><div class="comm-panel-copy">Allowed and rejected decisions recorded before form processing.</div></div><span class="comm-stat">{{ number_format($logs->total()) }} events</span></div>
        <form class="comm-toolbar" method="get" action="{{ route('security.ip.index') }}">
            <div class="form-group"><label for="filter-ip">IP address</label><input id="filter-ip" class="form-control" name="ip" value="{{ $filters['ip'] ?? '' }}" placeholder="Search IP"></div>
            <div class="form-group"><label for="filter-module">Module</label><select id="filter-module" class="form-control" name="module"><option value="">All modules</option>@foreach($modules as $value => $label)<option value="{{ $value }}" {{ ($filters['module'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label for="filter-decision">Decision</label><select id="filter-decision" class="form-control" name="decision"><option value="">All decisions</option>@foreach(['allowed', 'blacklisted', 'rate_limited', 'cooldown'] as $value)<option value="{{ $value }}" {{ ($filters['decision'] ?? '') === $value ? 'selected' : '' }}>{{ Str::headline($value) }}</option>@endforeach</select></div>
            <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
        </form>
        <div class="comm-table-wrap">
            <table class="table table-hover comm-table">
                <thead><tr><th>Time</th><th>IP address</th><th>Module</th><th>Decision</th><th>HTTP</th><th>Endpoint</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr><td>{{ $log->occurred_at->format('d M Y H:i:s') }}</td><td><strong>{{ $log->ip_address }}</strong></td><td>{{ $modules[$log->module] ?? Str::headline($log->module) }}</td><td><span class="comm-status comm-status-{{ $log->decision }}">{{ Str::headline($log->decision) }}</span></td><td>{{ $log->http_status }}</td><td>{{ $log->endpoint ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="6"><div class="comm-empty"><i class="fas fa-list-alt"></i>No rate limit events found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
</section>
@endsection
