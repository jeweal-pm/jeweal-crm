@extends('layouts.main')

@section('head')
@section('title', 'User Management')
@include('administrator.users.partials.user-management-styles')
@endsection

@section('content')
    <div class="user-crm-shell">
        <div class="user-crm-container">
            <div class="user-crm-topbar">
                <div class="user-crm-title">
                    <h1>User Management</h1>
                    <div class="user-crm-subtitle">Manage CRM access, roles, and account status for the Jeweal team.</div>
                </div>
                <div class="user-crm-actions">
                    <a class="user-crm-btn" href="{{ route('enquiry.index') }}">
                        <i class="fas fa-arrow-left"></i> Enquiries
                    </a>
                    @if(Auth::user()->hasCrmPermission('user.create'))
                        <a class="user-crm-btn user-crm-btn-primary" href="{{ route('users.create') }}">
                            <i class="fas fa-user-plus"></i> New User
                        </a>
                    @endif
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="user-crm-metrics">
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">Total Users</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['total']) }}</div>
                </div>
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">Active</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['active']) }}</div>
                </div>
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">Inactive</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['inactive']) }}</div>
                </div>
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">CRM Roles</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['roles']) }}</div>
                </div>
            </div>

            <form method="get" action="{{ route('users.index') }}" class="user-crm-toolbar">
                <div class="form-row">
                    <div class="form-group col-lg-4 col-md-6">
                        <label>Search</label>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name or email">
                    </div>
                    <div class="form-group col-lg-2 col-md-6">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="">All roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>
                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-6">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All statuses</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-6">
                        <label>Sort</label>
                        <select name="sort" class="form-control">
                            <option value="-created_at" @selected(($filters['sort'] ?? '-created_at') === '-created_at')>Newest first</option>
                            <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Oldest first</option>
                            <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Name A-Z</option>
                            <option value="-name" @selected(($filters['sort'] ?? '') === '-name')>Name Z-A</option>
                            <option value="email" @selected(($filters['sort'] ?? '') === 'email')>Email A-Z</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-12 d-flex align-items-end">
                        <button class="user-crm-btn user-crm-btn-primary btn-block" type="submit">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </div>
                </div>
            </form>

            <div class="user-crm-panel">
                <div class="user-crm-panel-head">
                    <h2 class="user-crm-panel-title">Team Accounts</h2>
                    <div class="user-crm-count">{{ number_format($users->total()) }} results</div>
                </div>

                <div class="table-responsive">
                    <table class="table user-crm-table">
                        <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Invited</th>
                            <th>Activated</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($users as $user)
                            @php
                                $initials = collect(explode(' ', trim($user->name)))
                                    ->filter()
                                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                            @endphp
                            <tr>
                                <td>
                                    <div class="user-crm-person">
                                        <div class="user-crm-avatar">{{ $initials ?: 'U' }}</div>
                                        <div>
                                            <div class="user-crm-primary">{{ $user->name }}</div>
                                            <div class="user-crm-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-crm-badge">
                                        <i class="fas fa-shield-alt"></i>
                                        {{ $user->primaryRole ? ucwords(str_replace('_', ' ', $user->primaryRole->name)) : 'No role' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="user-crm-badge {{ $user->is_active ? 'user-crm-status-active' : 'user-crm-status-inactive' }}">
                                        <i class="fas {{ $user->is_active ? 'fa-check-circle' : 'fa-pause-circle' }}"></i>
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $user->createdBy?->name ?? '-' }}</td>
                                <td>{{ $user->invited_at?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $user->activated_at?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="user-crm-empty">No users found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
