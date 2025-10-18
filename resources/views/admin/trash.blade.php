@extends('layouts.app')

@section('title', 'Admin Trash Can')

@section('content')
<div class="container mt-4">
    <h1><i class="fas fa-trash-alt"></i> Admin Trash Can</h1>
    <p>This page shows all items that have been "soft-deleted" (moved to the trash). You can restore them or delete them permanently.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($trashedEmployers->count() > 0)
        <h3 class="mt-4">Employers ({{ $trashedEmployers->count() }})</h3>
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Name (TH)</th>
                    <th>Deleted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trashedEmployers as $item)
                    <tr>
                        <td>{{ $item->employerNameTh }} ({{ $item->employerId }})</td>
                        <td>{{ $item->deleted_at->format('Y-m-d H:i') }}</td>
                        <td class="d-flex">
                            @can('restore-employers')
                                <form action="{{ route('admin.trash.restore', ['model' => 'employer', 'id' => $item->id]) }}" method="POST" class="me-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Restore</button>
                                </form>
                            @endcan
                            @can('force-delete-employers')
                                <form action="{{ route('admin.trash.forceDelete', ['model' => 'employer', 'id' => $item->id]) }}" method="POST" onsubmit="return confirm('PERMANENTLY DELETE? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Force Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($trashedAgents->count() > 0)
        <h3 class="mt-4">Agents ({{ $trashedAgents->count() }})</h3>
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Name (EN)</th>
                    <th>Deleted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trashedAgents as $item)
                    <tr>
                        <td>{{ $item->agentNameEn }}</td>
                        <td>{{ $item->deleted_at->format('Y-m-d H:i') }}</td>
                        <td class="d-flex">
                            @can('restore-agents')
                                <form action="{{ route('admin.trash.restore', ['model' => 'agent', 'id' => $item->id]) }}" method="POST" class="me-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Restore</button>
                                </form>
                            @endcan
                            @can('force-delete-agents')
                                <form action="{{ route('admin.trash.forceDelete', ['model' => 'agent', 'id' => $item->id]) }}" method="POST" onsubmit="return confirm('PERMANENTLY DELETE? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Force Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    </div>
@endsection