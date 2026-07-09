<div class="modal fade" id="advancedExportModal" tabindex="-1" aria-labelledby="advancedExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="advancedExportForm" action="{{ route('employees.advanced_export') }}" method="POST">
                @csrf
                <input type="hidden" name="employee_ids" id="export_employee_ids">
                <input type="hidden" name="export_source_menu" id="export_source_menu" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="advancedExportModalLabel">{{ __('Advanced Export') }} (Max 15 items)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i> {{ __('Please select up to 15 fields. If Photo is selected, it will be the first column.') }}
                        <span class="float-end fw-bold" id="selection-counter">0/15</span>
                    </div>

                    <div class="row g-3">
                        <!-- Group 1: Personal Information -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Personal Information') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeePhoto" id="col_photo">
                                        <label class="form-check-label" for="col_photo">{{ __('Photo') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeTitleTh" id="col_title_th">
                                        <label class="form-check-label" for="col_title_th">{{ __('Title (TH)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeNameTh" id="col_name_th">
                                        <label class="form-check-label" for="col_name_th">{{ __('Name (TH)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeTitleEn" id="col_title_en">
                                        <label class="form-check-label" for="col_title_en">{{ __('Prefix (EN)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeNameEn" id="col_name_en">
                                        <label class="form-check-label" for="col_name_en">{{ __('Name (EN)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="father_name" id="col_father">
                                        <label class="form-check-label" for="col_father">{{ __('Father Name') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="mother_name" id="col_mother">
                                        <label class="form-check-label" for="col_mother">{{ __('Mother Name') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeGender" id="col_gender">
                                        <label class="form-check-label" for="col_gender">{{ __('Gender') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeDob" id="col_dob">
                                        <label class="form-check-label" for="col_dob">{{ __('Date of Birth') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeAge" id="col_age">
                                        <label class="form-check-label" for="col_age">{{ __('Age') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="height" id="col_height">
                                        <label class="form-check-label" for="col_height">{{ __('Height') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="weight" id="col_weight">
                                        <label class="form-check-label" for="col_weight">{{ __('Weight') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 2: Contact & Nationality -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Contact & Nationality') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeePhone" id="col_phone">
                                        <label class="form-check-label" for="col_phone">{{ __('Phone') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeNationality" id="col_nat">
                                        <label class="form-check-label" for="col_nat">{{ __('Nationality') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="passportType" id="col_ppt_mm">
                                        <label class="form-check-label" for="col_ppt_mm">{{ __('Passport Type (MM)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="passport_type_cambodia" id="col_ppt_kh">
                                        <label class="form-check-label" for="col_ppt_kh">{{ __('Passport Type (KH)') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 3: Passport & Visa -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Passport & Visa') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeePassport" id="col_pp_no">
                                        <label class="form-check-label" for="col_pp_no">{{ __('Passport No') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="passport_issue_date" id="col_pp_issue">
                                        <label class="form-check-label" for="col_pp_issue">{{ __('Issue Date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="passport_issue_place" id="col_pp_issue_place">
                                        <label class="form-check-label" for="col_pp_issue_place">{{ __('Passport Issue Place') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="passportExpiryDate" id="col_pp_exp">
                                        <label class="form-check-label" for="col_pp_exp">{{ __('Expiry Date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="pinkCardNo" id="col_pink">
                                        <label class="form-check-label" for="col_pink">{{ __('Pink Card') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="visaType" id="col_visa_type">
                                        <label class="form-check-label" for="col_visa_type">{{ __('Visa Type') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="visaEndorsementDate" id="col_visa_endorse_date">
                                        <label class="form-check-label" for="col_visa_endorse_date">{{ __('Visa Endorsement Date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="visaEndorsementNo" id="col_visa_endorse_no">
                                        <label class="form-check-label" for="col_visa_endorse_no">{{ __('Visa Endorsement No') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="visaExpiryDate" id="col_visa_exp">
                                        <label class="form-check-label" for="col_visa_exp">{{ __('Visa Expiry') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="visa_issue_place" id="col_visa_issue_place">
                                        <label class="form-check-label" for="col_visa_issue_place">{{ __('Visa Issue Place') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 4: Employment -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Employment & Work IDs') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="job_title" id="col_job">
                                        <label class="form-check-label" for="col_job">{{ __('Job Title') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="job_description" id="col_nature">
                                        <label class="form-check-label" for="col_nature">{{ __('Nature of Work') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="department" id="col_department">
                                        <label class="form-check-label" for="col_department">{{ __('Department') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="startDate" id="col_start">
                                        <label class="form-check-label" for="col_start">{{ __('Start Date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employeeWorkPermit" id="col_wp_no">
                                        <label class="form-check-label" for="col_wp_no">{{ __('Work Permit No') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="workPermitIssueDate" id="col_wp_issue">
                                        <label class="form-check-label" for="col_wp_issue">{{ __('WP Issue Date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="workPermitExpiryDate" id="col_wp_exp">
                                        <label class="form-check-label" for="col_wp_exp">{{ __('WP Expiry') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="ninetyDayReportDate" id="col_90day">
                                        <label class="form-check-label" for="col_90day">{{ __('90 Day Report') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="workPermitMOUGroup" id="col_mou">
                                        <label class="form-check-label" for="col_mou">{{ __('WP Type') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="workPermitMOUGroupOther" id="col_mou_other">
                                        <label class="form-check-label" for="col_mou_other">{{ __('WP Other') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="name_list_number" id="col_namelist">
                                        <label class="form-check-label" for="col_namelist">{{ __('RA Number from Outsource') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="request_number" id="col_request">
                                        <label class="form-check-label" for="col_request">{{ __('Request No') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employee_id_number" id="col_pid">
                                        <label class="form-check-label" for="col_pid">{{ __('Personal ID') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="tax_id_number" id="col_tax">
                                        <label class="form-check-label" for="col_tax">{{ __('Tax ID') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employer_employee_id" id="col_emp_worker_id">
                                        <label class="form-check-label" for="col_emp_worker_id">{{ __('Employer-Worker ID') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employee_reference_id" id="col_ref">
                                        <label class="form-check-label" for="col_ref">{{ __('Reference ID') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="bank_name" id="col_bank_name">
                                        <label class="form-check-label" for="col_bank_name">{{ __('Bank Name') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="bank_account_number" id="col_bank_account_number">
                                        <label class="form-check-label" for="col_bank_account_number">{{ __('Bank Account Number') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 5: Insurance -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Health Insurance') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="insurance_type" id="col_ins_type">
                                        <label class="form-check-label" for="col_ins_type">{{ __('Insurance Type') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="social_security_number" id="col_ss_no">
                                        <label class="form-check-label" for="col_ss_no">{{ __('SS Number') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="sso_issue_date" id="col_ss_issue">
                                        <label class="form-check-label" for="col_ss_issue">SS Issue Date</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="sso_expiry_date" id="col_ss_exp">
                                        <label class="form-check-label" for="col_ss_exp">SS Expiry Date</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="insurance_detail" id="col_ins_hosp_right">
                                        <label class="form-check-label" for="col_ins_hosp_right">{{ __('Hospital Rights (SS)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="insurance_detail_hospital" id="col_hosp_name">
                                        <label class="form-check-label" for="col_hosp_name">{{ __('Hospital Name') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="medical_hospital_name" id="col_med_hosp">
                                        <label class="form-check-label" for="col_med_hosp">{{ __('Medical Check-up Hospital') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="insurance_expiry_date_hospital" id="col_hosp_exp">
                                        <label class="form-check-label" for="col_hosp_exp">{{ __('Hospital Expiry') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="insurance_detail_private" id="col_priv_comp">
                                        <label class="form-check-label" for="col_priv_comp">{{ __('Private Company') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="insurance_expiry_date_private" id="col_priv_exp">
                                        <label class="form-check-label" for="col_priv_exp">{{ __('Private Expiry') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 6: Login -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Login Information') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="email" id="col_email">
                                        <label class="form-check-label" for="col_email">{{ __('Email') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="password" id="col_password">
                                        <label class="form-check-label" for="col_password">{{ __('Email Password') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="outsource_code" id="col_outsource_code">
                                        <label class="form-check-label" for="col_outsource_code">{{ __('Outsource System Code') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 7: Employer Information -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1">{{ __('Employer Information') }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employerTaxId" id="col_emp_tax_id">
                                        <label class="form-check-label" for="col_emp_tax_id">{{ __('Employer Tax ID') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="businessType" id="col_emp_business_type">
                                        <label class="form-check-label" for="col_emp_business_type">{{ __('Business Type') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="job_owner_id" id="col_emp_job_owner">
                                        <label class="form-check-label" for="col_emp_job_owner">{{ __('Job Owner Name') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employerNameTh" id="col_emp_name_th">
                                        <label class="form-check-label" for="col_emp_name_th">{{ __('Employer Name (TH)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employerNameEn" id="col_emp_name_en">
                                        <label class="form-check-label" for="col_emp_name_en">{{ __('Employer Name (EN)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employerAddressTh" id="col_emp_addr_th">
                                        <label class="form-check-label" for="col_emp_addr_th">{{ __('Employer Address (TH)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-checkbox" type="checkbox" name="columns[]" value="employerAddressEn" id="col_emp_addr_en">
                                        <label class="form-check-label" for="col_emp_addr_en">{{ __('Employer Address (EN)') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <hr class="my-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_name_suffix" value="1" id="include_name_suffix">
                            <label class="form-check-label fw-bold" for="include_name_suffix">
                                <i class="bi bi-tag me-1"></i> {{ __('Include Name Suffix') }}
                            </label>
                        </div>
                        <small class="text-muted">{{ __('When selected, name suffix will be appended to employee (EN) and employer (Thai) names') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-success" id="btn-confirm-export"><i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> {{ __('Export Selected') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxItems = 15;
    const checkboxes = document.querySelectorAll('.export-checkbox');
    const counter = document.getElementById('selection-counter');

    function updateCounter() {
        const checkedCount = document.querySelectorAll('.export-checkbox:checked').length;
        counter.textContent = `${checkedCount}/${maxItems}`;

        if (checkedCount >= maxItems) {
            checkboxes.forEach(cb => {
                if (!cb.checked) cb.disabled = true;
            });
            counter.classList.add('text-danger');
        } else {
            checkboxes.forEach(cb => cb.disabled = false);
            counter.classList.remove('text-danger');
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });

    // Reset form when modal closes
    const modalEl = document.getElementById('advancedExportModal');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            document.getElementById('advancedExportForm').reset();
            updateCounter();
        });
    }
});
</script>
@endpush
