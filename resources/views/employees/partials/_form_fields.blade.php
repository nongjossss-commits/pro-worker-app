{{--
    Partial: Employee Form Fields (Unified)
    Usage:
    @include('employees.partials._form_fields', [
        'prefix' => 'edit_',    // Optional: Prefix for IDs (e.g., 'edit_' or '')
        'employee' => $employee, // Optional: Employee object for Edit mode
        'employers' => $employers // Optional: For Create mode selector
    ])
--}}

@php
    $prefix = $prefix ?? '';
    // $employee is passed from parent view, or null
    // $missingFields is passed from parent view (Edit mode), or null
@endphp

{{-- Employer Selector (Only for Create Mode / No Employee) --}}
@if(!isset($employee) || !$employee)
    @if(isset($employer) && $employer)
        <input type="hidden" name="employer_id" value="{{ $employer->id }}">
    @else
        <div class="row mb-4">
            <div class="col-md-12" x-data="employerSelector()" @click.outside="open = false" @set-employer-id.window="setFromEvent($event.detail)">
                <label for="employer_id" class="form-label">เลือกนายจ้าง <span class="text-danger">*</span></label>

                {{-- Hidden Input for Form Submission --}}
                <input type="hidden" name="employer_id" :value="selectedId" required>

                {{-- Searchable Dropdown --}}
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text"
                               class="form-control"
                               placeholder="พิมพ์เพื่อค้นหาชื่อนายจ้าง..."
                               x-model="search"
                               @focus="open = true"
                               @input="open = true; selectedId = ''; selectedName = ''"
                               @keydown.escape="open = false"
                               :class="{'is-invalid': !selectedId && touched}"
                               autocomplete="off">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                    </div>

                    {{-- Dropdown List --}}
                    <div class="card position-absolute w-100 shadow-sm mt-1 border-0"
                         style="z-index: 1050; max-height: 250px; overflow-y: auto;"
                         x-show="open && filteredEmployers.length > 0"
                         x-transition>
                        <ul class="list-group list-group-flush">
                            <template x-for="emp in filteredEmployers" :key="emp.id">
                                <li class="list-group-item list-group-item-action cursor-pointer"
                                    @click="selectEmployer(emp)">
                                    <div class="fw-bold" x-text="emp.name_th"></div>
                                    <div class="small text-muted" x-text="emp.name_en"></div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- No Results --}}
                    <div class="card position-absolute w-100 shadow-sm mt-1 border-0"
                         style="z-index: 1050;"
                         x-show="open && filteredEmployers.length === 0"
                         x-transition>
                         <div class="list-group list-group-flush">
                            <div class="list-group-item text-muted text-center">ไม่พบข้อมูล</div>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $employerOptions = isset($employers) ? $employers->map(fn($e) => [
                'id' => $e->id,
                'name_th' => $e->employerNameTh,
                'name_en' => $e->employerNameEn,
                'search_str' => strtolower($e->employerNameTh . ' ' . $e->employerNameEn)
            ]) : collect([]);
        @endphp
        <script>
            function employerSelector() {
                return {
                    search: '',
                    open: false,
                    selectedId: '{{ old('employer_id') }}',
                    selectedName: '',
                    touched: false,
                    employers: @json($employerOptions),

                    init() {
                        if (this.selectedId) {
                            const found = this.employers.find(e => e.id == this.selectedId);
                            if (found) {
                                this.selectEmployer(found, false);
                            }
                        }
                    },

                    get filteredEmployers() {
                        if (this.search === '') return this.employers;
                        const term = this.search.toLowerCase();
                        return this.employers.filter(e => e.search_str.includes(term));
                    },

                    selectEmployer(emp, close = true) {
                        this.selectedId = emp.id;
                        this.search = emp.name_th; // Show name in input
                        this.open = false;
                        this.touched = true;
                    },

                    setFromEvent(detail) {
                        if (detail && detail.id) {
                            this.selectedId = detail.id;
                            // Find name to set search
                            const found = this.employers.find(e => e.id == detail.id);
                            if(found) this.search = found.name_th;
                        } else {
                            this.selectedId = '';
                            this.search = '';
                        }
                    }
                }
            }
        </script>
    @endif

    @if(request()->has('employer_id'))
        <input type="hidden" name="source_employer_id" value="{{ request('employer_id') }}">
    @endif

