@extends('layouts.app')

@section('title', 'Add New Registration Employee')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>{{ __('Add New Registration Employee') }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('production.registration.index') }}">{{ __('Registration Resolution') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Add New') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Main Content reusing logic --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('production.registration.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                @if(request()->has('employer_id'))
                    <input type="hidden" name="source_employer_id" value="{{ request('employer_id') }}">
                @endif

                @if(isset($employer) && $employer)
                    <input type="hidden" name="employer_id" value="{{ $employer->id }}">
                    <h5 class="mb-3">For Employer: <span class="text-primary">{{ $employer->employerNameTh }}</span></h5>
                @else
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="employer_id" class="form-label">เลือกนายจ้าง <span class="text-danger">*</span></label>
                            <select class="form-select" id="employer_id" name="employer_id" required>
                                <option value="">-- กรุณาเลือกนายจ้าง --</option>
                                @foreach($employers as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employer_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employerNameTh }} ({{ $emp->employerNameEn }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                {{-- Include the partial form logic but without the layout wrapper --}}
                @include('employees.create_form_partial_content')

                <div class="mt-4 d-flex justify-content-end">
                    <a href="{{ route('production.registration.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน (Registration)</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

@endsection

@push('scripts')
{{-- Include the scripts from the partial, but we must ensure they run correctly --}}
{{-- Since we cannot easily extract scripts from the partial without parsing,
     I will manually duplicate the script block here for stability,
     as requested to ensure "zero doubt" and robustness.
--}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- V6: Get all required elements ---
    const titleTh = document.getElementById('employeeTitleTh');
    const titleEn = document.getElementById('employeeTitleEn');
    const genderInput = document.getElementById('employeeGender');
    const dobInput = document.getElementById('employeeDob');
    const ageInput = document.getElementById('employeeAge');
    const nationalitySelect = document.getElementById('employeeNationality');
    const mouGroupSelect = document.getElementById('workPermitMOUGroup');
    const triggerFileInput = document.getElementById('triggerFile');
    const triggerCameraInput = document.getElementById('triggerCamera');
    const actualInput = document.getElementById('employeePhotoInput');
    const employeePhotoPreview = document.getElementById('employeePhotoPreview');

    // Containers for conditional logic
    const myanmarPassportContainer = document.getElementById('passportTypeContainer');
    const cambodiaPassportContainer = document.getElementById('passportTypeCambodiaContainer');
    const mouGroupOtherContainer = document.getElementById('workPermitMOUGroupOtherContainer');
    const insuranceSelect = document.getElementById('insurance_type');
    const socialContainer = document.getElementById('insuranceSocialSecurity');
    const hospitalContainer = document.getElementById('insuranceHospital');
    const privateContainer = document.getElementById('insurancePrivate');


    // --- Logic Block 1: Title & Gender Sync ---
    const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
    const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

    function syncTitles(source) {
        if (source === 'th') {
            const selectedTh = titleTh.value;
            if (thToEnMap[selectedTh]) {
                titleEn.value = thToEnMap[selectedTh];
            }
        } else {
            const selectedEn = titleEn.value;
            if (enToThMap[selectedEn]) {
                titleTh.value = enToThMap[selectedEn];
            }
        }
        updateGender();
    }

    function updateGender() {
        const selectedTh = titleTh.value;
        if (selectedTh === 'นาย') {
            genderInput.value = 'ชาย';
        } else if (selectedTh === 'นางสาว' || selectedTh === 'นาง') {
            genderInput.value = 'หญิง';
        } else {
            genderInput.value = '';
        }
    }

    if(titleTh) titleTh.addEventListener('change', () => syncTitles('th'));
    if(titleEn) titleEn.addEventListener('change', () => syncTitles('en'));


    // --- Logic Block 2: Age Calculation ---
    function calculateAge() {
        if(!dobInput) return;
        const dob = new Date(dobInput.value);
        if (!isNaN(dob.getTime())) {
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageInput.value = age > 0 ? age : 0;
        } else {
            ageInput.value = '';
        }
    }
    if(dobInput) dobInput.addEventListener('change', calculateAge);


    // --- Logic Block 3: Nationality Conditional Fields ---
    function toggleNationalityFields() {
        if(!nationalitySelect) return;
        // Myanmar
        myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
        // Cambodia
        cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
    }
    if(nationalitySelect) nationalitySelect.addEventListener('change', toggleNationalityFields);


    // --- Logic Block 4: MOU "Other" Field ---
     function toggleMouGroupOther() {
        if(!mouGroupSelect) return;
        mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
    }
    if(mouGroupSelect) mouGroupSelect.addEventListener('change', toggleMouGroupOther);


    // --- V6: Logic Block 5: Insurance Conditional Fields ---
    function toggleInsuranceVisibility() {
        if(!insuranceSelect) return;
        const selectedType = insuranceSelect.value;
        socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
        hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
        privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
    }
    if(insuranceSelect) insuranceSelect.addEventListener('change', toggleInsuranceVisibility);


    // --- Logic Block 6: Photo Cropping ---
    const cropperModal = new bootstrap.Modal(document.getElementById('cropperModal'));
    const imageToCrop = document.getElementById('imageToCrop');
    const cropImageBtn = document.getElementById('cropImageBtn');
    let cropper;
    let originalFile;

    function handleFileSelect(event) {
        if (event.target.files && event.target.files.length > 0) {
            originalFile = event.target.files[0];
        } else {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            imageToCrop.src = e.target.result;
            cropperModal.show();
        };
        reader.readAsDataURL(originalFile);
        // Clear the input value to allow re-selecting the same file
        event.target.value = '';
    }

    document.getElementById('cropperModal').addEventListener('shown.bs.modal', function () {
        // Ensure image is loaded and ready
        if (imageToCrop.complete) {
            initCropper();
        } else {
            imageToCrop.onload = initCropper;
        }
    });

    function initCropper() {
        if (typeof Cropper === 'undefined') {
            console.error('Cropper.js is not loaded.');
            return;
        }
        if (cropper) {
            cropper.destroy();
        }
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 150 / 180,
            viewMode: 1,
            dragMode: 'move',
            background: false,
            autoCropArea: 0.8,
            movable: true,
            zoomable: true,
            rotatable: true,
            scalable: true,
            cropBoxMovable: true, // Allow moving the crop box
            cropBoxResizable: true, // Allow resizing the crop box
        });
    }

    document.getElementById('cropperModal').addEventListener('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        // Also clear the src to prevent flashing old image
        imageToCrop.src = '';
    });

    if(cropImageBtn) {
        cropImageBtn.addEventListener('click', function () {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 360,
                imageSmoothingQuality: 'high',
            });

            canvas.toBlob(function (blob) {
                if (!blob) return;

                const croppedImageUrl = URL.createObjectURL(blob);
                employeePhotoPreview.src = croppedImageUrl;

                // Create a new File object
                const croppedFile = new File([blob], originalFile.name, {
                    type: originalFile.type || 'image/jpeg',
                    lastModified: Date.now()
                });

                // Use a DataTransfer to create a FileList
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);

                // Assign the FileList to the ACTUAL input for submission
                actualInput.files = dataTransfer.files;

                cropperModal.hide();

            }, originalFile.type || 'image/jpeg');
        });
    }

    if (triggerFileInput) triggerFileInput.addEventListener('change', handleFileSelect);
    if (triggerCameraInput) triggerCameraInput.addEventListener('change', handleFileSelect);


    // --- Initial State Setup on Page Load ---
    updateGender();
    calculateAge();
    toggleNationalityFields();
    toggleMouGroupOther();
    toggleInsuranceVisibility();

});
</script>
@endpush
