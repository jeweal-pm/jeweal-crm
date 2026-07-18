@extends('layouts.main')

@section('head')
@section('title', 'CRM Dashboard')
@include('administrator.users.partials.user-management-styles')
@endsection

@section('content')
    <div class="crm-page-shell">
        <div class="crm-page-container">
            <div class="user-crm-topbar">
                <div class="user-crm-title">
                    <h1>Dashboard</h1>
                    <div class="user-crm-subtitle">A clean operational overview for CRM enquiries, GIS leads, and team access.</div>
                </div>
                <div class="user-crm-actions">
                    <a class="user-crm-btn user-crm-btn-primary" href="{{ route('enquiry.index') }}">
                        <i class="fas fa-inbox"></i> Open Enquiries
                    </a>
                    @if(Auth::user()->hasCrmPermission('user.view'))
                        <a class="user-crm-btn" href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                    @endif
                </div>
            </div>

            <div class="user-crm-metrics">
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">Clean Enquiries</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['enquiries']) }}</div>
                </div>
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">GIS Enquiries</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['gis_enquiries']) }}</div>
                </div>
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">Suspected Spam</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['suspected_spam']) }}</div>
                </div>
                <div class="user-crm-metric">
                    <div class="user-crm-metric-label">Active Users</div>
                    <div class="user-crm-metric-value">{{ number_format($summary['active_users']) }}</div>
                </div>
            </div>

            <div class="user-crm-panel">
                <div class="user-crm-panel-head">
                    <h2 class="user-crm-panel-title">Primary Workflows</h2>
                    <div class="user-crm-count">CRM modules</div>
                </div>
                <div class="table-responsive">
                    <table class="table user-crm-table">
                        <thead>
                        <tr>
                            <th>Module</th>
                            <th>Purpose</th>
                            <th>Records</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <div class="user-crm-primary">Enquiries</div>
                                <div class="user-crm-muted">Main inbound lead queue</div>
                            </td>
                            <td>Qualification, assignment, status updates, and spam review.</td>
                            <td>{{ number_format($summary['enquiries']) }}</td>
                            <td><a class="user-crm-btn" href="{{ route('enquiry.index') }}">Open</a></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="user-crm-primary">GIS Enquiries</div>
                                <div class="user-crm-muted">GIS-specific lead workflow</div>
                            </td>
                            <td>Track GIS quote requests and customer conversion.</td>
                            <td>{{ number_format($summary['gis_enquiries']) }}</td>
                            <td><a class="user-crm-btn" href="{{ route('gisEnquiry') }}">Open</a></td>
                        </tr>
                        @if(Auth::user()->hasCrmPermission('user.view'))
                            <tr>
                                <td>
                                    <div class="user-crm-primary">User Management</div>
                                    <div class="user-crm-muted">Access and roles</div>
                                </td>
                                <td>Create users, assign CRM roles, and review account status.</td>
                                <td>{{ number_format($summary['users']) }}</td>
                                <td><a class="user-crm-btn" href="{{ route('users.index') }}">Open</a></td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
