<form id="employeeEditForm" action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data"
    data-duplicate-check-url="{{ route('employees.check_duplicate') }}"
    data-duplicate-model-type="employee"
    data-duplicate-fields='["employeePassport","employeeWorkPermit","pinkCardNo","employee_id_number","employeeEmail"]'
    data-duplicate-exclude-id="{{ $employee->id }}"
    data-duplicate-label-title="{{ __('Duplicate data found') }}"
    data-duplicate-label-proceed="{{ __('Save anyway') }}"
    data-duplicate-label-fix="{{ __('Cancel, fix data first') }}"
    data-duplicate-label-ok="{{ __('OK') }}"
    data-duplicate-label-terminated="{{ __('Terminated') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="_previous" value="{{ old('_previous', $returnUrl ?? url()->previous()) }}">
    <input type="hidden" name="employer_id" value="{{ $employee->employer_id }}">

    {{-- Category 1: Personal Information --}}
    <h5><i class="bi bi-person-badge"></i> 1. ข้อมูลส่วนตัว (Personal Information)</h5>
    <hr class="mb-4">
    <div class="row">
        {{-- Left Column --}}
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edit_employeeTitleTh" class="form-label">{{ __('Prefix (Thai)') }}
                        @if(isset($missingFields) && in_array('employeeTitleTh', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <select class="form-select" id="edit_employeeTitleTh" name="employeeTitleTh">
                        <option value="นาย" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นาย')>นาย</option>
                        <option value="นางสาว" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นางสาว')>นางสาว</option>
                        <option value="นาง" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นาง')>นาง</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="edit_employeeNameTh" class="form-label">{{ __('Full Name (Thai)') }}
                        @if(isset($missingFields) && in_array('employeeNameTh', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="edit_employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh', $employee->employeeNameTh) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edit_height" class="form-label">{{ __('Height') }} (cm)
                        @if(isset($missingFields) && in_array('height', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="edit_height" name="height" value="{{ old('height', $employee->height) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="edit_weight" class="form-label">{{ __('Weight') }} (kg)
                        @if(isset($missingFields) && in_array('weight', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="edit_weight" name="weight" value="{{ old('weight', $employee->weight) }}">
                </div>
            </div>

            <div class="row">
                    <div class="col-md-6 mb-3">
                    <label for="edit_employeeTitleEn" class="form-label">Prefix (EN)
                        @if(isset($missingFields) && in_array('employeeTitleEn', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <select class="form-select" id="edit_employeeTitleEn" name="employeeTitleEn">
                            <option value="Mr." @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Mr.')>Mr.</option>
                            <option value="Miss" @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Miss')>Miss</option>
                            <option value="Mrs." @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Mrs.')>Mrs.</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="edit_employeeNameEn" class="form-label">Full Name (EN) <span class="text-danger">*</span>
                        @if(isset($missingFields) && in_array('employeeNameEn', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="edit_employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn', $employee->getRawOriginal('employeeNameEn')) }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edit_name_suffix" class="form-label">{{ __('Name Suffix (EN)') }}</label>
                    <input type="text" class="form-control" id="edit_name_suffix" name="name_suffix" value="{{ old('name_suffix', $employee->name_suffix) }}" placeholder="{{ __('Text appended after the English name') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">{{ __('Display Preview') }}</label>
                    <p class="form-control-plaintext text-muted mb-0">{{ ($employee->employeeTitleEn ?? '') }} {{ $employee->getRawOriginal('employeeNameEn') ?? '' }} {{ $employee->name_suffix ? '(' . $employee->name_suffix . ')' : '' }}</p>
                </div>
            </div>

                <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edit_father_name" class="form-label">{{ __("Father's Name") }}
                        @if(isset($missingFields) && in_array('father_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="edit_father_name" name="father_name" value="{{ old('father_name', $employee->father_name) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="edit_mother_name" class="form-label">{{ __("Mother's Name") }}
                        @if(isset($missingFields) && in_array('mother_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="edit_mother_name" name="mother_name" value="{{ old('mother_name', $employee->mother_name) }}">
                </div>
            </div>

            <div class="row">
                    <div class="col-md-4 mb-3">
                    <label for="edit_employeeGender" class="form-label">{{ __('Gender') }}</label>
                    <input type="text" class="form-control" id="edit_employeeGender" name="employeeGender" value="{{ old('employeeGender', $employee->employeeGender) }}" readonly>
                    </div>
                    <div class="col-md-5 mb-3">
                    <label for="edit_employeeDob" class="form-label">{{ __('Date of Birth (Full)') }}
                        @if(isset($missingFields) && in_array('employeeDob', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="date" class="form-control" id="edit_employeeDob" name="employeeDob" value="{{ old('employeeDob', optional($employee->employeeDob)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="edit_employeeAge" class="form-label">{{ __('Age') }}</label>
                    <input type="text" class="form-control" id="edit_employeeAge" name="employeeAge" value="{{ old('employeeAge', $employee->employeeAge) }}" readonly>
                </div>
            </div>
        </div>
        {{-- Right Column --}}
        <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
            <label class="form-label">{{ __('Employee Photo') }}
                @if(isset($missingFields) && in_array('employeePhoto', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <img id="edit_employeePhotoPreview" src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/150x180/f8fafc/6c757d?text=Photo' }}" class="img-thumbnail mb-3" style="width: 150px; height: 180px; object-fit: cover;">
            <div class="d-grid gap-2 w-75">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('edit_triggerFile').click();"><i class="bi bi-file-earmark-image me-1"></i> {{ __('Choose from File') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('edit_triggerCamera').click();"><i class="bi bi-camera-fill me-1"></i> {{ __('Take Photo') }}</button>
                @if($employee->employeePhoto)
                <button type="button" class="btn btn-sm btn-warning"
                        onclick="window.openCropperWithUrl('{{ asset('storage/' . $employee->employeePhoto) }}', 'edit_employeePhotoInput', 'edit_employeePhotoPreview')">
                    <i class="bi bi-pencil-square me-1"></i> {{ __('Edit Photo') }}
                </button>
                @endif
            </div>
            {{-- Hidden file inputs --}}
            <input type="file" class="d-none" id="edit_triggerFile" accept="image/*">
            <input type="file" class="d-none" id="edit_triggerCamera" accept="image/*" capture="environment">
            {{-- Actual Input for Submission --}}
            <input type="file" class="d-none" id="edit_employeePhotoInput" name="employeePhoto">
        </div>
    </div>


    {{-- Category 2: Contact & Nationality --}}
    <h5 class="mt-4"><i class="bi bi-telephone-fill"></i> 2. ข้อมูลการติดต่อและสัญชาติ (Contact & Nationality)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="edit_employeePhone" class="form-label">{{ __('Phone Number') }}
                @if(isset($missingFields) && in_array('employeePhone', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="tel" class="form-control" id="edit_employeePhone" name="employeePhone" value="{{ old('employeePhone', $employee->employeePhone) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="edit_employeeNationality" class="form-label">{{ __('Nationality') }}
                @if(isset($missingFields) && in_array('employeeNationality', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="edit_employeeNationality" name="employeeNationality">
                <option value="">{{ __('-- Select Nationality --') }}</option>
                <option value="เมียนมา" @selected(old('employeeNationality', $employee->employeeNationality) == 'เมียนมา')>เมียนมา</option>
                <option value="ลาว" @selected(old('employeeNationality', $employee->employeeNationality) == 'ลาว')>ลาว</option>
                <option value="กัมพูชา" @selected(old('employeeNationality', $employee->employeeNationality) == 'กัมพูชา')>กัมพูชา</option>
                <option value="เวียดนาม" @selected(old('employeeNationality', $employee->employeeNationality) == 'เวียดนาม')>เวียดนาม</option>
            </select>
        </div>
            <div class="col-md-4 mb-3 d-none" id="edit_passportTypeContainer">
            <label for="edit_passportType" class="form-label">{{ __('Passport Type (for Myanmar)') }}
                @if(isset($missingFields) && in_array('passportType', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="edit_passportType" name="passportType">
                <option value="">{{ __('-- Select Type --') }}</option>
                <option value="PJ" @selected(old('passportType', $employee->passportType) == 'PJ')>{{ __('PJ Book') }}</option>
                <option value="CI" @selected(old('passportType', $employee->passportType) == 'CI')>{{ __('CI Book') }}</option>
            </select>
        </div>
        <div class="col-md-4 mb-3 d-none" id="edit_passportTypeCambodiaContainer">
            <label for="edit_passport_type_cambodia" class="form-label">{{ __('Passport Type (for Cambodia)') }}
                @if(isset($missingFields) && in_array('passport_type_cambodia', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="edit_passport_type_cambodia" name="passport_type_cambodia">
                <option value="">{{ __('-- Select Type --') }}</option>
                <option value="เล่ม TD" @selected(old('passport_type_cambodia', $employee->passport_type_cambodia) == 'เล่ม TD')>{{ __('TD Book') }}</option>
                <option value="เล่มอินเตอร์" @selected(old('passport_type_cambodia', $employee->passport_type_cambodia) == 'เล่มอินเตอร์')>{{ __('International Book') }}</option>
            </select>
        </div>
    </div>

    {{-- Category 3: Passport & Visa --}}
    <h5 class="mt-4"><i class="bi bi-passport"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า (Passport & Visa)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="edit_employeePassport" class="form-label">{{ __('Passport Number (Edit)') }}
                @if(isset($missingFields) && in_array('employeePassport', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_employeePassport" name="employeePassport" value="{{ old('employeePassport', $employee->employeePassport) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_passport_issue_place" class="form-label">{{ __('Passport Issue Place') }}
                @if(isset($missingFields) && in_array('passport_issue_place', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_passport_issue_place" name="passport_issue_place" value="{{ old('passport_issue_place', $employee->passport_issue_place) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_passport_issue_date" class="form-label text-info">🔵 {{ __('Passport Issue Date') }}
                @if(isset($missingFields) && in_array('passport_issue_date', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-info" style="background-color:#f0f9ff" id="edit_passport_issue_date" name="passport_issue_date" value="{{ old('passport_issue_date', optional($employee->passport_issue_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_passportExpiryDate" class="form-label fw-bold text-danger">🔴 {{ __('Passport Expiry Date') }}
                @if(isset($missingFields) && in_array('passportExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-danger" style="background-color:#fff5f5" id="edit_passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate', optional($employee->passportExpiryDate)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="edit_pinkCardNo" class="form-label">{{ __('Pink Card ID') }}
                @if(isset($missingFields) && in_array('pinkCardNo', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo', $employee->pinkCardNo) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_visaType" class="form-label">{{ __('Visa Type') }}
                @if(isset($missingFields) && in_array('visaType', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_visaType" name="visaType" value="{{ old('visaType', $employee->visaType) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_visa_issue_place" class="form-label">{{ __('Visa Issue Place') }}
                @if(isset($missingFields) && in_array('visa_issue_place', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_visa_issue_place" name="visa_issue_place" value="{{ old('visa_issue_place', $employee->visa_issue_place) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_visaExpiryDate" class="form-label fw-bold text-danger">🔴 {{ __('Visa Expiry Date') }}
                @if(isset($missingFields) && in_array('visaExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-danger" style="background-color:#fff5f5" id="edit_visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate', optional($employee->visaExpiryDate)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_visaEndorsementDate" class="form-label text-info">🔵 {{ __('Visa Endorsement Date (Stamped)') }}
                @if(isset($missingFields) && in_array('visaEndorsementDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-info" style="background-color:#f0f9ff" id="edit_visaEndorsementDate" name="visaEndorsementDate" value="{{ old('visaEndorsementDate', optional($employee->visaEndorsementDate)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_visaEndorsementNo" class="form-label text-info">🔵 {{ __('Visa Endorsement Number') }}
                @if(isset($missingFields) && in_array('visaEndorsementNo', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control border-info" style="background-color:#f0f9ff" id="edit_visaEndorsementNo" name="visaEndorsementNo" maxlength="50" value="{{ old('visaEndorsementNo', $employee->visaEndorsementNo) }}">
        </div>
    </div>

    {{-- Category 4: Employment & Work IDs --}}
    <h5 class="mt-4"><i class="bi bi-briefcase-fill"></i> 4. ข้อมูลการจ้างงานและเอกสาร (Employment & Work IDs)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="edit_job_title" class="form-label">{{ __('Job Title') }}
                @if(isset($missingFields) && in_array('job_title', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_job_title" name="job_title" value="{{ old('job_title', $employee->job_title) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_job_description" class="form-label">{{ __('Job Description') }}
                @if(isset($missingFields) && in_array('job_description', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_job_description" name="job_description" value="{{ old('job_description', $employee->job_description) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_startDate" class="form-label">{{ __('Start Date (Employment)') }}
                @if(isset($missingFields) && in_array('startDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="edit_startDate" name="startDate" value="{{ old('startDate', optional($employee->startDate)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_workAge" class="form-label">{{ __('Work Age') }}</label>
            <input type="text" class="form-control" id="edit_workAge" value="{{ $employee->work_age }}" readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="edit_employeeWorkPermit" class="form-label">{{ __('Work Permit No. (Field)') }}
                @if(isset($missingFields) && in_array('employeeWorkPermit', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $employee->employeeWorkPermit) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_workPermitIssueDate" class="form-label text-info">🔵 {{ __('Work Permit Issue Date') }}
                @if(isset($missingFields) && in_array('workPermitIssueDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-info" style="background-color:#f0f9ff" id="edit_workPermitIssueDate" name="workPermitIssueDate" value="{{ old('workPermitIssueDate', optional($employee->workPermitIssueDate)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_workPermitExpiryDate" class="form-label fw-bold text-danger">🔴 {{ __('Work Permit Expiry Date') }}
                @if(isset($missingFields) && in_array('workPermitExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-danger" style="background-color:#fff5f5" id="edit_workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', optional($employee->workPermitExpiryDate)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="edit_ninetyDayReportDate" class="form-label fw-bold text-danger">🔴 {{ __('90-Day Report Date') }}
                @if(isset($missingFields) && in_array('ninetyDayReportDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control border-danger" style="background-color:#fff5f5" id="edit_ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', optional($employee->ninetyDayReportDate)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="row">
            <div class="col-md-6 mb-3">
            <label for="edit_workPermitMOUGroup" class="form-label">{{ __('Work Permit Type') }}
                @if(isset($missingFields) && in_array('workPermitMOUGroup', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="edit_workPermitMOUGroup" name="workPermitMOUGroup">
                <option value="">{{ __('-- Please Select --') }}</option>
                <option value="MOU" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'MOU')>MOU</option>
                <option value="MOU 2 ปีหลัง" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'MOU 2 ปีหลัง')>{{ __('MOU (2nd Year onwards)') }}</option>
                <option value="มติต่ออายุในประเทศ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติต่ออายุในประเทศ')>{{ __('Renewal Resolution (Domestic)') }}</option>
                <option value="มติขึ้นทะเบียน" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติขึ้นทะเบียน')>{{ __('Registration Resolution (MOU Group)') }}</option>
                <option value="อื่นๆ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'อื่นๆ')>{{ __('Other, specify..') }}</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 d-none" id="edit_workPermitMOUGroupOtherContainer">
            <label for="edit_workPermitMOUGroupOther" class="form-label">{{ __('Specify Other Type') }}
                @if(isset($missingFields) && in_array('workPermitMOUGroupOther', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
                <input type="text" class="form-control" id="edit_workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther', $employee->workPermitMOUGroupOther) }}">
        </div>
    </div>
    <div class="row">
            <div class="col-md-4 mb-3"><label for="edit_name_list_number" class="form-label">{{ __('RA Number (from Outsource System)') }}
                @if(isset($missingFields) && in_array('name_list_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_name_list_number" name="name_list_number" value="{{ old('name_list_number', $employee->name_list_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="edit_request_number" class="form-label">{{ __('Request Number') }}
                @if(isset($missingFields) && in_array('request_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_request_number" name="request_number" value="{{ old('request_number', $employee->request_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="edit_employee_id_number" class="form-label">{{ __('ID Number') }}
                @if(isset($missingFields) && in_array('employee_id_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_employee_id_number" name="employee_id_number" value="{{ old('employee_id_number', $employee->employee_id_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="edit_tax_id_number" class="form-label">{{ __('Tax ID Number') }}
                @if(isset($missingFields) && in_array('tax_id_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_tax_id_number" name="tax_id_number" value="{{ old('tax_id_number', $employee->tax_id_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="edit_employer_employee_id" class="form-label">{{ __("Worker Code (Employer's)") }}
                @if(isset($missingFields) && in_array('employer_employee_id', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_employer_employee_id" name="employer_employee_id" value="{{ old('employer_employee_id', $employee->employer_employee_id) }}"></div>
            <div class="col-md-4 mb-3"><label for="edit_department" class="form-label">{{ __('Department') }}
                @if(isset($missingFields) && in_array('department', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_department" name="department" value="{{ old('department', $employee->department) }}"></div>
            <div class="col-md-4 mb-3"><label for="edit_employee_reference_id" class="form-label">{{ __('Worker Reference Number') }}
                @if(isset($missingFields) && in_array('employee_reference_id', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_employee_reference_id" name="employee_reference_id" value="{{ old('employee_reference_id', $employee->employee_reference_id) }}"></div>
        <div class="col-md-4 mb-3"><label for="edit_bank_name" class="form-label">{{ __('Bank Name') }}
                @if(isset($missingFields) && in_array('bank_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_bank_name" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"></div>
        <div class="col-md-4 mb-3"><label for="edit_bank_account_number" class="form-label">{{ __('Bank Account Number') }}
                @if(isset($missingFields) && in_array('bank_account_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="edit_bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}"></div>
    </div>


    {{-- Category 5: Health Insurance --}}
    <h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="edit_insurance_type" class="form-label">{{ __('Insurance Type') }}
                @if(isset($missingFields) && in_array('insurance_type', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="edit_insurance_type" name="insurance_type">
                <option value="">{{ __('-- Select Type --') }}</option>
                <option value="ประกันสังคม" @selected(old('insurance_type', $employee->insurance_type) == 'ประกันสังคม')>{{ __('Social Security') }}</option>
                <option value="ประกันโรงพยาบาล" @selected(old('insurance_type', $employee->insurance_type) == 'ประกันโรงพยาบาล')>{{ __('Hospital Insurance') }}</option>
                <option value="ประกันเอกชน" @selected(old('insurance_type', $employee->insurance_type) == 'ประกันเอกชน')>{{ __('Private Insurance') }}</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <x-file-input-group
                id="edit_medical_certificate_path"
                name="medical_certificate_path"
                :label="__('Medical Certificate')"
                :value="$employee->medical_certificate_path"
                :pdfRoute="route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path'])"
            />
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_medical_hospital_name" class="form-label">{{ __('Hospital Name (Medical Exam)') }}</label>
            <input type="text" class="form-control" id="edit_medical_hospital_name" name="medical_hospital_name" value="{{ old('medical_hospital_name', $employee->medical_hospital_name) }}">
        </div>
    </div>

    {{-- Social Security Container --}}
    <div id="edit_insuranceSocialSecurity" class="d-none">
            <div class="row">
            <div class="col-md-4 mb-3">
                <label for="edit_social_security_number" class="form-label">{{ __('Social Security Number') }}
                    @if(isset($missingFields) && in_array('social_security_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="edit_social_security_number" name="social_security_number" value="{{ old('social_security_number', $employee->social_security_number) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="edit_insurance_detail" class="form-label">{{ __('Hospital Benefit') }}
                    @if(isset($missingFields) && in_array('insurance_detail', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="edit_insurance_detail" name="insurance_detail" value="{{ old('insurance_detail', $employee->insurance_detail) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="edit_sso_issue_date" class="form-label">{{ __('Card Issue Date') }}</label>
                <input type="date" class="form-control" id="edit_sso_issue_date" name="sso_issue_date" value="{{ old('sso_issue_date', optional($employee->sso_issue_date)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="edit_sso_expiry_date" class="form-label">{{ __('Expiry Date (Full Label)') }}</label>
                <input type="date" class="form-control" id="edit_sso_expiry_date" name="sso_expiry_date" value="{{ old('sso_expiry_date', optional($employee->sso_expiry_date)->format('Y-m-d')) }}">
            </div>
            </div>
    </div>
    {{-- Hospital Insurance Container --}}
    <div id="edit_insuranceHospital" class="d-none">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="edit_insurance_detail_hospital" class="form-label">{{ __('Hospital') }}
                    @if(isset($missingFields) && in_array('insurance_detail_hospital', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="edit_insurance_detail_hospital" name="insurance_detail_hospital" value="{{ old('insurance_detail_hospital', $employee->insurance_detail_hospital) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="edit_insurance_expiry_date_hospital" class="form-label">{{ __('Insurance Expiry Date') }}
                    @if(isset($missingFields) && in_array('insurance_expiry_date_hospital', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="date" class="form-control" id="edit_insurance_expiry_date_hospital" name="insurance_expiry_date_hospital" value="{{ old('insurance_expiry_date_hospital', optional($employee->insurance_expiry_date_hospital)->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
    {{-- Private Insurance Container --}}
    <div id="edit_insurancePrivate" class="d-none">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="edit_insurance_detail_private" class="form-label">{{ __('Insurance Company') }}
                    @if(isset($missingFields) && in_array('insurance_detail_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="edit_insurance_detail_private" name="insurance_detail_private" value="{{ old('insurance_detail_private', $employee->insurance_detail_private) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="edit_insurance_expiry_date_private" class="form-label">{{ __('Insurance Expiry Date') }}
                    @if(isset($missingFields) && in_array('insurance_expiry_date_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="date" class="form-control" id="edit_insurance_expiry_date_private" name="insurance_expiry_date_private" value="{{ old('insurance_expiry_date_private', optional($employee->insurance_expiry_date_private)->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <x-file-input-group
                id="edit_insurance_document_path_private"
                name="insurance_document_path_private"
                :label="__('Attach Insurance Document')"
                :value="$employee->insurance_document_path_private"
                :pdfRoute="route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private'])"
            />
        </div>
    </div>

    {{-- Category 6: Login Information --}}
    <h5 class="mt-4"><i class="bi bi-lock-fill"></i> 6. ข้อมูลการเข้าสู่ระบบ (Login Information)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="edit_employeeEmail" class="form-label">{{ __('Email') }}
                @if(isset($missingFields) && in_array('email', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_employeeEmail" name="employeeEmail" value="{{ old('employeeEmail', $employee->email) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="edit_password" class="form-label">{{ __('Email Password') }}
                @if(isset($missingFields) && in_array('password', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_password" name="password"
                    value="{{ $employee->password }}" placeholder="{{ __('Enter Password') }}">
            {{-- Note: type="text" creates a plain text box as requested --}}
        </div>
        <div class="col-md-4 mb-3">
            <label for="edit_outsource_code" class="form-label">{{ __('Outsource System Code') }}
                @if(isset($missingFields) && in_array('outsource_code', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="edit_outsource_code" name="outsource_code" value="{{ old('outsource_code', $employee->outsource_code) }}">
        </div>
    </div>

    {{-- Category 7: File Attachments --}}
    <h5 class="mt-4"><i class="bi bi-file-earmark-arrow-up-fill"></i> 7. ส่วนแนบไฟล์เอกสาร (File Attachments)</h5>
    <hr class="mb-4">
    @php
        $docSlots = [
            1 => __('Passport (Doc)'), 2 => __('Visa'), 3 => __('Work Permit Receipt'), 4 => __('Pink Card'),
            5 => __('House Registration (Tor.Ror.38)'), 6 => __('90-Day Report'), 7 => __('Accommodation Notification'), 8 => __('Home Country Documents'),
            9 => __('Other Document 1'), 10 => __('Other Document 2'), 11 => __('Other Document 3'), 12 => __('Other Document 4'),
            13 => __('Other Document 5'), 14 => __('Other Document 6'), 15 => __('Other Document 7'), 16 => __('Other Document 8'),
            17 => __('Other Document 9'), 18 => __('Other Document 10')
        ];
        $descSlots = [9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
    @endphp
    <div class="row">
        @foreach($docSlots as $i => $label)
            @php
                $docField = 'employee_doc_' . $i;
                $descField = 'other_doc_' . ($i - 8) . '_desc';
            @endphp
            <div class="{{ in_array($i, $descSlots) ? 'col-md-6' : 'col-md-4' }} mb-3">
                <x-file-input-group
                    :id="'edit_' . $docField"
                    :name="$docField"
                    :label="$i . '. ' . $label"
                    :value="$employee->{$docField}"
                    :pdfRoute="route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $docField])"
                    :description="in_array($i, $descSlots) ? $descField : null"
                    :descriptionValue="in_array($i, $descSlots) ? $employee->{$descField} : null"
                />
            </div>
        @endforeach
    </div>


    <div class="mt-4 d-flex justify-content-end">
        <button type="button" class="btn btn-secondary me-2 btn-cancel-edit">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Save Employee Data') }}</button>
    </div>
</form>
