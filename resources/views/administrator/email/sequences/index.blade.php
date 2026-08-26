@extends('layouts.UserLayout')

@section('title', 'Email Sequences')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>Email Sequences</h2><div class="crm-subtitle">Manage reusable follow-up journeys, their sending steps and lifecycle.</div></div><div class="crm-topbar-actions"><a class="btn btn-outline-secondary" href="{{ route('email.enrollments') }}"><i class="fas fa-user-plus"></i> Enrollments</a><a class="btn btn-primary" href="{{ route('email.sequences.create') }}"><i class="fas fa-plus"></i> New sequence</a></div></div>
            <section class="crm-panel">
                <div class="email-panel-head"><div><h3 class="crm-panel-title">Sequence library</h3><div class="email-panel-copy">Open a sequence to manage its settings and individual email steps.</div></div><div class="crm-result-count">{{ number_format($sequences->total()) }} sequences</div></div>
                <div class="email-table-wrap"><table class="table table-hover crm-table"><thead><tr><th>Name</th><th>Code</th><th>Steps</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
                @forelse($sequences as $sequence)
                    <tr><td><div class="crm-primary">{{ $sequence->name }}</div><div class="crm-muted">{{ \Illuminate\Support\Str::limit($sequence->description, 80) ?: 'No description' }}</div></td><td><span class="email-code">{{ $sequence->code }}</span></td><td>{{ $sequence->steps_count }}</td><td><span class="email-status email-status-{{ str_replace('_', '-', $sequence->status) }}">{{ ucfirst($sequence->status) }}</span></td><td>{{ optional($sequence->updated_at)->format('d/m/Y H:i') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('email.sequences.show', $sequence->id) }}" title="Manage sequence"><i class="fas fa-edit"></i></a></td></tr>
                @empty
                    <tr><td colspan="6" class="crm-empty"><i class="fas fa-stream email-empty-icon"></i>No sequences found.</td></tr>
                @endforelse
                </tbody></table></div>
                <div class="crm-pagination">{{ $sequences->links() }}</div>
            </section>
        </div>
    </section>
</div>
@endsection
