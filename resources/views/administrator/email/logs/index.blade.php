@extends('layouts.UserLayout')

@section('title', 'Email Delivery Logs')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>Email Delivery Logs</h2><div class="crm-subtitle">Provider status and engagement events per message.</div></div><a class="btn btn-outline-secondary" href="{{ route('email.dashboard') }}"><i class="fas fa-chart-line"></i> Email overview</a></div>
            <section class="crm-panel">
                <div class="email-panel-head"><div><h3 class="crm-panel-title">Message activity</h3><div class="email-panel-copy">Use status and timestamps to trace the delivery lifecycle.</div></div><div class="crm-result-count">{{ number_format($messages->total()) }} messages</div></div>
                <div class="email-table-wrap"><table class="table table-hover crm-table"><thead><tr><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Queued</th><th>Sent</th></tr></thead><tbody>
                @forelse($messages as $message)
                    <tr><td><div class="crm-primary">{{ $message->to_email }}</div><div class="crm-muted">{{ $message->subscriber?->fullName() }}</div></td><td>{{ $message->subject }}</td><td>{{ ucfirst($message->message_type) }}</td><td><span class="email-status email-status-{{ str_replace('_', '-', $message->status) }}">{{ ucfirst(str_replace('_', ' ', $message->status)) }}</span></td><td>{{ optional($message->queued_at)->format('d/m/Y H:i') }}</td><td>{{ optional($message->sent_at)->format('d/m/Y H:i') ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="crm-empty"><i class="fas fa-list email-empty-icon"></i>No messages found.</td></tr>
                @endforelse
                </tbody></table></div><div class="crm-pagination">{{ $messages->links() }}</div>
            </section>
        </div>
    </section>
</div>
@endsection
