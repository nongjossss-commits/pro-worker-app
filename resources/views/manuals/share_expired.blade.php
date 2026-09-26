{{-- Shown instead of the manual when a public share link has passed its
     expiry date (ManualShareController::show, HTTP 410). Same design as the
     app's error pages; no buttons — the visitor has no account here. --}}
@extends('errors.minimal')

@section('title', __('Link expired'))
@section('code', '410')
@section('message', __('This manual link has expired'))
@section('hint', __('Please ask the person who sent you this link for a new one.'))

@section('actions')
@endsection
