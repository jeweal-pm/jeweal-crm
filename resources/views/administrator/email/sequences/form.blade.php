@extends('layouts.UserLayout')

@section('title', 'New Email Sequence')

@section('head')
    @include('administrator.email.partials.styles')
@endsection

@section('content')
<div class="email-workspace">
    <section class="crm-page">
        <div class="container-fluid">
            <div class="crm-topbar"><div class="crm-title"><h2>New Email Sequence</h2><div class="crm-subtitle">Create the sequence first, then add and configure its email steps.</div></div><a class="btn btn-light" href="{{ route('email.sequences') }}"><i class="fas fa-arrow-left"></i> Back to sequences</a></div>
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="post" action="{{ route('email.sequences.store') }}" class="crm-panel email-form-panel">
                @csrf
                <div class="email-panel-head"><div><h3 class="crm-panel-title">Sequence details</h3><div class="email-panel-copy">New sequences start as Draft and cannot send until you publish them.</div></div></div>
                <div class="email-panel-body"><div class="row"><div class="col-md-6 form-group"><label>Name</label><input class="form-control" name="name" value="{{ old('name') }}" required autofocus></div><div class="col-md-6 form-group"><label>Code</label><input class="form-control" name="code" value="{{ old('code') }}" placeholder="gis-follow-up" required></div><div class="col-12 form-group"><label>Description</label><textarea class="form-control" name="description" rows="5">{{ old('description') }}</textarea></div></div><div class="email-form-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Create sequence</button><a class="btn btn-light" href="{{ route('email.sequences') }}">Cancel</a></div></div>
            </form>
        </div>
    </section>
</div>
@endsection
