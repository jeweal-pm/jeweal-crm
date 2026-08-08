@extends('layouts.main')

@section('head')
@section('title', 'GMS Enquiries')
@include('administrator.enquiry.partials.crm-workspace-styles')
<style>
    .gms-enquiry-table {
        min-width: 1380px;
        table-layout: fixed;
    }

    .gms-enquiry-table .crm-actions {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .gms-enquiry-table .crm-action-form select {
        width: 132px;
    }

    .gms-contact-line {
        align-items: center;
        display: flex;
        gap: 6px;
        min-width: 0;
    }

    .gms-contact-line .crm-link {
        overflow-wrap: anywhere;
    }

    .gms-reply-btn {
        color: #dc2626;
        flex: 0 0 auto;
        font-size: 12px;
        line-height: 1;
    }

    .gms-reply-btn:hover {
        color: #991b1b;
        text-decoration: none;
    }
</style>
@endsection

@section('content')
<section class="content crm-page">
    <div class="container-fluid">
        <div class="crm-topbar">
            <div class="crm-title">
                <h2>GMS Enquiries</h2>
                <div class="crm-subtitle">Stone account requests, billing details, and contact review in one working view.</div>
            </div>
            <div class="crm-switcher">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('enquiry.index') }}">
                    <i class="fas fa-list"></i> Enquiries
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('gisEnquiry') }}">
                    <i class="fas fa-map-marker-alt"></i> GIS
                </a>
                <a class="btn btn-primary btn-sm" href="{{ route('gms-enquiries.index') }}">
                    <i class="fas fa-gem"></i> GMS
                </a>
            </div>
        </div>

        <div class="crm-metrics">
            <div class="crm-metric">
                <div class="crm-metric-label">Active Requests</div>
                <div class="crm-metric-value">{{ number_format($summary['total']) }}</div>
            </div>
            <div class="crm-metric">
                <div class="crm-metric-label">Business</div>
                <div class="crm-metric-value">{{ number_format($summary['business']) }}</div>
            </div>
            <div class="crm-metric">
                <div class="crm-metric-label">Unseen</div>
                <div class="crm-metric-value">{{ number_format($summary['unseen']) }}</div>
            </div>
            <div class="crm-metric">
                <div class="crm-metric-label">Approved</div>
                <div class="crm-metric-value">{{ number_format($summary['approved']) }}</div>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="get" action="{{ route('gms-enquiries.index') }}" class="crm-toolbar">
            <div class="form-row">
                <div class="form-group col-lg-2 col-md-4">
                    <label>Account type</label>
                    <select name="account_type" class="form-control">
                        <option value="">All types</option>
                        <option value="personal" @selected(($filters['account_type'] ?? '') === 'personal')>Personal</option>
                        <option value="business" @selected(($filters['account_type'] ?? '') === 'business')>Business</option>
                    </select>
                </div>
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
                    <label>Seen</label>
                    <select name="is_seen" class="form-control">
                        <option value="">Any</option>
                        <option value="0" @selected(($filters['is_seen'] ?? '') === '0')>Unseen</option>
                        <option value="1" @selected(($filters['is_seen'] ?? '') === '1')>Seen</option>
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Approved</label>
                    <select name="is_approved" class="form-control">
                        <option value="">Any</option>
                        <option value="0" @selected(($filters['is_approved'] ?? '') === '0')>Not approved</option>
                        <option value="1" @selected(($filters['is_approved'] ?? '') === '1')>Approved</option>
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
                    <label>Sort</label>
                    <select name="sort" class="form-control">
                        <option value="-created_at" @selected(($filters['sort'] ?? '-created_at') === '-created_at')>Newest first</option>
                        <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Oldest first</option>
                        <option value="full_name" @selected(($filters['sort'] ?? '') === 'full_name')>Name A-Z</option>
                        <option value="status" @selected(($filters['sort'] ?? '') === 'status')>Status A-Z</option>
                        <option value="-updated_at" @selected(($filters['sort'] ?? '') === '-updated_at')>Recently updated</option>
                    </select>
                </div>
                <div class="form-group col-lg-10 col-md-8">
                    <label>Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, email, phone, company">
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
                <h3 class="crm-panel-title">GMS Stone Records</h3>
                <div>
                    <span class="crm-result-count mr-2">{{ number_format($data->total()) }} results</span>
                    <a href="{{ route('gms-enquiries.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover crm-table gms-enquiry-table">
                    <colgroup>
                        <col style="width: 14%">
                        <col style="width: 13%">
                        <col style="width: 15%">
                        <col style="width: 10%">
                        <col style="width: 10%">
                        <col style="width: 12%">
                        <col style="width: 8%">
                        <col style="width: 18%">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Requester</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($data as $row)
                        <tr>
                            <td>
                                <div class="crm-primary">{{ $row->full_name }}</div>
                                <div class="crm-muted">{{ ucfirst($row->account_type) }} account</div>
                                <div class="crm-muted">{{ $row->country_code }}</div>
                            </td>
                            <td>
                                <div>{{ $row->company_name ?: $row->business_name ?: '-' }}</div>
                                <div class="crm-muted">{{ $row->tax_id ?: 'No tax ID' }}</div>
                                @if($row->website)
                                    <a class="crm-link" href="{{ $row->website }}" target="_blank" rel="noopener">Website</a>
                                @endif
                            </td>
                            <td>
                                <div class="gms-contact-line">
                                    <a class="crm-link" href="mailto:{{ $row->email }}">{{ $row->email }}</a>
                                    @if(! $row->trashed())
                                        <a class="gms-reply-btn" href="{{ route('gms-enquiries.reply', $row->id) }}" title="Reply email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                    @endif
                                </div>
                                <a href="tel:{{ $row->phone_number }}">{{ $row->phone_number }}</a>
                                @if($row->contact_name)
                                    <div class="crm-muted">{{ $row->contact_name }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $row->city ?: '-' }}</div>
                                <div class="crm-muted">{{ collect([$row->province, $row->country])->filter()->implode(', ') }}</div>
                            </td>
                            <td>
                                @if($row->trashed())
                                    <span class="crm-status crm-status-deleted">Deleted</span>
                                @else
                                    <span class="crm-status crm-status-{{ $row->status }}">
                                        {{ $statusOptions[$row->status] ?? $row->status }}
                                    </span>
                                    @if($row->is_approved)
                                        <div class="crm-muted mt-1">Approved</div>
                                    @endif
                                    <div class="crm-muted mt-1">{{ $row->is_seen ? 'Seen' : 'Unseen' }}</div>
                                    <div class="crm-muted">
                                        PP: {{ $row->privacy_policy_accepted ? 'Yes' : 'No' }} /
                                        Terms: {{ $row->terms_conditions_accepted ? 'Yes' : 'No' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $assigneeName = $row->assignedTo?->name;
                                    $assigneeInitial = $assigneeName ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($assigneeName, 0, 1)) : '-';
                                @endphp
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
                                    @if(! $row->trashed() && $assignableUsers->isNotEmpty())
                                        @can('assign', $row)
                                            <form method="post" action="{{ route('gms-enquiries.assign', $row->id) }}" class="crm-action-form">
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
                                        @endcan
                                    @endif
                                    @if(! $row->trashed())
                                        @can('updateStatus', $row)
                                            <form method="post" action="{{ route('gms-enquiries.status', $row->id) }}" class="crm-action-form">
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
                                    @endif
                                    <a class="btn btn-sm btn-outline-secondary crm-icon-btn" href="{{ route('gms-enquiries.show', $row->id) }}" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(! $row->trashed())
                                        <a class="btn btn-sm btn-outline-primary crm-icon-btn" href="{{ route('gms-enquiries.edit', $row->id) }}" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="post" action="{{ route('gms-enquiries.destroy', $row->id) }}" class="crm-action-form" onsubmit="return confirm('Soft delete this GMS enquiry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger crm-icon-btn" type="submit" title="Soft delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('gms-enquiries.restore', $row->id) }}" class="crm-action-form">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary crm-icon-btn" type="submit" title="Restore">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="crm-empty">No GMS enquiries found.</td>
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
