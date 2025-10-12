<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Company Records')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --bs-primary: #F97316;
            --bs-primary-rgb: 249, 115, 22;
            --bs-primary-dark: #EA580C;
            --bs-primary-light: #FB923C;
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc; /* Fallback color */
            --bs-border-color: #e2e8f0;
        }

        body {
            font-size: 1rem;
            line-height: 1.6;
            background-color: var(--bs-body-bg);
            background-image: radial-gradient(var(--bs-border-color) 1px, transparent 1px);
            background-size: 1.5rem 1.5rem;
            background-attachment: fixed;
        }

        .main-layout {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 260px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        #sidebar .navbar-brand {
            font-weight: 700;
            color: var(--bs-primary);
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.75rem !important;
        }

        #sidebar .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: #475569;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
            font-size: 0.95rem;
        }

        #sidebar .list-group-item.active {
            background-color: var(--bs-primary-light);
            color: #ffffff;
            background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));
            box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2), 0 2px 4px -2px rgba(249, 115, 22, 0.2);
        }

        #sidebar .list-group-item:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        #main-content {
            flex-grow: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }

        .content-section {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07);
            padding: 2rem;
        }
        .table thead {
            --bs-table-bg: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .employee-card.highlight {
            border: 2px solid var(--bs-primary-dark);
            box-shadow: 0 0 12px rgba(var(--bs-primary-rgb), 0.4);
            background-color: #fffbeb;
        }
        .modal-backdrop.fade.show{
            display: none;
        }
        .modal.fade.show{
            background: rgba(0, 0, 0, 0.6);
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <aside id="sidebar">
            <a class="navbar-brand fs-4" href="#"><i class="bi bi-building-fill-gear"></i> Company Records</a>
            <div class="list-group" id="main-nav">
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-pie-chart-fill me-2"></i>ภาพรวม</a>
                <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <span><i class="bi bi-bell-fill me-2"></i>แจ้งเตือน</span>
                </a>
                <hr>
                <a href="{{ route('employers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard-fill me-2"></i>ข้อมูลนายจ้าง</a>
                <a href="{{ route('employees.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill me-2"></i>ข้อมูลลูกจ้าง
                </a>
                <a href="{{ route('importers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('importers.*') ? 'active' : '' }}"><i class="bi bi-box-arrow-in-down-left me-2"></i>ข้อมูลบริษัทนำเข้า</a>
                <a href="{{ route('agents.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('agents.*') ? 'active' : '' }}"><i class="bi bi-person-square me-2"></i>ข้อมูลเอเจนซี่</a>
                <a href="{{ route('delegates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('delegates.*') ? 'active' : '' }}"><i class="bi bi-people-fill me-2"></i>ข้อมูลพนักงาน</a>

                @canany(['manage-roles', 'manage-settings'])
                <hr>
                <a href="{{ route('admin.roles_permissions.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.roles_permissions.*') ? 'active' : '' }}"><i class="bi bi-shield-lock-fill me-2"></i>จัดการสิทธิ์</a>
                @endcanany
            </div>
            <div class="mt-auto">
                <hr>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="bi bi-person-circle fs-2"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ Auth::user()->name ?? 'User Name' }}</h6>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}"
                               onclick="event.preventDefault(); this.closest('form').submit();"
                               class="text-muted small">
                                Logout
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <main id="main-content" style="position: relative; z-index: 1;">
            @yield('debug-tracker')
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>

        {{-- Notification Modals --}}
        <div class="modal fade" id="renewNotificationModal" tabindex="-1" aria-labelledby="renewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="renew-form" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="renewModalLabel">ต่ออายุการแจ้งเตือน</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="new_due_date" class="form-label">เลือกวันหมดอายุใหม่:</label>
                                <input type="date" class="form-control" id="new_due_date" name="new_due_date" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cancelNotificationModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="cancel-form" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelModalLabel">ยืนยันการยกเลิกการต่ออายุ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการแจ้งเตือนนี้? การกระทำนี้จะย้ายรายการไปที่แท็บ "รายการที่ยกเลิก" และคุณสามารถกู้คืนได้ในภายหลัง</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            <button type="submit" class="btn btn-danger">ยืนยันการยกเลิก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    {{-- Job Owner Management Modal --}}
    <div class="modal fade" id="jobOwnerModal" tabindex="-1" aria-labelledby="jobOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobOwnerModalLabel">จัดการข้อมูลเจ้าของงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- List of current owners --}}
                    <h5>เจ้าของงานทั้งหมด</h5>
                    <ul class="list-group mb-3" id="jobOwnerList">
                        {{-- Owners will be loaded here via JS --}}
                        <li class="list-group-item text-muted">กำลังโหลด...</li>
                    </ul>

                    <hr>

                    {{-- Add new owner form --}}
                    <h6>เพิ่มเจ้าของงานใหม่</h6>
                    <div class="input-group">
                        <input type="text" id="newJobOwnerName" class="form-control" placeholder="ชื่อเจ้าของงาน">
                        <button class="btn btn-primary" type="button" id="saveNewJobOwnerBtn">บันทึก</button>
                    </div>
                    <div id="jobOwnerError" class="text-danger small mt-1" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast Notification Container --}}
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill rounded me-2 text-success"></i>
                <strong class="me-auto">การแจ้งเตือน</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-body-content">
                </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const renewModal = document.getElementById('renewNotificationModal');
            if (renewModal) {
                renewModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const form = document.getElementById('renew-form');
                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/renew`;
                    }
                });
            }

            const cancelModal = document.getElementById('cancelNotificationModal');
            if (cancelModal) {
                cancelModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const form = document.getElementById('cancel-form');
                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/cancel`;
                    }
                });
            }
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const jobOwnerModalEl = document.getElementById('jobOwnerModal');
        if (!jobOwnerModalEl) return;

        const jobOwnerModal = new bootstrap.Modal(jobOwnerModalEl);
        const jobOwnerList = document.getElementById('jobOwnerList');
        const newJobOwnerNameInput = document.getElementById('newJobOwnerName');
        const saveNewJobOwnerBtn = document.getElementById('saveNewJobOwnerBtn');
        const jobOwnerError = document.getElementById('jobOwnerError');
        const mainJobOwnerSelect = document.getElementById('job_owner_id');
        const deleteJobOwnerBtn = document.getElementById('deleteJobOwnerBtn');

        // --- Main Logic ---

        // 1. Open Modal: Fetch and display all current job owners
        jobOwnerModalEl.addEventListener('show.bs.modal', function () {
            fetchJobOwners();
        });

        // 2. Add Owner: Handle click on "Save New Owner" button
        saveNewJobOwnerBtn.addEventListener('click', function () {
            const name = newJobOwnerNameInput.value.trim();
            if (!name) {
                showError('กรุณาใส่ชื่อเจ้าของงาน');
                return;
            }

            fetch('{{ route('job-owners.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Add to modal list
                    appendOwnerToList(data.jobOwner);
                    // Add to main dropdown and select it
                    if (mainJobOwnerSelect) {
                        const newOption = new Option(data.jobOwner.name, data.jobOwner.id, true, true);
                        mainJobOwnerSelect.add(newOption, null);
                    }
                    newJobOwnerNameInput.value = '';
                    hideError();
                }
            })
            .catch(error => {
                if (error.errors && error.errors.name) {
                    showError(error.errors.name[0]);
                } else {
                    showError('เกิดข้อผิดพลาดในการบันทึก');
                }
            });
        });

        // 3. Delete Owner: Handle clicks on delete icons in the modal
        jobOwnerList.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-job-owner-icon')) {
                const ownerId = e.target.dataset.id;
                if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเจ้าของงานนี้?')) {
                    deleteOwner(ownerId);
                }
            }
        });

        // 4. Delete selected owner from the main form
        if (deleteJobOwnerBtn && mainJobOwnerSelect) {
            deleteJobOwnerBtn.addEventListener('click', function() {
                const selectedOwnerId = mainJobOwnerSelect.value;
                if (selectedOwnerId && selectedOwnerId !== '--- เลือกเจ้าของงาน ---') {
                     if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเจ้าของงานที่เลือก?')) {
                        deleteOwner(selectedOwnerId);
                    }
                } else {
                    alert('กรุณาเลือกเจ้าของงานที่ต้องการลบ');
                }
            });
        }


        // --- Helper Functions ---

        function fetchJobOwners() {
            jobOwnerList.innerHTML = '<li class="list-group-item text-muted">กำลังโหลด...</li>';
            fetch('{{ route('job-owners.index') }}')
                .then(response => response.json())
                .then(data => {
                    jobOwnerList.innerHTML = '';
                    if (data.length === 0) {
                        jobOwnerList.innerHTML = '<li class="list-group-item text-muted">ไม่มีข้อมูลเจ้าของงาน</li>';
                    } else {
                        data.forEach(owner => appendOwnerToList(owner));
                    }
                });
        }

        function appendOwnerToList(owner) {
             // Check if "no data" placeholder exists and remove it
            const placeholder = jobOwnerList.querySelector('.text-muted');
            if (placeholder) {
                placeholder.parentElement.innerHTML = '';
            }
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.id = `job-owner-${owner.id}`;
            li.textContent = owner.name;
            const deleteIcon = document.createElement('i');
            deleteIcon.className = 'bi bi-trash-fill text-danger delete-job-owner-icon';
            deleteIcon.style.cursor = 'pointer';
            deleteIcon.dataset.id = owner.id;
            li.appendChild(deleteIcon);
            jobOwnerList.appendChild(li);
        }

        function deleteOwner(ownerId) {
            fetch(`/job-owners/${ownerId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from modal list
                    const listItem = document.getElementById(`job-owner-${ownerId}`);
                    if (listItem) listItem.remove();

                    // Remove from main dropdown
                    if (mainJobOwnerSelect) {
                        const optionToRemove = mainJobOwnerSelect.querySelector(`option[value='${ownerId}']`);
                        if (optionToRemove) optionToRemove.remove();
                    }
                } else {
                    alert(data.message || 'ไม่สามารถลบข้อมูลได้');
                }
            });
        }

        function showError(message) {
            jobOwnerError.textContent = message;
            jobOwnerError.style.display = 'block';
            newJobOwnerNameInput.classList.add('is-invalid');
        }

        function hideError() {
            jobOwnerError.textContent = '';
            jobOwnerError.style.display = 'none';
            newJobOwnerNameInput.classList.remove('is-invalid');
        }
    });
    </script>
    <script>
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-body-content');
        const toastIcon = toastEl.querySelector('.toast-header i');

        // Reset classes
        toastIcon.className = 'rounded me-2';

        if (type === 'success') {
            toastIcon.classList.add('bi', 'bi-check-circle-fill', 'text-success');
        } else if (type === 'danger') {
            toastIcon.classList.add('bi', 'bi-exclamation-triangle-fill', 'text-danger');
        }

        toastBody.textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')

<script>
// Full-Featured Address Management Script
document.addEventListener('DOMContentLoaded', function () {
    // --- Configuration & Global State ---
    const thaiDataUrl = "/thai-addresses"; // Hardcoded URL
    let thaiAddressData = [];
    let dataLoaded = false;
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return; // Exit if the modal isn't on the page

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveBtn = document.getElementById('saveAddressBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Form Fields ---
    const fields = {
        id: document.getElementById('address_id'),
        addressable_id: document.getElementById('addressable_id'),
        addressable_type: document.getElementById('addressable_type'),
        type: document.getElementById('address_type'),
        addrNo: document.getElementById('addrNo'),
        addrNoEn: document.getElementById('addrNoEn'),
        addrMoo: document.getElementById('addrMoo'),
        addrMooEn: document.getElementById('addrMooEn'),
        addrSoi: document.getElementById('addrSoi'),
        addrSoiEn: document.getElementById('addrSoiEn'),
        addrRoad: document.getElementById('addrRoad'),
        addrRoadEn: document.getElementById('addrRoadEn'),
        addrProvince: document.getElementById('addrProvince'),
        addrProvinceEn: document.getElementById('addrProvinceEn'),
        addrDistrict: document.getElementById('addrDistrict'),
        addrDistrictEn: document.getElementById('addrDistrictEn'),
        addrSubDistrict: document.getElementById('addrSubDistrict'),
        addrSubDistrictEn: document.getElementById('addrSubDistrictEn'),
        addrZipCode: document.getElementById('addrZipCode')
    };

    // --- Data Loading ---
    async function fetchThaiAddressData() {
        if (dataLoaded) return;
        try {
            const response = await fetch(thaiDataUrl);
            if (!response.ok) throw new Error('Network response was not ok.');
            thaiAddressData = await response.json();
            dataLoaded = true;
            populateProvinces();
        } catch (error) {
            console.error('Failed to fetch Thai address data:', error);
            showToast('ไม่สามารถโหลดข้อมูลที่อยู่ได้', 'danger');
        }
    }

    // --- Dropdown Population ---
    function populateProvinces() {
        fields.addrProvince.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
        const uniqueProvinces = [...new Map(thaiAddressData.map(item => [item['province_th'], item])).values()];
        uniqueProvinces.sort((a, b) => a.province_th.localeCompare(b.province_th, 'th'));
        uniqueProvinces.forEach(item => {
            const option = new Option(item.province_th, item.province_th);
            fields.addrProvince.add(option);
        });
    }

    function populateDistricts(province) {
        fields.addrDistrict.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
        fields.addrSubDistrict.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>'; // Reset sub-districts
        if (!province) return;

        const districts = [...new Set(thaiAddressData.filter(d => d.province_th === province).map(d => d.district_th))];
        districts.sort((a, b) => a.localeCompare(b, 'th'));
        districts.forEach(district => {
            const option = new Option(district, district);
            fields.addrDistrict.add(option);
        });
    }

    function populateSubDistricts(province, district) {
        fields.addrSubDistrict.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
        if (!province || !district) return;

        const subDistricts = thaiAddressData.filter(d => d.province_th === province && d.district_th === district);
        subDistricts.sort((a, b) => a.subdistrict_th.localeCompare(b.subdistrict_th, 'th'));
        subDistricts.forEach(sub => {
            const option = new Option(sub.subdistrict_th, sub.subdistrict_th);
            fields.addrSubDistrict.add(option);
        });
    }

    // --- Event Listeners for Dropdowns ---
    fields.addrProvince.addEventListener('change', function() {
        populateDistricts(this.value);
        const selectedData = thaiAddressData.find(d => d.province_th === this.value);
        fields.addrProvinceEn.value = selectedData ? selectedData.province_en : '';
        fields.addrDistrictEn.value = '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrDistrict.addEventListener('change', function() {
        populateSubDistricts(fields.addrProvince.value, this.value);
        const selectedData = thaiAddressData.find(d => d.province_th === fields.addrProvince.value && d.district_th === this.value);
        fields.addrDistrictEn.value = selectedData ? selectedData.district_en : '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrSubDistrict.addEventListener('change', function() {
        const selectedData = thaiAddressData.find(d =>
            d.province_th === fields.addrProvince.value &&
            d.district_th === fields.addrDistrict.value &&
            d.subdistrict_th === this.value
        );
        if (selectedData) {
            fields.addrSubDistrictEn.value = selectedData.subdistrict_en;
            fields.addrZipCode.value = selectedData.zip_code;
        }
    });

    // --- Modal Opening Logic ---
    addressModalEl.addEventListener('show.bs.modal', async function(e) {
        await fetchThaiAddressData();
        const button = e.relatedTarget;
        if (!button) return;

        const isAddButton = button.matches('.add-address-btn');
        const isEditButton = button.matches('.edit-address-btn');

        if (isAddButton) {
            addressForm.reset();
            fields.id.value = '';
            fields.addressable_id.value = button.dataset.addressableId;
            fields.addressable_type.value = 'App\\Models\\Employer';
            fields.type.value = button.dataset.type;
            document.getElementById('addressModalLabel').textContent = 'เพิ่มที่อยู่ใหม่';
        } else if (isEditButton) {
            const addressId = button.dataset.addressId;
            document.getElementById('addressModalLabel').textContent = 'กำลังโหลด...';
            try {
                const response = await fetch(`/addresses/${addressId}`);
                if (!response.ok) throw new Error('Failed to fetch address data.');
                const data = await response.json();

                addressForm.reset();
                for (const key in data) {
                    if (fields[key]) {
                       fields[key].value = data[key];
                    }
                }

                populateDistricts(data.addrProvince);
                fields.addrDistrict.value = data.addrDistrict;
                populateSubDistricts(data.addrProvince, data.addrDistrict);
                fields.addrSubDistrict.value = data.addrSubDistrict;

                document.getElementById('addressModalLabel').textContent = 'แก้ไขที่อยู่';
            } catch (error) {
                console.error('Error fetching address for edit:', error);
                showToast('ไม่สามารถโหลดข้อมูลที่อยู่เพื่อแก้ไขได้', 'danger');
                addressModal.hide();
            }
        }
    });

    // --- Save Logic ---
    saveBtn.addEventListener('click', async function() {
        const formData = new FormData(addressForm);
        const addressId = fields.id.value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = 'POST';

        if (addressId) {
            formData.append('_method', 'PUT');
        }

        try {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก...`;

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                     let errorMsg = Object.values(result.errors).flat().join('\\n');
                     throw new Error(errorMsg);
                }
                throw new Error(result.message || 'An unknown error occurred.');
            }

            showToast(result.message || 'บันทึกที่อยู่เรียบร้อยแล้ว', 'success');
            addressModal.hide();

            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            console.error('Save address error:', error);
            showToast(error.message || 'เกิดข้อผิดพลาดในการบันทึก', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'บันทึก';
        }
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        populateDistricts('');
        document.getElementById('addressModalLabel').textContent = 'เพิ่ม/แก้ไขที่อยู่';
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const bulkActionBar = document.querySelector('.bulk-action-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionButton = bulkActionBar ? bulkActionBar.querySelector('button') : null;

    if (!selectAllCheckbox) return; // Exit if controls are not on this page

    function updateBulkActionBar() {
        const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
        const count = selectedCheckboxes.length;

        if (count > 0) {
            bulkActionBar.style.display = 'flex';
            selectedCountSpan.textContent = count;
            bulkActionButton.disabled = false;
            // Sync select-all checkbox
            selectAllCheckbox.checked = (count === employeeCheckboxes.length);
        } else {
            bulkActionBar.style.display = 'none';
            bulkActionButton.disabled = true;
            selectAllCheckbox.checked = false;
        }
    }

    selectAllCheckbox.addEventListener('change', function () {
        employeeCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionBar();
    });

    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionBar);
    });
});
</script>
</body>
</html>
