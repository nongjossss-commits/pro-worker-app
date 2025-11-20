@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Incomplete Employee Data</h1>
            <p class="text-muted">Employees with missing mandatory information.</p>
        </div>
        <div>
            @role('admin')
            <a href="{{ route('admin.settings.completeness.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-gear-fill me-1"></i> Configure Settings
            </a>
            @endrole
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0 me-2" style="font-size: 1.5rem;"></i>
            <div>
                <strong>Great job!</strong> No employees found with missing mandatory data.
            </div>
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" style="font-size: 1.5rem;"></i>
            <div>
                Found <strong>{{ $totalIncomplete }}</strong> employees with missing data. Please update their profiles.
            </div>
        </div>

        <div class="row g-3">
            @foreach($employees as $employee)
                <div class="col-12 col-md-6 col-xl-4">
                    @include('employees._employee_card', ['employee' => $employee, 'is_incomplete_view' => true])
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