@else
    {{-- Edit Mode: Keep Employer ID Hidden --}}
    <input type="hidden" name="employer_id" value="{{ $employee->employer_id }}">
    <input type="hidden" name="_previous" value="{{ url()->previous() }}">
@endif


{{-- Category 1: Personal Information --}}
<h5><i class="bi bi-person-badge"></i> 1. ข้อมูลส่วนตัว (Personal Information)</h5>
<hr class="mb-4">
<div class="row">
    {{-- Left Column --}}
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="{{ $prefix }}employeeTitleTh" class="form-label">คำนำหน้าชื่อ (ไทย)
                    @if(isset($missingFields) && in_array('employeeTitleTh', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <select class="form-select" id="{{ $prefix }}employeeTitleTh" name="employeeTitleTh">
                    <option value="นาย" @selected(old('employeeTitleTh', $employee->employeeTitleTh ?? '') == 'นาย')>นาย</option>
                    <option value="นางสาว" @selected(old('employeeTitleTh', $employee->employeeTitleTh ?? '') == 'นางสาว')>นางสาว</option>
                    <option value="นาง" @selected(old('employeeTitleTh', $employee->employeeTitleTh ?? '') == 'นาง')>นาง</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="{{ $prefix }}employeeNameTh" class="form-label">ชื่อ-สกุล (ไทย)
                    @if(isset($missingFields) && in_array('employeeNameTh', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="{{ $prefix }}employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh', $employee->employeeNameTh ?? '') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="{{ $prefix }}employeeTitleEn" class="form-label">Prefix (EN)
                    @if(isset($missingFields) && in_array('employeeTitleEn', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <select class="form-select" id="{{ $prefix }}employeeTitleEn" name="employeeTitleEn">
                    <option value="Mr." @selected(old('employeeTitleEn', $employee->employeeTitleEn ?? '') == 'Mr.')>Mr.</option>
                    <option value="Miss" @selected(old('employeeTitleEn', $employee->employeeTitleEn ?? '') == 'Miss')>Miss</option>
                    <option value="Mrs." @selected(old('employeeTitleEn', $employee->employeeTitleEn ?? '') == 'Mrs.')>Mrs.</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="{{ $prefix }}employeeNameEn" class="form-label">Full Name (EN) <span class="text-danger">*</span>
                    @if(isset($missingFields) && in_array('employeeNameEn', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="{{ $prefix }}employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn', $employee->employeeNameEn ?? '') }}" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="{{ $prefix }}father_name" class="form-label">ชื่อพ่อ
                    @if(isset($missingFields) && in_array('father_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="{{ $prefix }}father_name" name="father_name" value="{{ old('father_name', $employee->father_name ?? '') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="{{ $prefix }}mother_name" class="form-label">ชื่อแม่
                    @if(isset($missingFields) && in_array('mother_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="{{ $prefix }}mother_name" name="mother_name" value="{{ old('mother_name', $employee->mother_name ?? '') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="{{ $prefix }}employeeGender" class="form-label">เพศ</label>
                <input type="text" class="form-control" id="{{ $prefix }}employeeGender" name="employeeGender" value="{{ old('employeeGender', $employee->employeeGender ?? '') }}" readonly>
            </div>
            <div class="col-md-5 mb-3">
                <label for="{{ $prefix }}employeeDob" class="form-label">วันเดือนปีเกิด
                    @if(isset($missingFields) && in_array('employeeDob', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="date" class="form-control" id="{{ $prefix }}employeeDob" name="employeeDob" value="{{ old('employeeDob', isset($employee->employeeDob) ? $employee->employeeDob->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label for="{{ $prefix }}employeeAge" class="form-label">อายุ</label>
                <input type="text" class="form-control" id="{{ $prefix }}employeeAge" name="employeeAge" value="{{ old('employeeAge', $employee->employeeAge ?? '') }}" readonly>
            </div>
        </div>
    </div>
    {{-- Right Column --}}
    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
        <label class="form-label">รูปภาพพนักงาน
            @if(isset($missingFields) && in_array('employeePhoto', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        @php
            $photoSrc = (isset($employee) && $employee->employeePhoto)
                ? asset('storage/' . $employee->employeePhoto)
                : 'https://placehold.co/150x180/f8fafc/6c757d?text=Photo';
        @endphp
        <img id="{{ $prefix }}employeePhotoPreview" src="{{ $photoSrc }}" class="img-thumbnail mb-3" style="width: 150px; height: 180px; object-fit: cover;">
        <div class="d-grid gap-2 w-75">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('{{ $prefix }}triggerFile').click();"><i class="bi bi-file-earmark-image me-1"></i> เลือกจากไฟล์</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('{{ $prefix }}triggerCamera').click();"><i class="bi bi-camera-fill me-1"></i> ถ่ายภาพ</button>

            {{-- Edit Button (Show only if photo exists) --}}
            @if(isset($employee) && $employee->employeePhoto)
            <button type="button" class="btn btn-sm btn-warning"
                    onclick="window.openCropperWithUrl('{{ asset('storage/' . $employee->employeePhoto) }}', '{{ $prefix }}employeePhotoInput', '{{ $prefix }}employeePhotoPreview')">
                <i class="bi bi-pencil-square me-1"></i> แก้ไขรูปภาพ
            </button>
            @endif
        </div>
        {{-- Hidden file inputs --}}
        <input type="file" class="d-none" id="{{ $prefix }}triggerFile" accept="image/*">
        <input type="file" class="d-none" id="{{ $prefix }}triggerCamera" accept="image/*" capture="environment">
        {{-- Actual Input for Submission --}}
        <input type="file" class="d-none" id="{{ $prefix }}employeePhotoInput" name="employeePhoto">
    </div>
</div>


{{-- Category 2: Contact & Nationality --}}
<h5 class="mt-4"><i class="bi bi-telephone-fill"></i> 2. ข้อมูลการติดต่อและสัญชาติ (Contact & Nationality)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}employeePhone" class="form-label">เบอร์โทรศัพท์
            @if(isset($missingFields) && in_array('employeePhone', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="tel" class="form-control" id="{{ $prefix }}employeePhone" name="employeePhone" value="{{ old('employeePhone', $employee->employeePhone ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}employeeNationality" class="form-label">สัญชาติ
            @if(isset($missingFields) && in_array('employeeNationality', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <select class="form-select" id="{{ $prefix }}employeeNationality" name="employeeNationality">
            <option value="">-- เลือกสัญชาติ --</option>
            <option value="เมียนมา" @selected(old('employeeNationality', $employee->employeeNationality ?? '') == 'เมียนมา')>เมียนมา</option>
            <option value="ลาว" @selected(old('employeeNationality', $employee->employeeNationality ?? '') == 'ลาว')>ลาว</option>
            <option value="กัมพูชา" @selected(old('employeeNationality', $employee->employeeNationality ?? '') == 'กัมพูชา')>กัมพูชา</option>
            <option value="เวียดนาม" @selected(old('employeeNationality', $employee->employeeNationality ?? '') == 'เวียดนาม')>เวียดนาม</option>
        </select>
    </div>
    <div class="col-md-4 mb-3 d-none" id="{{ $prefix }}passportTypeContainer">
        <label for="{{ $prefix }}passportType" class="form-label">ประเภทหนังสือเดินทาง (สำหรับเมียนมา)
            @if(isset($missingFields) && in_array('passportType', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <select class="form-select" id="{{ $prefix }}passportType" name="passportType">
            <option value="">-- เลือกประเภท --</option>
            <option value="PJ" @selected(old('passportType', $employee->passportType ?? '') == 'PJ')>เล่ม PJ</option>
            <option value="CI" @selected(old('passportType', $employee->passportType ?? '') == 'CI')>เล่ม CI</option>
        </select>
    </div>
    <div class="col-md-4 mb-3 d-none" id="{{ $prefix }}passportTypeCambodiaContainer">
        <label for="{{ $prefix }}passport_type_cambodia" class="form-label">ประเภทหนังสือเดินทาง (สำหรับกัมพูชา)
            @if(isset($missingFields) && in_array('passport_type_cambodia', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <select class="form-select" id="{{ $prefix }}passport_type_cambodia" name="passport_type_cambodia">
            <option value="">-- เลือกประเภท --</option>
            <option value="เล่ม TD" @selected(old('passport_type_cambodia', $employee->passport_type_cambodia ?? '') == 'เล่ม TD')>เล่ม TD</option>
            <option value="เล่มอินเตอร์" @selected(old('passport_type_cambodia', $employee->passport_type_cambodia ?? '') == 'เล่มอินเตอร์')>เล่มอินเตอร์</option>
        </select>
    </div>
</div>

{{-- Category 3: Passport & Visa --}}
<h5 class="mt-4"><i class="bi bi-passport"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า (Passport & Visa)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}employeePassport" class="form-label">เลขพาสปอร์ต
            @if(isset($missingFields) && in_array('employeePassport', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}employeePassport" name="employeePassport" value="{{ old('employeePassport', $employee->employeePassport ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}passport_issue_date" class="form-label">วันออกพาสปอร์ต
            @if(isset($missingFields) && in_array('passport_issue_date', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="date" class="form-control" id="{{ $prefix }}passport_issue_date" name="passport_issue_date" value="{{ old('passport_issue_date', isset($employee->passport_issue_date) ? $employee->passport_issue_date->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต
            @if(isset($missingFields) && in_array('passportExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="date" class="form-control" id="{{ $prefix }}passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate', isset($employee->passportExpiryDate) ? $employee->passportExpiryDate->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}pinkCardNo" class="form-label">เลขบัตรชมพู
            @if(isset($missingFields) && in_array('pinkCardNo', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo', $employee->pinkCardNo ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}visaType" class="form-label">ประเภทวีซ่า
            @if(isset($missingFields) && in_array('visaType', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}visaType" name="visaType" value="{{ old('visaType', $employee->visaType ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}visaExpiryDate" class="form-label">วันหมดอายุวีซ่า
            @if(isset($missingFields) && in_array('visaExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="date" class="form-control" id="{{ $prefix }}visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate', isset($employee->visaExpiryDate) ? $employee->visaExpiryDate->format('Y-m-d') : '') }}">
    </div>
</div>

{{-- Category 4: Employment & Work IDs --}}
<h5 class="mt-4"><i class="bi bi-briefcase-fill"></i> 4. ข้อมูลการจ้างงานและเอกสาร (Employment & Work IDs)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}job_title" class="form-label">ตำแหน่งงาน
            @if(isset($missingFields) && in_array('job_title', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}job_title" name="job_title" value="{{ old('job_title', $employee->job_title ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}job_description" class="form-label">ลักษณะงาน
            @if(isset($missingFields) && in_array('job_description', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}job_description" name="job_description" value="{{ old('job_description', $employee->job_description ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}startDate" class="form-label">วันที่เริ่มงาน
            @if(isset($missingFields) && in_array('startDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="date" class="form-control" id="{{ $prefix }}startDate" name="startDate" value="{{ old('startDate', isset($employee->startDate) ? $employee->startDate->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}employeeWorkPermit" class="form-label">เลข Work Permit
            @if(isset($missingFields) && in_array('employeeWorkPermit', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $employee->employeeWorkPermit ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}workPermitExpiryDate" class="form-label">วันหมดอายุ Work Permit
            @if(isset($missingFields) && in_array('workPermitExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="date" class="form-control" id="{{ $prefix }}workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', isset($employee->workPermitExpiryDate) ? $employee->workPermitExpiryDate->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}ninetyDayReportDate" class="form-label">วันรายงานตัว 90 วัน
            @if(isset($missingFields) && in_array('ninetyDayReportDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="date" class="form-control" id="{{ $prefix }}ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', isset($employee->ninetyDayReportDate) ? $employee->ninetyDayReportDate->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="{{ $prefix }}workPermitMOUGroup" class="form-label">ประเภทใบอนุญาตทำงาน
            @if(isset($missingFields) && in_array('workPermitMOUGroup', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <select class="form-select" id="{{ $prefix }}workPermitMOUGroup" name="workPermitMOUGroup">
            <option value="">-- กรุณาเลือก --</option>
            <option value="MOU" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup ?? '') == 'MOU')>MOU</option>
            <option value="มติต่ออายุในประเทศ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup ?? '') == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option>
            <option value="มติขึ้นทะเบียน" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup ?? '') == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option>
            <option value="อื่นๆ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup ?? '') == 'อื่นๆ')>อื่นๆ ระบุ..</option>
        </select>
    </div>
    <div class="col-md-6 mb-3 d-none" id="{{ $prefix }}workPermitMOUGroupOtherContainer">
        <label for="{{ $prefix }}workPermitMOUGroupOther" class="form-label">ระบุประเภทอื่นๆ
            @if(isset($missingFields) && in_array('workPermitMOUGroupOther', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther', $employee->workPermitMOUGroupOther ?? '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}name_list_number" class="form-label">เลข RA จากระบบ outsource
        @if(isset($missingFields) && in_array('name_list_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}name_list_number" name="name_list_number" value="{{ old('name_list_number', $employee->name_list_number ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}request_number" class="form-label">เลขที่คำขอ
        @if(isset($missingFields) && in_array('request_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}request_number" name="request_number" value="{{ old('request_number', $employee->request_number ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}employee_id_number" class="form-label">เลขประจำตัว
        @if(isset($missingFields) && in_array('employee_id_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}employee_id_number" name="employee_id_number" value="{{ old('employee_id_number', $employee->employee_id_number ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}tax_id_number" class="form-label">เลขประจำตัวผู้เสียภาษี
        @if(isset($missingFields) && in_array('tax_id_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}tax_id_number" name="tax_id_number" value="{{ old('tax_id_number', $employee->tax_id_number ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}employer_employee_id" class="form-label">รหัสคนงาน - ของนายจ้าง
        @if(isset($missingFields) && in_array('employer_employee_id', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}employer_employee_id" name="employer_employee_id" value="{{ old('employer_employee_id', $employee->employer_employee_id ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}employee_reference_id" class="form-label">เลขอ้างอิงคนงาน
        @if(isset($missingFields) && in_array('employee_reference_id', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}employee_reference_id" name="employee_reference_id" value="{{ old('employee_reference_id', $employee->employee_reference_id ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}bank_name" class="form-label">ชื่อธนาคาร
        @if(isset($missingFields) && in_array('bank_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}bank_name" name="bank_name" value="{{ old('bank_name', $employee->bank_name ?? '') }}"></div>
    <div class="col-md-4 mb-3"><label for="{{ $prefix }}bank_account_number" class="form-label">เลขบัญชีธนาคาร
        @if(isset($missingFields) && in_array('bank_account_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
    </label><input type="text" class="form-control" id="{{ $prefix }}bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number ?? '') }}"></div>
</div>


{{-- Category 5: Health Insurance --}}
<h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}insurance_type" class="form-label">ประเภทประกัน
            @if(isset($missingFields) && in_array('insurance_type', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <select class="form-select" id="{{ $prefix }}insurance_type" name="insurance_type">
            <option value="">-- เลือกประเภท --</option>
            <option value="ประกันสังคม" @selected(old('insurance_type', $employee->insurance_type ?? '') == 'ประกันสังคม')>ประกันสังคม</option>
            <option value="ประกันโรงพยาบาล" @selected(old('insurance_type', $employee->insurance_type ?? '') == 'ประกันโรงพยาบาล')>ประกันโรงพยาบาล</option>
            <option value="ประกันเอกชน" @selected(old('insurance_type', $employee->insurance_type ?? '') == 'ประกันเอกชน')>ประกันเอกชน</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <x-file-input-group
            id="{{ $prefix }}medical_certificate_path"
            name="medical_certificate_path"
            label="ใบรับรองแพทย์ (Medical Certificate)"
            :value="$employee->medical_certificate_path ?? null"
            :pdfRoute="isset($employee) ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path']) : null"
        />
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $prefix }}medical_hospital_name" class="form-label">โรงพยาบาลที่ตรวจโรค (Hospital Name)</label>
        <input type="text" class="form-control" id="{{ $prefix }}medical_hospital_name" name="medical_hospital_name" value="{{ old('medical_hospital_name', $employee->medical_hospital_name ?? '') }}">
    </div>
</div>

{{-- Social Security Container --}}
<div id="{{ $prefix }}insuranceSocialSecurity" class="d-none">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="{{ $prefix }}social_security_number" class="form-label">เลขประกันสังคม
                @if(isset($missingFields) && in_array('social_security_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="{{ $prefix }}social_security_number" name="social_security_number" value="{{ old('social_security_number', $employee->social_security_number ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="{{ $prefix }}insurance_detail" class="form-label">สิทธิ์โรงพยาบาล
                @if(isset($missingFields) && in_array('insurance_detail', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="{{ $prefix }}insurance_detail" name="insurance_detail" value="{{ old('insurance_detail', $employee->insurance_detail ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <x-file-input-group
                id="{{ $prefix }}insurance_document_path_social"
                name="insurance_document_path_social"
                label="แนบไฟล์เอกสารประกัน"
                :value="$employee->insurance_document_path ?? null"
                :pdfRoute="isset($employee) ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path']) : null"
            />
        </div>
    </div>
</div>
{{-- Hospital Insurance Container --}}
<div id="{{ $prefix }}insuranceHospital" class="d-none">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="{{ $prefix }}insurance_detail_hospital" class="form-label">ชื่อโรงพยาบาล
                @if(isset($missingFields) && in_array('insurance_detail_hospital', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="{{ $prefix }}insurance_detail_hospital" name="insurance_detail_hospital" value="{{ old('insurance_detail_hospital', $employee->insurance_detail_hospital ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="{{ $prefix }}insurance_expiry_date_hospital" class="form-label">วันหมดอายุประกัน
                @if(isset($missingFields) && in_array('insurance_expiry_date_hospital', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="{{ $prefix }}insurance_expiry_date_hospital" name="insurance_expiry_date_hospital" value="{{ old('insurance_expiry_date_hospital', isset($employee->insurance_expiry_date_hospital) ? $employee->insurance_expiry_date_hospital->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <x-file-input-group
                id="{{ $prefix }}insurance_document_path_hospital"
                name="insurance_document_path_hospital"
                label="แนบไฟล์เอกสารประกัน"
                :value="$employee->insurance_document_path ?? null"
                :pdfRoute="isset($employee) ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path']) : null"
            />
        </div>
    </div>
</div>
{{-- Private Insurance Container --}}
<div id="{{ $prefix }}insurancePrivate" class="d-none">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="{{ $prefix }}insurance_detail_private" class="form-label">บริษัทประกัน
                @if(isset($missingFields) && in_array('insurance_detail_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="{{ $prefix }}insurance_detail_private" name="insurance_detail_private" value="{{ old('insurance_detail_private', $employee->insurance_detail_private ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="{{ $prefix }}insurance_expiry_date_private" class="form-label">วันหมดอายุประกัน
                @if(isset($missingFields) && in_array('insurance_expiry_date_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="{{ $prefix }}insurance_expiry_date_private" name="insurance_expiry_date_private" value="{{ old('insurance_expiry_date_private', isset($employee->insurance_expiry_date_private) ? $employee->insurance_expiry_date_private->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <x-file-input-group
                id="{{ $prefix }}insurance_document_path_private"
                name="insurance_document_path_private"
                label="แนบไฟล์เอกสารประกัน"
                :value="$employee->insurance_document_path_private ?? null"
                :pdfRoute="isset($employee) ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private']) : null"
            />
        </div>
    </div>
</div>

{{-- Category 6: Login Information --}}
<h5 class="mt-4"><i class="bi bi-lock-fill"></i> 6. ข้อมูลการเข้าสู่ระบบ (Login Information)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}employeeEmail" class="form-label">อีเมล
            @if(isset($missingFields) && in_array('email', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}employeeEmail" name="employeeEmail" value="{{ old('employeeEmail', $employee->email ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}password" class="form-label">รหัสสำหรับอีเมล
            @if(isset($missingFields) && in_array('password', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        {{-- Display current password for editing (plain text as per requirement) --}}
        <input type="text" class="form-control" id="{{ $prefix }}password" name="password"
                value="{{ $employee->password ?? '' }}" placeholder="กรอกรหัสผ่าน">
    </div>
    <div class="col-md-4 mb-3">
        <label for="{{ $prefix }}outsource_code" class="form-label">รหัสสำหรับระบบ Outsource
            @if(isset($missingFields) && in_array('outsource_code', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
        </label>
        <input type="text" class="form-control" id="{{ $prefix }}outsource_code" name="outsource_code" value="{{ old('outsource_code', $employee->outsource_code ?? '') }}">
    </div>
</div>

{{-- Category 7: File Attachments --}}
<h5 class="mt-4"><i class="bi bi-file-earmark-arrow-up-fill"></i> 7. ส่วนแนบไฟล์เอกสาร (File Attachments)</h5>
<hr class="mb-4">
@php
    $docSlots = [
        1 => '1. พาสปอร์ต', 2 => '2. วีซ่า', 3 => '3. ใบเสร็จ Work Permit', 4 => '4. บัตรชมพู',
        5 => '5. ทร. 38', 6 => '6. รายงานตัว 90 วัน', 7 => '7. ใบแจ้งที่พักอาศัย', 8 => '8. เอกสารบ้านเกิด',
        9 => '9. เอกสารอื่นๆ 1', 10 => '10. เอกสารอื่นๆ 2', 11 => '11. เอกสารอื่นๆ 3', 12 => '12. เอกสารอื่นๆ 4',
        13 => '13. เอกสารอื่นๆ 5', 14 => '14. เอกสารอื่นๆ 6', 15 => '15. เอกสารอื่นๆ 7', 16 => '16. เอกสารอื่นๆ 8',
        17 => '17. เอกสารอื่นๆ 9', 18 => '18. เอกสารอื่นๆ 10'
    ];
    $descSlots = [9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
@endphp
<div class="row">
    @foreach($docSlots as $i => $label)
        @php
            $docField = 'employee_doc_' . $i;
            $descField = 'other_doc_' . ($i - 8) . '_desc';
            // Determine loop values for description
            $descValue = isset($employee) && in_array($i, $descSlots) ? $employee->{$descField} : null;
        @endphp
        <div class="{{ in_array($i, $descSlots) ? 'col-md-6' : 'col-md-4' }} mb-3">
            <x-file-input-group
                :id="$prefix . $docField"
                :name="$docField"
                :label="$label"
                :value="$employee->{$docField} ?? null"
                :pdfRoute="(isset($employee) && $employee->{$docField}) ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $docField]) : null"
                :description="in_array($i, $descSlots) ? $prefix . $descField : null"
                :descriptionValue="$descValue"
            />
        </div>
    @endforeach
</div>

@if(isset($employee))
<div class="mt-4 d-flex justify-content-end">
    <button type="button" class="btn btn-secondary me-2 btn-cancel-edit">ยกเลิก</button>
    <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน</button>
</div>
@endif
