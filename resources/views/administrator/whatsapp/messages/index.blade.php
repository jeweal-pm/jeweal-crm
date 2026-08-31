@extends('layouts.main')

@section('title', 'WhatsApp Delivery')

@section('head')
    @include('administrator.whatsapp.partials.styles')
@endsection

@section('content')
<section class="communication-page">
    <header class="page-heading">
        <div>
            <h1>WhatsApp Delivery</h1>
            <p>Monitor queued, completed and failed outbound messages.</p>
        </div>
        <div class="page-actions">
            @if(auth()->user()->hasCrmPermission('security.ip.view'))
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('security.ip.index') }}"><i class="fas fa-shield-alt"></i> IP Security</a>
            @endif
            @if(auth()->user()->hasCrmPermission('whatsapp.config.manage'))
                <a class="btn btn-outline-primary btn-sm" href="{{ route('whatsapp.config.edit') }}"><i class="fas fa-cog"></i> Twilio Config</a>
            @endif
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <nav class="comm-tabs" aria-label="Message status">
        @foreach(['waiting' => 'Waiting', 'sent' => 'Complete', 'failed' => 'Failed', 'all' => 'All'] as $value => $label)
            <a class="comm-tab {{ $activeStatus === $value ? 'active' : '' }}" href="{{ route('whatsapp.messages.index', ['status' => $value]) }}">
                {{ $label }} <span class="comm-count">{{ $value === 'all' ? $counts->sum() : ($counts[$value] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    <section class="comm-panel">
        <form class="comm-toolbar" method="get" action="{{ route('whatsapp.messages.index') }}">
            <input type="hidden" name="status" value="{{ $activeStatus }}">
            <div class="form-group">
                <label for="message-search">Search</label>
                <input id="message-search" class="form-control" name="search" value="{{ $search }}" placeholder="Phone number, reference or message">
            </div>
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
            @if($search)<a class="btn btn-outline-secondary" href="{{ route('whatsapp.messages.index', ['status' => $activeStatus]) }}">Clear</a>@endif
        </form>

        <div class="comm-table-wrap">
            <table class="table table-hover comm-table">
                <thead><tr><th>Recipient</th><th>Message</th><th>Status</th><th>Attempts</th><th>Next action</th><th>Provider</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td><strong>{{ $message->recipient_normalized }}</strong><div class="comm-meta">{{ $message->source_reference ?: 'Public form' }}</div></td>
                        <td><div class="comm-message-preview">{{ Str::limit($message->body, 130) }}</div></td>
                        <td><span class="comm-status comm-status-{{ $message->status }}">{{ ucfirst($message->status) }}</span><div class="comm-meta">{{ $message->wait_reason ? Str::headline($message->wait_reason) : '' }}</div></td>
                        <td>{{ $message->attempts }} / {{ $message->max_attempts }}</td>
                        <td>{{ optional($message->next_attempt_at)->format('d M Y H:i') ?: '-' }}</td>
                        <td>{{ $message->provider_status ?: '-' }}<div class="comm-meta">{{ $message->provider_error_code ?: $message->provider_message_sid }}</div></td>
                        <td>{{ $message->created_at->format('d M Y') }}<div class="comm-meta">{{ $message->created_at->format('H:i') }}</div></td>
                        <td>
                            <div class="d-flex" style="gap:6px">
                                @if(auth()->user()->hasCrmPermission('whatsapp.message.manage') && $message->status !== 'sent')
                                    <form method="post" action="{{ route('whatsapp.messages.retry', $message->id) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit" title="Queue retry"><i class="fas fa-redo"></i></button></form>
                                @endif
                                @if(auth()->user()->hasCrmPermission('whatsapp.message.manage'))
                                    <form method="post" action="{{ route('whatsapp.messages.destroy', $message->id) }}" onsubmit="return confirm('Delete this record and allow the number to be submitted again?');">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit" title="Delete record"><i class="fas fa-trash"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="comm-empty"><i class="fas fa-comment-slash"></i>No WhatsApp messages found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $messages->links() }}
    </section>
</section>
@endsection
