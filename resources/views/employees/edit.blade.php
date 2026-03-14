@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลพนักงาน')

@section('content')
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ __('แก้ไขข้อมูลพนักงาน:') }}<span class="fw-bold">{{ $employee->employeeNameTh }}</span></h2>
        <a href="{{ route('employers.edit', $employee->employer_id) }}#employee-card-{{ $employee->id }}" class="btn btn-secondary">{{ __('กลับไปที่นายจ้าง') }}</a>
    </div>

    @if(isset($missingFields) && count($missingFields) > 0)
        <div class="alert alert-warning d-flex align-items-center" role="alert">
             <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
             <div>
                 <strong>Data Incomplete:</strong> This employee is missing {{ count($missingFields) }} required fields. Please fill in the fields marked with <i class="bi bi-exclamation-circle-fill text-warning"></i>.
             </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('employees.partials._edit_form', ['employee' => $employee])

    <!-- Cropper Modal -->
    <x-cropper-modal />

    @include('employees.partials._edit_scripts')
</div>
@endsection
