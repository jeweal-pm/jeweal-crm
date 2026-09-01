@extends('layouts.UserLayout')

@section('title', 'Sequence Enrollments')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>Sequence Enrollments</h2><div class="crm-subtitle">Enroll a subscriber manually or let an approved campaign enroll its audience.</div></div><a class="btn btn-outline-secondary" href="{{ route('email.sequences') }}"><i class="fas fa-stream"></i> Sequences</a></div>
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <section class="crm-panel h-100"><div class="email-panel-head"><div><h3 class="crm-panel-title">Enrollment activity</h3><div class="email-panel-copy">Monitor each subscriber's current step and next send.</div></div><div class="crm-result-count">{{ number_format($enrollments->total()) }} enrollments</div></div><div class="email-table-wrap"><table class="table table-hover crm-table"><thead><tr><th>Subscriber</th><th>Sequence</th><th>Step</th><th>Status</th><th>Next scheduled</th><th>Actions</th></tr></thead><tbody>
                    @forelse($enrollments as $enrollment)
                        <tr><td><div class="crm-primary">{{ $enrollment->subscriber?->email }}</div><div class="crm-muted">{{ $enrollment->subscriber?->fullName() }}</div></td><td>{{ $enrollment->sequence?->name }}</td><td>Step {{ $enrollment->current_step }}</td><td><span class="email-status email-status-{{ str_replace('_', '-', $enrollment->status) }}">{{ ucfirst($enrollment->status) }}</span></td><td>{{ optional($enrollment->next_scheduled_at)->format('d/m/Y H:i') ?: '-' }}</td><td><form method="post" action="{{ route('email.enrollments.destroy', $enrollment->id) }}" data-confirm="Remove this enrollment and stop its pending emails?" data-confirm-title="Remove enrollment" data-confirm-tone="danger" data-confirm-button="Remove">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit" title="Remove enrollment"><i class="fas fa-trash"></i></button></form></td></tr>
                    @empty
                        <tr><td colspan="6" class="crm-empty"><i class="fas fa-user-plus email-empty-icon"></i>No enrollments found.</td></tr>
                    @endforelse
                    </tbody></table></div><div class="crm-pagination">{{ $enrollments->links() }}</div></section>
                </div>
                <div class="col-lg-4 mb-3"><form class="crm-panel" method="post" action="{{ route('email.enrollments.store') }}">@csrf<div class="email-panel-head"><div><h3 class="crm-panel-title">Manual enrollment</h3><div class="email-panel-copy">Add one subscriber to a published sequence.</div></div></div><div class="email-panel-body"><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required placeholder="customer@example.com"></div><div class="form-group"><label>Sequence</label><select class="form-control" name="email_sequence_template_id" required><option value="">Select sequence</option>@foreach($sequences as $sequence)<option value="{{ $sequence->id }}">{{ $sequence->name }}</option>@endforeach</select></div><button class="btn btn-primary btn-block" type="submit"><i class="fas fa-user-plus"></i> Enroll subscriber</button></div></form></div>
            </div>
        </div>
    </section>
</div>
@endsection
