@extends('layouts.main')

@section('head')
@section('title', $mode === 'edit' ? 'Edit GMS Enquiry' : 'Create GMS Enquiry')
@include('administrator.enquiry.partials.crm-workspace-styles')
@endsection

@section('content')
<section class="content crm-page">
    <div class="container-fluid">
        <div class="crm-topbar">
            <div class="crm-title">
                <h2>{{ $mode === 'edit' ? 'Edit GMS Enquiry' : 'Create GMS Enquiry' }}</h2>
                <div class="crm-subtitle">Maintain stone account request details and contact data.</div>
            </div>
            <div class="crm-switcher">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('gms-enquiries.index') }}">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                Please check the highlighted fields and try again.
            </div>
        @endif

        <form method="post" action="{{ $mode === 'edit' ? route('gms-enquiries.update', $enquiry->id) : route('gms-enquiries.store') }}" class="crm-toolbar">
            @csrf
            @if($mode === 'edit')
                @method('PUT')
            @endif

            <div class="form-row">
                <div class="form-group col-lg-4 col-md-6">
                    <label>Full name</label>
                    <input name="full_name" value="{{ old('full_name', $enquiry->full_name) }}" class="form-control @error('full_name') is-invalid @enderror" required>
                    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-lg-4 col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $enquiry->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-lg-2 col-md-6">
                    <label>Phone</label>
                    <input name="phone_number" value="{{ old('phone_number', $enquiry->phone_number) }}" class="form-control @error('phone_number') is-invalid @enderror" required>
                    @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-lg-2 col-md-6">
                    <label>Country code</label>
                    <input name="country_code" value="{{ old('country_code', $enquiry->country_code) }}" class="form-control @error('country_code') is-invalid @enderror" required>
                    @error('country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group col-lg-3 col-md-6">
                    <label>Account type</label>
                    <select name="account_type" class="form-control @error('account_type') is-invalid @enderror" required>
                        <option value="personal" @selected(old('account_type', $enquiry->account_type) === 'personal')>Personal</option>
                        <option value="business" @selected(old('account_type', $enquiry->account_type) === 'business')>Business</option>
                    </select>
                    @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label>Business name</label>
                    <input name="business_name" value="{{ old('business_name', $enquiry->business_name) }}" class="form-control">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label>Company name</label>
                    <input name="company_name" value="{{ old('company_name', $enquiry->company_name) }}" class="form-control">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label>Tax ID</label>
                    <input name="tax_id" value="{{ old('tax_id', $enquiry->tax_id) }}" class="form-control">
                </div>

                <div class="form-group col-lg-3 col-md-6">
                    <label>Mailing name</label>
                    <input name="mailing_name" value="{{ old('mailing_name', $enquiry->mailing_name) }}" class="form-control">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label>Website</label>
                    <input name="website" value="{{ old('website', $enquiry->website) }}" class="form-control @error('website') is-invalid @enderror">
                    @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label>Office type</label>
                    <input name="office_type" value="{{ old('office_type', $enquiry->office_type) }}" class="form-control">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label>Branch code</label>
                    <input name="branch_code" value="{{ old('branch_code', $enquiry->branch_code) }}" class="form-control">
                </div>

                <div class="form-group col-lg-6">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $enquiry->address) }}</textarea>
                </div>
                <div class="form-group col-lg-6">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Country</label>
                            <input name="country" value="{{ old('country', $enquiry->country) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>City</label>
                            <input name="city" value="{{ old('city', $enquiry->city) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Province</label>
                            <input name="province" value="{{ old('province', $enquiry->province) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Postcode</label>
                            <input name="postcode" value="{{ old('postcode', $enquiry->postcode) }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group col-lg-4 col-md-6">
                    <label>Contact name</label>
                    <input name="contact_name" value="{{ old('contact_name', $enquiry->contact_name) }}" class="form-control">
                </div>
                <div class="form-group col-lg-4 col-md-6">
                    <label>Contact email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $enquiry->contact_email) }}" class="form-control @error('contact_email') is-invalid @enderror">
                    @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-lg-4 col-md-6">
                    <label>Contact phone</label>
                    <input name="contact_phone" value="{{ old('contact_phone', $enquiry->contact_phone) }}" class="form-control @error('contact_phone') is-invalid @enderror">
                    @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group col-md-3">
                    <label>Seen</label>
                    <select name="is_seen" class="form-control">
                        <option value="0" @selected(! old('is_seen', $enquiry->is_seen))>Unseen</option>
                        <option value="1" @selected((bool) old('is_seen', $enquiry->is_seen))>Seen</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Approved</label>
                    <select name="is_approved" class="form-control">
                        <option value="0" @selected(! old('is_approved', $enquiry->is_approved))>Not approved</option>
                        <option value="1" @selected((bool) old('is_approved', $enquiry->is_approved))>Approved</option>
                    </select>
                </div>
                <div class="form-group col-md-6 d-flex align-items-end justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-save"></i> Save GMS Enquiry
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
