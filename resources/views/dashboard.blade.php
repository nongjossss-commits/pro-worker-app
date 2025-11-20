@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <div class="py-4">
        <h2 class="mb-4">{{ __('Dashboard') }}</h2>

        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-0">{{ __("You're logged in!") }}</p>
            </div>
        </div>
    </div>
@endsection
