@extends('layouts.app')
@section('title', 'My Tickets')
@section('content')
<div class="content-section">
    <h1>My Tickets (Employer V2.4-S2 Placeholder)</h1>
    <p>My Total Tickets: {{ $tickets->total() }}</p>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">Create New Ticket</a>
</div>
@endsection
