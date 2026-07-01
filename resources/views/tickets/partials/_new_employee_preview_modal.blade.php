{{-- resources/views/tickets/partials/_new_employee_preview_modal.blade.php --}}
<div class="modal fade" id="newEmployeePreviewModal" tabindex="-1" aria-labelledby="newEmployeePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="newEmployeePreviewModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>รายละเอียดแจ้งเข้าลูกจ้างใหม่ (Preview)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- Photo Section --}}
                    <div class="col-md-3 text-center mb-3">
                        <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-center" style="height: 180px; width: 150px; margin: 0 auto;">
                            <img id="preview_employeePhoto" src="" alt="No Photo" class="img-fluid" style="max-height: 100%; display: none;">
                            <i id="preview_employeePhoto_placeholder" class="bi bi-person-bounding-box fs-1 text-secondary"></i>
                        </div>
                        <div class="mt-2 small text-muted">รูปถ่ายคนงาน</div>
                    </div>

                    {{-- Personal Info Section --}}
                    <div class="col-md-9">
                        <h6 class="text-primary border-bottom pb-2 mb-3">1. ข้อมูลส่วนตัว</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold small">ชื่อ-นามสกุล (ไทย):</label>
                                <div id="preview_name_th" class="text-dark"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small">Name (Eng):</label>
                                <div id="preview_name_en" class="text-dark"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small">เพศ:</label>
                                <div id="preview_gender" class="text-dark"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small">วันเกิด:</label>
                                <div id="preview_dob" class="text-dark"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold small">อายุ:</label>
                                <div id="preview_age" class="text-dark"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small">ชื่อบิดา:</label>
                                <div id="preview_father_name" class="text-dark"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small">ชื่อมารดา:</label>
                                <div id="preview_mother_name" class="text-dark"></div>
                            </div>
                        </div>
                        <h6 class="text-primary border-bottom pb-2 mb-3">2. ข้อมูลการติดต่อ</h6>
                         <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold small">เบอร์โทรศัพท์:</label>
                                <div id="preview_phone" class="text-dark"></div>
                            </div>
                             <div class="col-md-6">
                                <label class="fw-bold small">อีเมล:</label>
                                <div id="preview_email" class="text-dark"></div>
                            </div>
                             <div class="col-md-6">
                                <label class="fw-bold small">รหัสผ่าน (สำหรับเข้าสู่ระบบ):</label>
                                <div id="preview_password" class="text-dark"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Passport & Visa Section --}}
                <h6 class="text-primary border-bottom pb-2 mb-3 mt-2">3. ข้อมูลหนังสือเดินทาง & วีซ่า</h6>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="fw-bold small">สัญชาติ:</label>
                        <div id="preview_nationality" class="text-dark"></div>
                    </div>
                     <div class="col-md-4">
                        <label class="fw-bold small">เลขที่พาสปอร์ต:</label>
                        <div id="preview_passport_no" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small">ประเภทพาสปอร์ต:</label>
                        <div id="preview_passport_type" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small">วันที่ออก:</label>
                        <div id="preview_passport_issue" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small">วันหมดอายุ:</label>
                        <div id="preview_passport_expiry" class="text-dark"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small">บัตรสีชมพู:</label>
                        <div id="preview_pink_card" class="text-dark"></div>
                    </div>
                     <div class="col-md-6">
                        <label class="fw-bold small">ประเภทวีซ่า:</label>
                        <div id="preview_visa_type" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">วันที่ตรวจลงตราวีซ่า:</label>
                        <div id="preview_visa_endorsement_date" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">เลขที่ตรวจลงตราวีซ่า:</label>
                        <div id="preview_visa_endorsement_no" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">วีซ่าหมดอายุ:</label>
                        <div id="preview_visa_expiry" class="text-dark"></div>
                    </div>
                </div>

                {{-- Work Permit Section --}}
                <h6 class="text-primary border-bottom pb-2 mb-3 mt-2">4. ข้อมูลใบอนุญาตทำงาน</h6>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small">ตำแหน่งงาน:</label>
                        <div id="preview_job_title" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">ลักษณะงาน:</label>
                        <div id="preview_job_description" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">วันที่เริ่มงาน:</label>
                        <div id="preview_start_date" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">เลขที่ใบอนุญาต:</label>
                        <div id="preview_work_permit" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">วันหมดอายุใบอนุญาต:</label>
                        <div id="preview_work_permit_expiry" class="text-dark"></div>
                    </div>
                     <div class="col-md-6">
                        <label class="fw-bold small">รายงานตัว 90 วัน:</label>
                        <div id="preview_90_day" class="text-dark"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small">ประเภทใบอนุญาต (MOU/อื่นๆ):</label>
                        <div id="preview_mou_group" class="text-dark"></div>
                    </div>
                </div>
                {{-- Additional Work Permit Info --}}
                 <div class="row g-2 mb-3 bg-light p-2 rounded">
                    <div class="col-md-4">
                        <label class="fw-bold small">เลข RA จากระบบ outsource:</label>
                        <div id="preview_name_list" class="text-dark"></div>
                    </div>
                     <div class="col-md-4">
                        <label class="fw-bold small">เลขที่คำขอ:</label>
                        <div id="preview_request_number" class="text-dark"></div>
                    </div>
                     <div class="col-md-4">
                        <label class="fw-bold small">เลขประจำตัว:</label>
                        <div id="preview_id_number" class="text-dark"></div>
                    </div>
                     <div class="col-md-4">
                        <label class="fw-bold small">เลขผู้เสียภาษี:</label>
                        <div id="preview_tax_id" class="text-dark"></div>
                    </div>
                     <div class="col-md-4">
                        <label class="fw-bold small">รหัสคนงาน (นายจ้าง):</label>
                        <div id="preview_employer_emp_id" class="text-dark"></div>
                    </div>
                     <div class="col-md-4">
                        <label class="fw-bold small">เลขอ้างอิงคนงาน:</label>
                        <div id="preview_ref_id" class="text-dark"></div>
                    </div>
                </div>

                {{-- Insurance Section --}}
                <h6 class="text-primary border-bottom pb-2 mb-3 mt-2">5. ข้อมูลประกัน</h6>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="fw-bold small">ประเภทประกัน:</label>
                        <div id="preview_insurance_type" class="text-dark"></div>
                    </div>
                    <div class="col-md-8">
                        <label class="fw-bold small">รายละเอียด/เลขที่/วันหมดอายุ:</label>
                        <div id="preview_insurance_detail" class="text-dark"></div>
                    </div>
                </div>

                {{-- Documents Section --}}
                <h6 class="text-primary border-bottom pb-2 mb-3 mt-2">6. เอกสารแนบ</h6>
                <div class="row g-2" id="preview_documents_container">
                    {{-- Links will be injected here via JS --}}
                </div>

            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openNewEmployeePreview(dataIndex) {
        // V2.5 FIX: Fetch data from global object if index is passed, otherwise use data directly
        let data;
        if (typeof dataIndex === 'number' || typeof dataIndex === 'string') {
             if (typeof window.newEmployeeDataMap === 'undefined' || !window.newEmployeeDataMap[dataIndex]) {
                 console.error('New employee data not found for index:', dataIndex);
                 return;
             }
             data = window.newEmployeeDataMap[dataIndex];
        } else {
            data = dataIndex; // Fallback for legacy calls
        }

        if (!data) return;

        // Helper to safely set text
        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '-';
        };

        // Helper to format date (simple YYYY-MM-DD to DD/MM/YYYY)
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                // Use Thai locale logic if desired, but let's keep it simple first
                return date.toLocaleDateString('th-TH');
            } catch (e) { return dateStr; }
        };

        // 1. Personal Info
        setText('preview_name_th', (data.employeeTitleTh || '') + ' ' + (data.employeeNameTh || ''));
        setText('preview_name_en', (data.employeeTitleEn || '') + ' ' + (data.employeeNameEn || ''));
        setText('preview_gender', data.employeeGender);
        setText('preview_dob', formatDate(data.employeeDob));
        setText('preview_age', data.employeeAge);
        setText('preview_father_name', data.father_name);
        setText('preview_mother_name', data.mother_name);
        setText('preview_phone', data.employeePhone);
        setText('preview_email', data.employeeEmail);
        setText('preview_password', data.employeePassword);

        // 2. Passport
        setText('preview_nationality', data.employeeNationality);
        setText('preview_passport_no', data.employeePassport);
        setText('preview_passport_type', data.passportType || data.passport_type_cambodia); // Handle varying fields
        setText('preview_passport_issue', formatDate(data.passport_issue_date));
        setText('preview_passport_expiry', formatDate(data.passportExpiryDate));
        setText('preview_pink_card', data.pinkCardNo);
        setText('preview_visa_type', data.visaType);
        setText('preview_visa_endorsement_date', formatDate(data.visaEndorsementDate));
        setText('preview_visa_endorsement_no', data.visaEndorsementNo);
        setText('preview_visa_expiry', formatDate(data.visaExpiryDate));

        // 3. Work Permit
        setText('preview_job_title', data.job_title);
        setText('preview_job_description', data.job_description);
        setText('preview_start_date', formatDate(data.startDate));
        setText('preview_work_permit', data.employeeWorkPermit);
        setText('preview_work_permit_expiry', formatDate(data.workPermitExpiryDate));
        setText('preview_90_day', formatDate(data.ninetyDayReportDate));

        // MOU Group
        let mouText = data.workPermitMOUGroup || '-';
        if (data.workPermitMOUGroup === 'อื่นๆ' && data.workPermitMOUGroupOther) {
            mouText += ' (' + data.workPermitMOUGroupOther + ')';
        }
        setText('preview_mou_group', mouText);

        // Additional IDs
        setText('preview_name_list', data.name_list_number);
        setText('preview_request_number', data.request_number);
        setText('preview_id_number', data.employee_id_number);
        setText('preview_tax_id', data.tax_id_number);
        setText('preview_employer_emp_id', data.employer_employee_id);
        setText('preview_ref_id', data.employee_reference_id);


        // 4. Insurance (Logic based on type)
        setText('preview_insurance_type', data.insurance_type);
        let insuranceDetail = '-';
        if (data.insurance_type === 'ประกันสังคม') {
            insuranceDetail = 'เลข: ' + (data.social_security_number || '-') + ' / รพ: ' + (data.insurance_detail_social || '-');
        } else if (data.insurance_type === 'ประกันโรงพยาบาล') {
            insuranceDetail = 'รพ: ' + (data.insurance_detail_hospital || '-') + ' (หมดอายุ: ' + formatDate(data.insurance_expiry_date_hospital) + ')';
        } else if (data.insurance_type === 'ประกันเอกชน') {
            insuranceDetail = 'บริษัท: ' + (data.insurance_detail_private || '-') + ' (หมดอายุ: ' + formatDate(data.insurance_expiry_date_private) + ')';
        }
        setText('preview_insurance_detail', insuranceDetail);

        // 5. Photo
        const photoImg = document.getElementById('preview_employeePhoto');
        const photoPlaceholder = document.getElementById('preview_employeePhoto_placeholder');

        // Check for employeePhoto_url which is generated by the JobTicket accessor
        if (data.employeePhoto_url) {
            photoImg.src = data.employeePhoto_url;
            photoImg.style.display = 'block';
            photoPlaceholder.style.display = 'none';
        } else {
            photoImg.style.display = 'none';
            photoPlaceholder.style.display = 'block';
        }

        // 6. Documents Link Generation
        const docsContainer = document.getElementById('preview_documents_container');
        docsContainer.innerHTML = ''; // Clear previous

        const docLabels = {
            'employee_doc_1': 'Passport',
            'employee_doc_2': 'Visa',
            'employee_doc_3': 'ใบเสร็จ Work Permit',
            'employee_doc_4': 'บัตรสีชมพู',
            'employee_doc_5': 'ทร. 38',
            'employee_doc_6': 'รายงานตัว 90 วัน',
            'employee_doc_7': 'ใบแจ้งที่พักอาศัย',
            'employee_doc_8': 'เอกสารบ้านเกิด',
            'employee_doc_9': 'เอกสารอื่นๆ 1',
            'employee_doc_10': 'เอกสารอื่นๆ 2',
            'employee_doc_11': 'เอกสารอื่นๆ 3',
            'employee_doc_12': 'เอกสารอื่นๆ 4',
            'employee_doc_13': 'เอกสารอื่นๆ 5',
            'employee_doc_14': 'เอกสารอื่นๆ 6',
            'employee_doc_15': 'เอกสารอื่นๆ 7',
            'employee_doc_16': 'เอกสารอื่นๆ 8',
            'employee_doc_17': 'เอกสารอื่นๆ 9',
            'employee_doc_18': 'เอกสารอื่นๆ 10',
            'insurance_document_path_social': 'เอกสารประกันสังคม',
            'insurance_document_path_hospital': 'เอกสารประกัน รพ.',
            'insurance_document_path_private': 'เอกสารประกันเอกชน'
        };

        let hasDocs = false;
        for (const [key, label] of Object.entries(docLabels)) {
            const urlKey = key + '_url';
            if (data[urlKey]) {
                hasDocs = true;
                let displayLabel = label;
                // Check for custom descriptions for other docs
                if (key === 'employee_doc_9' && data.other_doc_1_desc) displayLabel += ': ' + data.other_doc_1_desc;
                if (key === 'employee_doc_10' && data.other_doc_2_desc) displayLabel += ': ' + data.other_doc_2_desc;
                if (key === 'employee_doc_11' && data.other_doc_3_desc) displayLabel += ': ' + data.other_doc_3_desc;
                if (key === 'employee_doc_12' && data.other_doc_4_desc) displayLabel += ': ' + data.other_doc_4_desc;
                if (key === 'employee_doc_13' && data.other_doc_5_desc) displayLabel += ': ' + data.other_doc_5_desc;
                if (key === 'employee_doc_14' && data.other_doc_6_desc) displayLabel += ': ' + data.other_doc_6_desc;
                if (key === 'employee_doc_15' && data.other_doc_7_desc) displayLabel += ': ' + data.other_doc_7_desc;
                if (key === 'employee_doc_16' && data.other_doc_8_desc) displayLabel += ': ' + data.other_doc_8_desc;
                if (key === 'employee_doc_17' && data.other_doc_9_desc) displayLabel += ': ' + data.other_doc_9_desc;
                if (key === 'employee_doc_18' && data.other_doc_10_desc) displayLabel += ': ' + data.other_doc_10_desc;


                const col = document.createElement('div');
                col.className = 'col-md-4 col-6 mb-2';
                col.innerHTML = `
                    <a href="${data[urlKey]}" target="_blank" class="btn btn-outline-secondary w-100 text-start text-truncate" title="${displayLabel}">
                        <i class="bi bi-file-earmark-text me-1"></i> ${displayLabel}
                    </a>
                `;
                docsContainer.appendChild(col);
            }
        }

        if (!hasDocs) {
            docsContainer.innerHTML = '<div class="col-12 text-muted fst-italic">- ไม่มีเอกสารแนบ -</div>';
        }

        // Show Modal
        const modalEl = document.getElementById('newEmployeePreviewModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
</script>
