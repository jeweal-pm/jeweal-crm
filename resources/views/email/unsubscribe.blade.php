@extends('layouts.UserLayout')

@section('title', 'Email Preferences')

@section('content')
<div class="container py-5" style="max-width: 640px">
    <div class="card">
        <div class="card-body">
            <h2>Email preferences</h2>
            <p class="text-muted">Choose whether you want to stop receiving marketing email.</p>
            <form method="post" action="{{ url('/unsubscribe/'.$subscriber->unsubscribe_token_hash) }}">
                @csrf
                <div class="form-group">
                    <label for="category">Email category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="all_marketing">All marketing email</option>
                        <option value="promotion">Promotions</option>
                        <option value="newsletter">Newsletter</option>
                    </select>
                </div>
                <button class="btn btn-danger" type="submit">Unsubscribe</button>
            </form>
        </div>
    </div>
</div>
@endsection
