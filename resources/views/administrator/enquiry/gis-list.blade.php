@extends('layouts.main')

@section('head')
@section('title', 'CRM GIS Enquiries')
@include('administrator.enquiry.partials.crm-workspace-styles')
@endsection

@section('content')
@php
    $bulkFormId = 'gis-enquiry-bulk-actions';
    $bulkEnabled = auth()->user()->hasCrmPermission('enquiry.bulk_delete')
        || auth()->user()->hasCrmPermission('enquiry.restore')
        || auth()->user()->hasCrmPermission('enquiry.update_status')
        || $assignableUsers->isNotEmpty();
@endphp
<section class="content crm-page">
    <div class="container-fluid">
        <div class="crm-topbar">
            <div class="crm-title">
                <h2>GIS Prospects</h2>
                <div class="crm-subtitle">One pipeline for direct GIS enquiries and eligible Fair Funnel registrations.</div>
            </div>
            <div class="crm-switcher">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('enquiry.index') }}">
                    <i class="fas fa-list"></i> Enquiries
                </a>
                <a class="btn btn-primary btn-sm" href="{{ route('gisEnquiry') }}">
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
            <a class="crm-tab {{ $activeSpam === 'inbox' ? 'crm-tab-active' : '' }}" href="{{ route('gisEnquiry', array_merge($filters, ['spam' => 'inbox', 'page' => null])) }}">
                Inbox <span class="crm-tab-count">{{ number_format($summary['total']) }}</span>
            </a>
            <a class="crm-tab {{ $activeSpam === 'suspected' ? 'crm-tab-active' : '' }}" href="{{ route('gisEnquiry', array_merge($filters, ['spam' => 'suspected', 'page' => null])) }}">
                Suspected Spam <span class="crm-tab-count">{{ number_format($summary['suspected_spam']) }}</span>
            </a>
            <a class="crm-tab {{ $activeSpam === 'confirmed' ? 'crm-tab-active' : '' }}" href="{{ route('gisEnquiry', array_merge($filters, ['spam' => 'confirmed', 'page' => null])) }}">
                Confirmed Spam <span class="crm-tab-count">{{ number_format($summary['confirmed_spam']) }}</span>
            </a>
            <a class="crm-tab {{ $activeSpam === 'not_spam' ? 'crm-tab-active' : '' }}" href="{{ route('gisEnquiry', array_merge($filters, ['spam' => 'not_spam', 'page' => null])) }}">
                Marked Valid
            </a>
        </div>

        <form method="get" action="{{ route('gisEnquiry') }}" class="crm-toolbar">
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
                    <label>Source</label>
                    <select name="record_source" class="form-control">
                        <option value="">All sources</option>
                        <option value="gis_enquiry" @selected(($filters['record_source'] ?? '') === 'gis_enquiry')>GIS-Enquiry</option>
                        <option value="fair_funnel" @selected(($filters['record_source'] ?? '') === 'fair_funnel')>fair-funnel</option>
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
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, email, company or fair code">
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
                <h3 class="crm-panel-title">Unified GIS Prospect Records</h3>
                <div class="crm-result-count">{{ number_format($data->total()) }} results</div>
            </div>

            @if($bulkEnabled)
                @include('administrator.enquiry.partials.bulk-actions', [
                    'bulkActionRoute' => route('gis-enquiries.bulk-action'),
                ])
            @endif

            <div class="table-responsive">
                <table class="table table-hover crm-table">
                    <colgroup>
                        @if($bulkEnabled)<col style="width: 4%">@endif
                        <col style="width: 12%">
                        <col style="width: 9%">
                        <col style="width: 14%">
                        <col style="width: 12%">
                        <col style="width: 14%">
                        <col style="width: 8%">
                        <col style="width: 9%">
                        <col style="width: 7%">
                        <col style="width: 15%">
                    </colgroup>
                    <thead>
                    <tr>
                        @if($bulkEnabled)
                            <th class="crm-select-cell">
                                <input class="crm-select-checkbox" type="checkbox" data-bulk-select-all="{{ $bulkFormId }}" aria-label="Select all GIS enquiries on this page">
                            </th>
                        @endif
                        <th>Lead</th>
                        <th>Source</th>
                        <th>Contact</th>
                        <th>Inquiry</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($data as $row)
                        @php
                            $isFairLead = $row instanceof \App\Models\GisFairLead;
                            $assigneeName = $row->assignedTo?->name;
                            $assigneeInitial = $assigneeName ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($assigneeName, 0, 1)) : '-';
                            $phone = $isFairLead ? $row->phone_e164 : $row->phone_number;
                            $inquiry = $isFairLead ? ($row->campaign?->name ?: 'Fair registration') : $row->inquiry;
                            $message = $isFairLead
                                ? (collect($row->interests)->filter()->isNotEmpty() ? 'Interests: '.collect($row->interests)->filter()->join(', ') : $row->current_system)
                                : $row->message;
                            $assignRoute = $isFairLead ? route('gis-fair.leads.assign', $row) : route('gis-enquiries.assign', $row->id);
                            $statusRoute = $isFairLead ? route('gis-fair.leads.status', $row) : route('gis-enquiries.status', $row->id);
                            $replyRoute = $isFairLead ? route('gis-fair.leads.reply', $row) : route('gis-enquiries.reply', $row->id);
                            $deleteRoute = $isFairLead ? route('gis-fair.leads.destroy', $row) : route('gis-enquiries.destroy', $row->id);
                            $restoreRoute = $isFairLead ? route('gis-fair.leads.restore', $row->id) : route('gis-enquiries.restore', $row->id);
                            $spamRoute = $isFairLead ? route('gis-fair.leads.spam-status', $row) : route('gis-enquiries.spam-status', $row->id);
                        @endphp
                        <tr>
                            @if($bulkEnabled)
                                <td class="crm-select-cell">
                                    <input class="crm-select-checkbox" type="checkbox" name="records[]" value="{{ $row->record_key }}" form="{{ $bulkFormId }}" data-bulk-item aria-label="Select {{ trim($row->first_name.' '.$row->last_name) }}">
                                </td>
                            @endif
                            <td>
                                <div class="crm-primary">{{ trim($row->first_name.' '.$row->last_name) }}</div>
                                @if($isFairLead && $row->company)<div class="crm-muted">{{ $row->company }}</div>@endif
                            </td>
                            <td>
                                <span class="crm-source crm-source-{{ $isFairLead ? 'fair' : 'direct' }}">{{ $isFairLead ? 'fair-funnel' : 'GIS-Enquiry' }}</span>
                                @if($isFairLead)<div class="crm-muted">{{ $row->fair_code }}</div>@endif
                            </td>
                            <td>
                                <div class="crm-email-line">
                                    <a class="crm-link" href="mailto:{{ $row->email }}">{{ $row->email }}</a>
                                    @if(! $row->trashed())
                                        <a class="crm-reply-link" href="{{ $replyRoute }}" title="Reply email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                    @endif
                                </div>
                                <a href="tel:{{ $phone }}">{{ $phone }}</a>
                            </td>
                            <td>{{ $inquiry }}</td>
                            <td class="crm-muted" title="{{ $message }}">
                                {{ \Illuminate\Support\Str::limit($message, 120) ?: '-' }}
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
                                                <form method="post" action="{{ $assignRoute }}" class="crm-action-form">
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
                                            <form method="post" action="{{ $statusRoute }}" class="crm-action-form">
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
                                            <form method="post" action="{{ $deleteRoute }}" class="crm-action-form" data-confirm="Move this GIS prospect to deleted records?" data-confirm-title="Delete GIS prospect" data-confirm-tone="danger" data-confirm-button="Delete">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger crm-icon-btn" type="submit" title="Soft delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan

                                        @can('restore', $row)
                                            @if($row->spam_status === 'suspected')
                                                <form method="post" action="{{ $spamRoute }}" class="crm-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="spam_status" value="not_spam">
                                                    <button class="btn btn-sm btn-outline-success crm-icon-btn" type="submit" title="Mark valid">
                                                        <i class="fas fa-inbox"></i>
                                                    </button>
                                                </form>
                                                <form method="post" action="{{ $spamRoute }}" class="crm-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="spam_status" value="confirmed">
                                                    <button class="btn btn-sm btn-outline-secondary crm-icon-btn" type="submit" title="Confirm spam">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @elseif(in_array($row->spam_status, ['clean', 'not_spam'], true))
                                                <form method="post" action="{{ $spamRoute }}" class="crm-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="spam_status" value="suspected">
                                                    <button class="btn btn-sm btn-outline-warning crm-icon-btn" type="submit" title="Move to suspected spam">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                        @if($isFairLead)
                                            <a class="btn btn-sm btn-outline-secondary crm-icon-btn" href="{{ route('gis-fair.leads.show', $row) }}" title="View fair registration">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    @else
                                        @can('restore', $row)
                                            <form method="post" action="{{ $restoreRoute }}" class="crm-action-form">
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
                            <td colspan="{{ $bulkEnabled ? 10 : 9 }}" class="crm-empty">No GIS prospects found.</td>
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
