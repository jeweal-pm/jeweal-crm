@extends('layouts.main')

@section('head')
@section('title', 'CRM Enquiries')
@include('administrator.enquiry.partials.crm-workspace-styles')
@endsection

@section('content')
@php
    $bulkFormId = 'enquiry-bulk-actions';
    $bulkEnabled = auth()->user()->hasCrmPermission('enquiry.bulk_delete')
        || auth()->user()->hasCrmPermission('enquiry.restore')
        || auth()->user()->hasCrmPermission('enquiry.update_status')
        || $assignableUsers->isNotEmpty();
@endphp
<section class="content crm-page">
    <div class="container-fluid">
        <div class="crm-topbar">
            <div class="crm-title">
                <h2>Enquiries</h2>
                <div class="crm-subtitle">Sales pipeline, assignee workflow, and lead follow-up in one working view.</div>
            </div>
            <div class="crm-switcher">
                <a class="btn btn-primary btn-sm" href="{{ route('enquiry.index') }}">
                    <i class="fas fa-list"></i> Enquiries
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('gisEnquiry') }}">
                    <i class="fas fa-map-marker-alt"></i> GIS
                </a>
            </div>
        </div>

        <div class="crm-metrics">
            <div class="crm-metric">
                <div class="crm-metric-label">Inbox Leads</div>
                <div class="crm-metric-value">{{ number_format($summary['total']) }}</div>
            </div>
            <div class="crm-metric">
                <div class="crm-metric-label">Unassigned</div>
                <div class="crm-metric-value">{{ number_format($summary['unassigned']) }}</div>
            </div>
            <div class="crm-metric">
                <div class="crm-metric-label">Customers</div>
                <div class="crm-metric-value">{{ number_format($summary['customers']) }}</div>
            </div>
            <div class="crm-metric">
                <div class="crm-metric-label">Suspected Spam</div>
                <div class="crm-metric-value">{{ number_format($summary['suspected_spam']) }}</div>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @php
            $activeSpam = $filters['spam'] ?? 'inbox';
        @endphp
        <div class="crm-tabs">
            <a class="crm-tab {{ $activeSpam === 'inbox' ? 'crm-tab-active' : '' }}" href="{{ route('enquiry.index', array_merge($filters, ['spam' => 'inbox', 'page' => null])) }}">
                Inbox <span class="crm-tab-count">{{ number_format($summary['total']) }}</span>
            </a>
            <a class="crm-tab {{ $activeSpam === 'suspected' ? 'crm-tab-active' : '' }}" href="{{ route('enquiry.index', array_merge($filters, ['spam' => 'suspected', 'page' => null])) }}">
                Suspected Spam <span class="crm-tab-count">{{ number_format($summary['suspected_spam']) }}</span>
            </a>
            <a class="crm-tab {{ $activeSpam === 'confirmed' ? 'crm-tab-active' : '' }}" href="{{ route('enquiry.index', array_merge($filters, ['spam' => 'confirmed', 'page' => null])) }}">
                Confirmed Spam <span class="crm-tab-count">{{ number_format($summary['confirmed_spam']) }}</span>
            </a>
            <a class="crm-tab {{ $activeSpam === 'not_spam' ? 'crm-tab-active' : '' }}" href="{{ route('enquiry.index', array_merge($filters, ['spam' => 'not_spam', 'page' => null])) }}">
                Marked Valid
            </a>
        </div>

        <form method="get" action="{{ route('enquiry.index') }}" class="crm-toolbar">
            <input type="hidden" name="spam" value="{{ $activeSpam }}">
            <div class="form-row">
                <div class="form-group col-lg-2 col-md-4">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All statuses</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Assignee</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">All assignees</option>
                        @foreach($teamUsers as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Deleted</label>
                    <select name="trashed" class="form-control">
                        <option value="">Active only</option>
                        <option value="with" @selected(($filters['trashed'] ?? '') === 'with')>Include deleted</option>
                        <option value="only" @selected(($filters['trashed'] ?? '') === 'only')>Deleted only</option>
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Date from</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Date to</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Sort</label>
                    <select name="sort" class="form-control">
                        <option value="-created_at" @selected(($filters['sort'] ?? '-created_at') === '-created_at')>Newest first</option>
                        <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Oldest first</option>
                        <option value="-assigned_at" @selected(($filters['sort'] ?? '') === '-assigned_at')>Recently assigned</option>
                        <option value="status" @selected(($filters['sort'] ?? '') === 'status')>Status A-Z</option>
                    </select>
                </div>
                <div class="form-group col-lg-10 col-md-8">
                    <label>Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, email, company">
                </div>
                <div class="form-group col-lg-2 col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="crm-panel">
            <div class="crm-panel-head">
                <h3 class="crm-panel-title">Pipeline Records</h3>
                <div class="crm-result-count">{{ number_format($data->total()) }} results</div>
            </div>

            @if($bulkEnabled)
                @include('administrator.enquiry.partials.bulk-actions', [
                    'bulkActionRoute' => route('enquiries.bulk-action'),
                ])
            @endif

            <div class="table-responsive">
                <table class="table table-hover crm-table">
                    <colgroup>
                        @if($bulkEnabled)<col style="width: 4%">@endif
                        <col style="width: 16%">
                        <col style="width: 13%">
                        <col style="width: 15%">
                        <col style="width: 11%">
                        <col style="width: 11%">
                        <col style="width: 9%">
                        <col style="width: 21%">
                    </colgroup>
                    <thead>
                    <tr>
                        @if($bulkEnabled)
                            <th class="crm-select-cell">
                                <input class="crm-select-checkbox" type="checkbox" data-bulk-select-all="{{ $bulkFormId }}" aria-label="Select all enquiries on this page">
                            </th>
                        @endif
                        <th>Lead</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($data as $row)
                        @php
                            $assigneeName = $row->assignedTo?->name;
                            $assigneeInitial = $assigneeName ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($assigneeName, 0, 1)) : '-';
                        @endphp
                        <tr>
                            @if($bulkEnabled)
                                <td class="crm-select-cell">
                                    <input class="crm-select-checkbox" type="checkbox" name="ids[]" value="{{ $row->id }}" form="{{ $bulkFormId }}" data-bulk-item aria-label="Select enquiry {{ $row->name }}">
                                </td>
                            @endif
                            <td>
                                <div class="crm-primary">{{ $row->name }}</div>
                                <div class="crm-muted">{{ implode(', ', (array) $row->interest_in) }}</div>
                                <div class="crm-muted">{{ \Illuminate\Support\Str::limit($row->description, 72) }}</div>
                            </td>
                            <td>
                                <div>{{ $row->company }}</div>
                                <div class="crm-muted">{{ implode(', ', (array) $row->business_type) }}</div>
                                @if($row->company_website)
                                    <a class="crm-link" href="{{ $row->company_website }}" target="_blank" rel="noopener">Website</a>
                                @endif
                            </td>
                            <td>
                                <div class="crm-email-line">
                                    <a class="crm-link" href="mailto:{{ $row->email }}">{{ $row->email }}</a>
                                    @if(! $row->trashed())
                                        <a class="crm-reply-link" href="{{ route('enquiries.reply', $row->id) }}" title="Reply email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                    @endif
                                </div>
                                <a href="tel:{{ $row->phone }}">{{ $row->phone }}</a>
                            </td>
                            <td>
                                <span class="crm-status crm-status-{{ $row->trashed() ? 'deleted' : $row->status }}">
                                    {{ $row->trashed() ? 'Deleted' : ($statusOptions[$row->status] ?? $row->status) }}
                                </span>
                                @if($row->spam_status !== 'clean')
                                    <div>
                                        <span class="crm-spam-chip crm-spam-{{ $row->spam_status }}">
                                            Spam {{ $row->spam_score }}/100
                                        </span>
                                    </div>
                                    @if($row->spam_reasons)
                                        <div class="crm-reasons">{{ implode(', ', $row->spam_reasons) }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="crm-owner">
                                    <span class="crm-avatar">{{ $assigneeInitial }}</span>
                                    <span>{{ $assigneeName ?? 'Unassigned' }}</span>
                                </div>
                            </td>
                            <td>
                                <div>{{ optional($row->created_at)->format('d/m/Y') }}</div>
                                <div class="crm-muted">{{ optional($row->created_at)->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="crm-actions">
                                    @if(! $row->trashed())
                                        @can('assign', $row)
                                            @if($assignableUsers->isNotEmpty())
                                                <form method="post" action="{{ route('enquiries.assign', $row->id) }}" class="crm-action-form">
                                                    @csrf
                                                    <select name="user_id" class="form-control form-control-sm" required>
                                                        <option value="">Assign to</option>
                                                        @foreach($assignableUsers as $user)
                                                            <option value="{{ $user->id }}" @selected((int) $row->assigned_to === (int) $user->id)>{{ $user->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-sm btn-outline-primary crm-icon-btn" type="submit" title="Assign">
                                                        <i class="fas fa-user-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan

                                        @can('updateStatus', $row)
                                            <form method="post" action="{{ route('enquiries.status', $row->id) }}" class="crm-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-control form-control-sm">
                                                    @foreach($statusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($row->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm btn-outline-success crm-icon-btn" type="submit" title="Update status">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endcan

                                        @can('delete', $row)
                                            <form method="post" action="{{ route('enquiries.destroy', $row->id) }}" class="crm-action-form" data-confirm="Move this enquiry to deleted records?" data-confirm-title="Delete enquiry" data-confirm-tone="danger" data-confirm-button="Delete">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger crm-icon-btn" type="submit" title="Soft delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan

                                        @can('restore', $row)
                                            @if($row->spam_status === 'suspected')
                                                <form method="post" action="{{ route('enquiries.spam-status', $row->id) }}" class="crm-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="spam_status" value="not_spam">
                                                    <button class="btn btn-sm btn-outline-success crm-icon-btn" type="submit" title="Mark valid">
                                                        <i class="fas fa-inbox"></i>
                                                    </button>
                                                </form>
                                                <form method="post" action="{{ route('enquiries.spam-status', $row->id) }}" class="crm-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="spam_status" value="confirmed">
                                                    <button class="btn btn-sm btn-outline-secondary crm-icon-btn" type="submit" title="Confirm spam">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @elseif(in_array($row->spam_status, ['clean', 'not_spam'], true))
                                                <form method="post" action="{{ route('enquiries.spam-status', $row->id) }}" class="crm-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="spam_status" value="suspected">
                                                    <button class="btn btn-sm btn-outline-warning crm-icon-btn" type="submit" title="Move to suspected spam">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    @else
                                        @can('restore', $row)
                                            <form method="post" action="{{ route('enquiries.restore', $row->id) }}" class="crm-action-form">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary crm-icon-btn" type="submit" title="Restore">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $bulkEnabled ? 8 : 7 }}" class="crm-empty">No enquiries found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="crm-pagination">
                {{ $data->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
