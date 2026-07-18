@extends('layouts.main')

@section('head')
@section('title', 'JEWEAL CRM Login')
<style>
    .crm-login-card {
        width: min(420px, 100%);
        background: #ffffff;
        border: 1px solid #d9e1ec;
        border-radius: 12px;
        box-shadow: 0 24px 80px rgba(16, 24, 40, 0.14);
        padding: 28px;
    }

    .crm-login-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 28px;
    }

    .crm-login-mark {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #1f6feb;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 800;
    }

    .crm-login-title {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: 0;
    }

    .crm-login-subtitle {
        color: #667085;
        font-size: 14px;
        margin-top: 4px;
    }

    .crm-login-card label {
        font-size: 12px;
        font-weight: 800;
        color: #344054;
        margin-bottom: 6px;
    }

    .crm-login-card .form-control {
        min-height: 42px;
        border-radius: 8px;
        border-color: #d9e1ec;
    }

    .crm-login-button {
        width: 100%;
        min-height: 42px;
        border-radius: 8px;
        border: 0;
        background: #1f6feb;
        color: #ffffff;
        font-weight: 800;
    }

    .crm-login-button:hover {
        background: #1750b8;
        color: #ffffff;
    }
</style>
@endsection

@section('content')
    <form class="crm-login-card" method="post" action="{{ route('login') }}">
        @csrf

        <div class="crm-login-brand">
            <div class="crm-login-mark">J</div>
            <div>
                <h1 class="crm-login-title">Jeweal CRM</h1>
                <div class="crm-login-subtitle">Sign in to manage sales operations.</div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@jeweal.com" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        </div>

        <button type="submit" class="btn crm-login-button">Login</button>
    </form>
@endsection
