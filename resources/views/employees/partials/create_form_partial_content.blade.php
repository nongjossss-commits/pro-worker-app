{{--
    Partial: Employee Create Form Content
    Usage: @include('employees.partials.create_form_partial_content')
    Note: Requires $employers variable. $employer variable is optional.
--}}

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

{{-- Category 1: Personal Information --}}
<h5><i class="bi bi-person-badge"></i> 1. ข้อมูลส่วนตัว (Personal Information)</h5>
<hr class="mb-4">
<div class="row">
    {{-- Left Column --}}
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="employeeTitleTh" class="form-label">คำนำหน้าชื่อ (ไทย)</label>
                <select class="form-select" id="employeeTitleTh" name="employeeTitleTh">
                    <option value="นาย" {{ old('employeeTitleTh') == 'นาย' ? 'selected' : '' }}>นาย</option>
                    <option value="นางสาว" {{ old('employeeTitleTh') == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                    <option value="นาง" {{ old('employeeTitleTh') == 'นาง' ? 'selected' : '' }}>นาง</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="employeeNameTh" class="form-label">ชื่อ-สกุล (ไทย)</label>
                <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh') }}">
            </div>
        </div>

        <div class="row">
                <div class="col-md-6 mb-3">
                <label for="employeeTitleEn" class="form-label">Prefix (EN)</label>
                <select class="form-select" id="employeeTitleEn" name="employeeTitleEn">
                        <option value="Mr." {{ old('employeeTitleEn') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Miss" {{ old('employeeTitleEn') == 'Miss' ? 'selected' : '' }}>Miss</option>
                        <option value="Mrs." {{ old('employeeTitleEn') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="employeeNameEn" class="form-label">Full Name (EN) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn') }}" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name_suffix" class="form-label">ต่อท้ายชื่อ (EN)</label>
                <input type="text" class="form-control" id="name_suffix" name="name_suffix" value="{{ old('name_suffix') }}" placeholder="ข้อความต่อท้ายชื่อภาษาอังกฤษ">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="height" class="form-label">{{ __('Height') }} (cm)</label>
                <input type="text" class="form-control" id="height" name="height" value="{{ old('height') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="weight" class="form-label">{{ __('Weight') }} (kg)</label>
                <input type="text" class="form-control" id="weight" name="weight" value="{{ old('weight') }}">
            </div>
        </div>

            <div class="row">
            <div class="col-md-6 mb-3">
                <label for="father_name" class="form-label">ชื่อพ่อ</label>
                <input type="text" class="form-control" id="father_name" name="father_name" value="{{ old('father_name') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="mother_name" class="form-label">ชื่อแม่</label>
                <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ old('mother_name') }}">
            </div>
        </div>

        <div class="row">
                <div class="col-md-4 mb-3">
                <label for="employeeGender" class="form-label">เพศ</label>
                <input type="text" class="form-control" id="employeeGender" name="employeeGender" value="{{ old('employeeGender') }}" readonly>
                </div>
                <div class="col-md-5 mb-3">
                <label for="employeeDob" class="form-label">วันเดือนปีเกิด</label>
                <input type="date" class="form-control" id="employeeDob" name="employeeDob" value="{{ old('employeeDob') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label for="employeeAge" class="form-label">อายุ</label>
                <input type="text" class="form-control" id="employeeAge" name="employeeAge" value="{{ old('employeeAge') }}" readonly>
            </div>
        </div>
    </div>
    {{-- Right Column --}}
    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
        <label class="form-label">รูปภาพพนักงาน</label>
        <img id="employeePhotoPreview" src="https://placehold.co/150x180/f8fafc/6c757d?text=Photo" class="img-thumbnail mb-3" style="width: 150px; height: 180px; object-fit: cover;">
        <div class="d-grid gap-2 w-75">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('triggerFile').click();"><i class="bi bi-file-earmark-image me-1"></i> เลือกจากไฟล์</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('triggerCamera').click();"><i class="bi bi-camera-fill me-1"></i> ถ่ายภาพ</button>
        </div>
        {{-- Hidden file inputs --}}
        <input type="file" class="d-none" id="triggerFile" accept="image/*">
        <input type="file" class="d-none" id="triggerCamera" accept="image/*" capture="environment">
        {{-- Actual Input for Submission --}}
        <input type="file" class="d-none" id="employeePhotoInput" name="employeePhoto">
    </div>
</div>


{{-- Category 2: Contact & Nationality --}}
<h5 class="mt-4"><i class="bi bi-telephone-fill"></i> 2. ข้อมูลการติดต่อและสัญชาติ (Contact & Nationality)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="employeePhone" class="form-label">เบอร์โทรศัพท์</label>
        <input type="tel" class="form-control" id="employeePhone" name="employeePhone" value="{{ old('employeePhone') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="employeeNationality" class="form-label">สัญชาติ</label>
        <select class="form-select" id="employeeNationality" name="employeeNationality">
            <option value="">-- เลือกสัญชาติ --</option>
            <option value="เมียนมา" {{ old('employeeNationality') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
            <option value="ลาว" {{ old('employeeNationality') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
            <option value="กัมพูชา" {{ old('employeeNationality') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
            <option value="เวียดนาม" {{ old('employeeNationality') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
        </select>
    </div>
        <div class="col-md-4 mb-3 d-none" id="passportTypeContainer">
        <label for="passportType" class="form-label">ประเภทหนังสือเดินทาง (สำหรับเมียนมา)</label>
        <select class="form-select" id="passportType" name="passportType">
            <option value="">-- เลือกประเภท --</option>
            <option value="PJ" {{ old('passportType') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
            <option value="CI" {{ old('passportType') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
        </select>
    </div>
    <div class="col-md-4 mb-3 d-none" id="passportTypeCambodiaContainer">
        <label for="passport_type_cambodia" class="form-label">ประเภทหนังสือเดินทาง (สำหรับกัมพูชา)</label>
        <select class="form-select" id="passport_type_cambodia" name="passport_type_cambodia">
            <option value="">-- เลือกประเภท --</option>
            <option value="เล่ม TD" {{ old('passport_type_cambodia') == 'เล่ม TD' ? 'selected' : '' }}>เล่ม TD</option>
            <option value="เล่มอินเตอร์" {{ old('passport_type_cambodia') == 'เล่มอินเตอร์' ? 'selected' : '' }}>เล่มอินเตอร์</option>
        </select>
    </div>
</div>

{{-- Category 3: Passport & Visa --}}
<h5 class="mt-4"><i class="bi bi-passport"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า (Passport & Visa)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-3 mb-3">
        <label for="employeePassport" class="form-label">เลขพาสปอร์ต</label>
        <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="passport_issue_place" class="form-label">{{ __('Passport Issue Place') }}</label>
        <input type="text" class="form-control" id="passport_issue_place" name="passport_issue_place" value="{{ old('passport_issue_place') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="passport_issue_date" class="form-label">วันออกพาสปอร์ต</label>
        <input type="date" class="form-control" id="passport_issue_date" name="passport_issue_date" value="{{ old('passport_issue_date') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต</label>
        <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-3 mb-3">
        <label for="pinkCardNo" class="form-label">เลขบัตรชมพู</label>
        <input type="text" class="form-control" id="pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="visaType" class="form-label">ประเภทวีซ่า</label>
        <input type="text" class="form-control" id="visaType" name="visaType" value="{{ old('visaType') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="visa_issue_place" class="form-label">{{ __('Visa Issue Place') }}</label>
        <input type="text" class="form-control" id="visa_issue_place" name="visa_issue_place" value="{{ old('visa_issue_place') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="visaExpiryDate" class="form-label fw-bold text-danger">🔴 วันหมดอายุวีซ่า</label>
        <input type="date" class="form-control border-danger" style="background-color:#fff5f5" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="visaEndorsementDate" class="form-label text-info">🔵 วันที่ตรวจลงตราวีซ่า (ประทับ)</label>
        <input type="date" class="form-control border-info" style="background-color:#f0f9ff" id="visaEndorsementDate" name="visaEndorsementDate" value="{{ old('visaEndorsementDate') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="visaEndorsementNo" class="form-label text-info">🔵 เลขที่ตรวจลงตราวีซ่า</label>
        <input type="text" class="form-control border-info" style="background-color:#f0f9ff" id="visaEndorsementNo" name="visaEndorsementNo" maxlength="50" value="{{ old('visaEndorsementNo') }}">
    </div>
</div>

{{-- Category 4: Employment & Work IDs --}}
<h5 class="mt-4"><i class="bi bi-briefcase-fill"></i> 4. ข้อมูลการจ้างงานและเอกสาร (Employment & Work IDs)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-3 mb-3">
        <label for="job_title" class="form-label">ตำแหน่งงาน</label>
        <input type="text" class="form-control" id="job_title" name="job_title" value="{{ old('job_title') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="job_description" class="form-label">ลักษณะงาน</label>
        <input type="text" class="form-control" id="job_description" name="job_description" value="{{ old('job_description') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="startDate" class="form-label">วันที่เริ่มงาน</label>
        <input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="workAge" class="form-label">{{ __('Work Age') }}</label>
        <input type="text" class="form-control" id="workAge" value="" readonly>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="employeeWorkPermit" class="form-label">เลข Work Permit</label>
        <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="workPermitExpiryDate" class="form-label">วันหมดอายุ Work Permit</label>
        <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate') }}">
    </div>
        <div class="col-md-4 mb-3">
        <label for="ninetyDayReportDate" class="form-label">วันรายงานตัว 90 วัน</label>
        <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate') }}">
    </div>
</div>
<div class="row">
        <div class="col-md-6 mb-3">
        <label for="workPermitType" class="form-label">ประเภทใบอนุญาตทำงาน</label>
        <select class="form-select" id="workPermitMOUGroup" name="workPermitMOUGroup">
            <option value="">-- กรุณาเลือก --</option>
            <option value="MOU" {{ old('workPermitMOUGroup') == 'MOU' ? 'selected' : '' }}>MOU</option>
            <option value="MOU 2 ปีหลัง" {{ old('workPermitMOUGroup') == 'MOU 2 ปีหลัง' ? 'selected' : '' }}>MOU 2 ปีหลัง</option>
            <option value="มติต่ออายุในประเทศ" {{ old('workPermitMOUGroup') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
            <option value="มติขึ้นทะเบียน" {{ old('workPermitMOUGroup') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
            <option value="อื่นๆ" {{ old('workPermitMOUGroup') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ ระบุ..</option>
        </select>
    </div>
    <div class="col-md-6 mb-3 d-none" id="workPermitMOUGroupOtherContainer">
        <label for="workPermitMOUGroupOther" class="form-label">ระบุประเภทอื่นๆ</label>
            <input type="text" class="form-control" id="workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther') }}">
    </div>
</div>
<div class="row">
            <div class="col-md-4 mb-3"><label for="name_list_number" class="form-label">เลข RA จากระบบ outsource</label><input type="text" class="form-control" id="name_list_number" name="name_list_number" value="{{ old('name_list_number') }}"></div>
        <div class="col-md-4 mb-3"><label for="request_number" class="form-label">เลขที่คำขอ</label><input type="text" class="form-control" id="request_number" name="request_number" value="{{ old('request_number') }}"></div>
        <div class="col-md-4 mb-3"><label for="employee_id_number" class="form-label">เลขประจำตัว</label><input type="text" class="form-control" id="employee_id_number" name="employee_id_number" value="{{ old('employee_id_number') }}"></div>
        <div class="col-md-4 mb-3"><label for="tax_id_number" class="form-label">เลขประจำตัวผู้เสียภาษี</label><input type="text" class="form-control" id="tax_id_number" name="tax_id_number" value="{{ old('tax_id_number') }}"></div>
        <div class="col-md-4 mb-3"><label for="employer_employee_id" class="form-label">รหัสคนงาน - ของนายจ้าง</label><input type="text" class="form-control" id="employer_employee_id" name="employer_employee_id" value="{{ old('employer_employee_id') }}"></div>
        <div class="col-md-4 mb-3"><label for="department" class="form-label">{{ __('Department') }}</label><input type="text" class="form-control" id="department" name="department" value="{{ old('department') }}"></div>
        <div class="col-md-4 mb-3"><label for="employee_reference_id" class="form-label">เลขอ้างอิงคนงาน</label><input type="text" class="form-control" id="employee_reference_id" name="employee_reference_id" value="{{ old('employee_reference_id') }}"></div>
        <div class="col-md-4 mb-3"><label for="bank_name" class="form-label">ชื่อธนาคาร</label><input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name') }}"></div>
        <div class="col-md-4 mb-3"><label for="bank_account_number" class="form-label">เลขบัญชีธนาคาร</label><input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}"></div>
</div>


{{-- Category 5: Health Insurance --}}
<h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="insurance_type" class="form-label">ประเภทประกัน</label>
        <select class="form-select" id="insurance_type" name="insurance_type">
            <option value="">-- เลือกประเภท --</option>
            <option value="ประกันสังคม" {{ old('insurance_type') == 'ประกันสังคม' ? 'selected' : '' }}>ประกันสังคม</option>
            <option value="ประกันโรงพยาบาล" {{ old('insurance_type') == 'ประกันโรงพยาบาล' ? 'selected' : '' }}>ประกันโรงพยาบาล</option>
            <option value="ประกันเอกชน" {{ old('insurance_type') == 'ประกันเอกชน' ? 'selected' : '' }}>ประกันเอกชน</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <x-file-input-group
            id="medical_certificate_path"
            name="medical_certificate_path"
            label="ใบรับรองแพทย์ (Medical Certificate)"
        />
    </div>
    <div class="col-md-6 mb-3">
        <label for="medical_hospital_name" class="form-label">โรงพยาบาลที่ตรวจโรค (Hospital Name)</label>
        <input type="text" class="form-control" id="medical_hospital_name" name="medical_hospital_name" value="{{ old('medical_hospital_name') }}">
    </div>
</div>

{{-- Social Security Container --}}
<div id="insuranceSocialSecurity" class="d-none">
        <div class="row">
        <div class="col-md-4 mb-3">
            <label for="social_security_number" class="form-label">เลขประกันสังคม</label>
            <input type="text" class="form-control" id="social_security_number" name="social_security_number" value="{{ old('social_security_number') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="insurance_detail_social" class="form-label">สิทธิ์โรงพยาบาล</label>
            <input type="text" class="form-control" name="insurance_detail_social" value="{{ old('insurance_detail_social') }}">
        </div>
        <div class="col-md-4 mb-3">
            <x-file-input-group
                id="insurance_document_path_social"
                name="insurance_document_path_social"
                label="แนบไฟล์เอกสารประกัน"
            />
        </div>
        <div class="col-md-4 mb-3">
            <label for="sso_issue_date" class="form-label">วันที่ออกบัตร (Issue Date)</label>
            <input type="date" class="form-control" id="sso_issue_date" name="sso_issue_date" value="{{ old('sso_issue_date') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="sso_expiry_date" class="form-label">วันหมดอายุ (Expiry Date)</label>
            <input type="date" class="form-control" id="sso_expiry_date" name="sso_expiry_date" value="{{ old('sso_expiry_date') }}">
        </div>
        </div>
</div>
{{-- Hospital Insurance Container --}}
<div id="insuranceHospital" class="d-none">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="insurance_detail_hospital" class="form-label">ชื่อโรงพยาบาล</label>
            <input type="text" class="form-control" name="insurance_detail_hospital" value="{{ old('insurance_detail_hospital') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="insurance_expiry_date_hospital" class="form-label">วันหมดอายุประกัน</label>
            <input type="date" class="form-control" name="insurance_expiry_date_hospital" value="{{ old('insurance_expiry_date_hospital') }}">
        </div>
            <div class="col-md-4 mb-3">
            <x-file-input-group
                id="insurance_document_path_hospital"
                name="insurance_document_path_hospital"
                label="แนบไฟล์เอกสารประกัน"
            />
        </div>
    </div>
</div>
{{-- Private Insurance Container --}}
<div id="insurancePrivate" class="d-none">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="insurance_detail_private" class="form-label">บริษัทประกัน</label>
            <input type="text" class="form-control" name="insurance_detail_private" value="{{ old('insurance_detail_private') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="insurance_expiry_date_private" class="form-label">วันหมดอายุประกัน</label>
            <input type="date" class="form-control" name="insurance_expiry_date_private" value="{{ old('insurance_expiry_date_private') }}">
        </div>
            <div class="col-md-4 mb-3">
            <x-file-input-group
                id="insurance_document_path_private"
                name="insurance_document_path_private"
                label="แนบไฟล์เอกสารประกัน"
            />
        </div>
    </div>
</div>


{{-- Category 6: Login Information --}}
<h5 class="mt-4"><i class="bi bi-lock-fill"></i> 6. ข้อมูลการเข้าสู่ระบบ (Login Information)</h5>
<hr class="mb-4">
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="employeeEmail" class="form-label">อีเมล</label>
        <input type="text" class="form-control" id="employeeEmail" name="employeeEmail" value="{{ old('employeeEmail') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="employeePassword" class="form-label">รหัสสำหรับอีเมล</label>
        <input type="text" class="form-control" id="employeePassword" name="employeePassword">
    </div>
    <div class="col-md-4 mb-3">
        <label for="outsource_code" class="form-label">รหัสสำหรับระบบ Outsource</label>
        <input type="text" class="form-control" id="outsource_code" name="outsource_code" value="{{ old('outsource_code') }}">
    </div>
</div>

{{-- Category 7: File Attachments --}}
<h5 class="mt-4"><i class="bi bi-file-earmark-arrow-up-fill"></i> 7. ส่วนแนบไฟล์เอกสาร (File Attachments)</h5>
<hr class="mb-4">
<div class="row">
    @php
        $docs = [
            1 => '1. พาสปอร์ต', 2 => '2. วีซ่า', 3 => '3. ใบเสร็จ Work Permit', 4 => '4. บัตรชมพู',
            5 => '5. ทร. 38', 6 => '6. รายงานตัว 90 วัน', 7 => '7. ใบแจ้งที่พักอาศัย', 8 => '8. เอกสารบ้านเกิด'
        ];
    @endphp

    @foreach($docs as $i => $label)
        <div class="col-md-4 mb-3">
            <x-file-input-group
                :id="'employee_doc_' . $i"
                :name="'employee_doc_' . $i"
                :label="$label"
            />
        </div>
    @endforeach

    @for($i = 1; $i <= 10; $i++)
        @php
            $docIndex = $i + 8;
            $fieldName = "employee_doc_" . $docIndex;
            $descName = "other_doc_" . $i . "_desc";
            $label = "เอกสารอื่นๆ " . $i;
        @endphp
        <div class="col-md-6 mb-3">
            <x-file-input-group
                :id="$fieldName"
                :name="$fieldName"
                :label="$docIndex . '. ' . $label"
                :description="$descName"
                :descriptionValue="old($descName)"
            />
        </div>
    @endfor
</div>
