@extends('layouts.app')

@section('title', 'เพิ่มพนักงานใหม่')

@section('content')
<div class="content-section">
    @if(isset($employer) && $employer)
        <h2 class="mb-4">เพิ่มพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>
    @else
        <h2 class="mb-4">เพิ่มพนักงานใหม่</h2>
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

    <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @if(isset($employer) && $employer)
            <input type="hidden" name="source_employer_id" value="{{ $employer->id }}">
        @endif

        @include('employees.partials.create_form_partial_content')

        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ isset($employer) ? route('employers.edit', $employer->id) : route('employees.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน</button>
        </div>
    </form>
</div>

<x-cropper-modal />
@endsection

@push('scripts')
@include('employees.partials._cropper_scripts')
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

    if (titleTh) titleTh.addEventListener('change', () => syncTitles('th'));
    if (titleEn) titleEn.addEventListener('change', () => syncTitles('en'));


    // --- Logic Block 2: Age Calculation ---
    function calculateAge() {
        if (!dobInput || !ageInput) return;
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
    if (dobInput) dobInput.addEventListener('change', calculateAge);


    // --- Logic Block 3: Nationality Conditional Fields ---
    function toggleNationalityFields() {
        if (!nationalitySelect) return;
        // Myanmar
        if (myanmarPassportContainer) myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
        // Cambodia
        if (cambodiaPassportContainer) cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
    }
    if (nationalitySelect) nationalitySelect.addEventListener('change', toggleNationalityFields);


    // --- Logic Block 4: MOU "Other" Field ---
     function toggleMouGroupOther() {
        if (!mouGroupSelect) return;
        if (mouGroupOtherContainer) mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
    }
    if (mouGroupSelect) mouGroupSelect.addEventListener('change', toggleMouGroupOther);


    // --- V6: Logic Block 5: Insurance Conditional Fields ---
    function toggleInsuranceVisibility() {
        if (!insuranceSelect) return;
        const selectedType = insuranceSelect.value;
        if (socialContainer) socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
        if (hospitalContainer) hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
        if (privateContainer) privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
    }
    if (insuranceSelect) insuranceSelect.addEventListener('change', toggleInsuranceVisibility);


    // --- Logic Block 6: Photo Cropping ---
    function handleFileSelect(event) {
        if (event.target.files && event.target.files.length > 0) {
            window.cropperManager.openWithFile(event.target.files[0], 'employeePhotoInput', 'employeePhotoPreview');
        }
        // Clear the input value to allow re-selecting the same file
        event.target.value = '';
    }

    if (triggerFileInput) triggerFileInput.addEventListener('change', handleFileSelect);
    if (triggerCameraInput) triggerCameraInput.addEventListener('change', handleFileSelect);


    // --- Initial State Setup on Page Load ---
    if (titleTh) updateGender();
    if (dobInput) calculateAge();
    if (nationalitySelect) toggleNationalityFields();
    if (mouGroupSelect) toggleMouGroupOther();
    if (insuranceSelect) toggleInsuranceVisibility();

});
</script>
@endpush
