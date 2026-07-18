@extends('layouts.main')

@section('head')
@section('title', 'Create User')
@include('administrator.users.partials.user-management-styles')
@endsection

@section('content')
    <div class="user-crm-shell">
        <div class="user-crm-container">
            <div class="user-crm-topbar">
                <div class="user-crm-title">
                    <h1>Create User</h1>
                    <div class="user-crm-subtitle">Add a teammate and assign their CRM access role.</div>
                </div>
                <div class="user-crm-actions">
                    <a class="user-crm-btn" href="{{ route('users.index') }}">
                        <i class="fas fa-arrow-left"></i> User Management
                    </a>
                    <button form="user-create-form" type="submit" class="user-crm-btn user-crm-btn-primary">
                        <i class="fas fa-save"></i> Create User
                    </button>
                </div>
            </div>

            @if($errors->any())
                <div class="user-crm-alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="user-crm-form-panel">
                <form id="user-create-form" method="post" action="{{ route('users.store') }}" class="user-crm-form">
                    @csrf

                    <div class="user-crm-form-grid">
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Full name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@jeweal.com" required>
                        </div>

                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="is_active">Account Status</label>
                            <div class="user-crm-switch-row">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                                <label class="mb-0" for="is_active">Active and ready to sign in</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="inputPassword">Password *</label>
                            <input type="password" class="form-control" id="inputPassword" name="password" placeholder="At least 8 characters" required>
                        </div>

                        <div class="form-group">
                            <label for="inputConfirmPassword">Confirm Password *</label>
                            <input type="password" class="form-control" id="inputConfirmPassword" name="password_confirmation" placeholder="Repeat password" required>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
