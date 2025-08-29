@extends('layouts.app')

@section('title', 'Delegates List')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Delegates</h1>
                <a href="{{ route('delegates.create') }}" class="btn btn-primary">Add Delegate</a>
            </div>
            <hr>
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name (TH)</th>
                        <th>National ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($delegates as $delegate)
                    <tr>
                        <td>
                            @if ($delegate->delegatePhoto)
                                <img src="{{ asset('storage/' . $delegate->delegatePhoto) }}" alt="{{ $delegate->delegateNameEn }}" width="50">
                            @endif
                        </td>
                        <td>{{ $delegate->delegateNameTh }}</td>
                        <td>{{ $delegate->delegateId }}</td>
                        <td>
                            <a href="{{ route('delegates.edit', $delegate->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('delegates.destroy', $delegate->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this delegate?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
