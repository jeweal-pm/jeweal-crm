@extends('layouts.main')

@section('head')
@section('title', 'GMS Enquiry Detail')
@include('administrator.enquiry.partials.crm-workspace-styles')
@endsection

@section('content')
<section class="content crm-page">
    <div class="container-fluid">
        <div class="crm-topbar">
            <div class="crm-title">
                <h2>{{ $enquiry->full_name }}</h2>
                <div class="crm-subtitle">{{ ucfirst($enquiry->account_type) }} GMS stone account request.</div>
            </div>
            <div class="crm-switcher">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('gms-enquiries.index') }}">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if(! $enquiry->trashed())
                    <a class="btn btn-primary btn-sm" href="{{ route('gms-enquiries.edit', $enquiry->id) }}">
                        <i class="fas fa-pen"></i> Edit
                    </a>
                @endif
            </div>
        </div>

        <div class="crm-panel">
            <div class="crm-panel-head">
                <h3 class="crm-panel-title">Request Details</h3>
                <span class="crm-status {{ $enquiry->trashed() ? 'crm-status-deleted' : ($enquiry->is_approved ? 'crm-status-customer' : 'crm-status-prospect') }}">
                    {{ $enquiry->trashed() ? 'Deleted' : ($enquiry->is_approved ? 'Approved' : 'Pending') }}
                </span>
            </div>
            <div class="p-3">
                <div class="row">
                    @foreach([
                        'Email' => $enquiry->email,
                        'Phone' => $enquiry->phone_number,
                        'Country code' => $enquiry->country_code,
                        'Business name' => $enquiry->business_name,
                        'Company name' => $enquiry->company_name,
                        'Tax ID' => $enquiry->tax_id,
                        'Mailing name' => $enquiry->mailing_name,
                        'Website' => $enquiry->website,
                        'Office type' => $enquiry->office_type,
                        'Branch code' => $enquiry->branch_code,
                        'Contact name' => $enquiry->contact_name,
                        'Contact email' => $enquiry->contact_email,
                        'Contact phone' => $enquiry->contact_phone,
                        'Location' => collect([$enquiry->city, $enquiry->province, $enquiry->country, $enquiry->postcode])->filter()->implode(', '),
                        'Seen' => $enquiry->is_seen ? 'Yes' : 'No',
                        'Privacy policy' => $enquiry->privacy_policy_accepted ? 'Accepted' : 'Not accepted',
                        'Terms conditions' => $enquiry->terms_conditions_accepted ? 'Accepted' : 'Not accepted',
                    ] as $label => $value)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="crm-muted">{{ $label }}</div>
                            <div class="crm-primary">{{ $value ?: '-' }}</div>
                        </div>
                    @endforeach
                    <div class="col-12">
                        <div class="crm-muted">Address</div>
                        <div>{{ $enquiry->address ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
