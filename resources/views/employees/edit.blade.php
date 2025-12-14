@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลพนักงาน')

@section('content')
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>แก้ไขข้อมูลพนักงาน: <span class="fw-bold">{{ $employee->employeeNameTh }}</span></h2>
        <a href="{{ route('employers.edit', $employee->employer_id) }}#employee-card-{{ $employee->id }}" class="btn btn-secondary">กลับไปที่นายจ้าง</a>
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
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cropperModalLabel">ครอบตัดรูปภาพ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <style>
                        .img-container {
                            max-height: 500px;
                            display: block;
                        }
                        .img-container img {
                            max-width: 100%;
                            display: block;
                        }
                    </style>
                    <div class="img-container">
                        <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="cropImageBtn">ครอบตัดและบันทึก</button>
                </div>
            </div>
        </div>
    </div>

    @include('employees.partials._edit_scripts')
</div>
@endsection
