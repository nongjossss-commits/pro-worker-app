@extends('layouts.app')
@section('title', 'Admin Ticket Inbox')
@section('content')
<div class="content-section">
    <h1>Admin/Staff Ticket Inbox (V2.4-S2 Placeholder)</h1>
    <p>Total Tickets: {{ $tickets->total() }}</p>
</div>
@endsection
