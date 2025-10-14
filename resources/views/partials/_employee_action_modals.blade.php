{{-- Terminate Employee Modal --}}
<div class="modal fade" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="terminate-form" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="terminateModalLabel">แจ้งออก / เลิกจ้าง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="terminated_at" class="form-label">วันที่แจ้งออก / เลิกจ้าง</label>
                        <input type="date" class="form-control" id="terminated_at" name="terminated_at" required>
                    </div>
                    <div class="mb-3">
                        <label for="termination_reason" class="form-label">เหตุผล (ถ้ามี)</label>
                        <textarea class="form-control" id="termination_reason" name="termination_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Employment History Modal --}}
<div class="modal fade" id="employmentHistoryModal" tabindex="-1" aria-labelledby="employmentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employmentHistoryModalLabel">ประวัติการจ้างงาน</h5>
                {{-- Search Form --}}
                <form id="historySearchForm" class="ms-auto d-flex">
                    <input type="text" id="historySearchInput" class="form-control me-2" placeholder="ค้นหาพนักงาน...">
                    <button type="submit" class="btn btn-primary">ค้นหา</button>
                </form>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>รูป</th>
                                <th>ชื่อ-สกุล (ไทย)</th>
                                <th>ชื่อ-สกุล (อังกฤษ)</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            {{-- Dynamic content will be loaded here --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <button id="exportHistoryBtn" class="btn btn-success">Export to Excel</button>
                </div>
                <div id="historyPagination" class="d-flex align-items-center">
                    {{-- Pagination controls will be rendered here --}}
                </div>
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const historyModalEl = document.getElementById('employmentHistoryModal');
    if (!historyModalEl) return;

    const employerId = {{ $employer->id ?? 'null' }};
    const tableBody = document.getElementById('historyTableBody');
    const searchForm = document.getElementById('historySearchForm');
    const searchInput = document.getElementById('historySearchInput');
    const paginationContainer = document.getElementById('historyPagination');
    const exportBtn = document.getElementById('exportHistoryBtn');

    let currentSearchTerm = '';

    // --- Main function to fetch and render history data ---
    function fetchHistory(page = 1, searchTerm = '') {
        if (!employerId) return;

        currentSearchTerm = searchTerm;
        const url = `/employers/${employerId}/history?page=${page}&search=${encodeURIComponent(searchTerm)}`;

        tableBody.innerHTML = `<tr><td colspan="4" class="text-center">กำลังโหลดข้อมูล...</td></tr>`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (!data.data || data.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="4" class="text-center">ไม่พบข้อมูล</td></tr>`;
                    renderPagination(null, null); // Clear pagination
                    return;
                }

                data.data.forEach(employee => {
                    const photoUrl = employee.employeePhoto ? `/storage/${employee.employeePhoto}` : '/img/placeholder.png';
                    let actions = '';
                    if (employee.can_restore) {
                        actions += `<button class="btn btn-sm btn-info js-restore-btn" data-employee-id="${employee.id}">คืนสถานะ</button> `;
                    }
                    if (employee.can_force_delete) {
                        actions += `<button class="btn btn-sm btn-danger js-force-delete-btn" data-employee-id="${employee.id}">ลบถาวร</button>`;
                    }

                    const row = `
                        <tr>
                            <td><img src="${photoUrl}" alt="Photo" class="img-thumbnail" width="50"></td>
                            <td>${employee.employeeNameTh || '-'}</td>
                            <td>${employee.employeeNameEn || '-'}</td>
                            <td>${actions || 'ไม่มี'}</td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });

                renderPagination(data.links, data.meta);
            })
            .catch(error => {
                console.error('Error fetching history:', error);
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
            });
    }

    // --- Function to render pagination controls ---
    function renderPagination(links, meta) {
        paginationContainer.innerHTML = '';
        if (!links || !meta || meta.total <= meta.per_page) {
            return;
        }

        const prevButton = document.createElement('button');
        prevButton.innerText = 'ก่อนหน้า';
        prevButton.className = 'btn btn-outline-secondary me-2';
        prevButton.disabled = !links.prev;
        if (links.prev) {
            prevButton.onclick = () => fetchHistory(meta.current_page - 1, currentSearchTerm);
        }

        const nextButton = document.createElement('button');
        nextButton.innerText = 'ถัดไป';
        nextButton.className = 'btn btn-outline-secondary';
        nextButton.disabled = !links.next;
        if (links.next) {
            nextButton.onclick = () => fetchHistory(meta.current_page + 1, currentSearchTerm);
        }

        const pageInfo = document.createElement('span');
        pageInfo.className = 'me-3';
        pageInfo.innerText = `หน้า ${meta.current_page} / ${meta.last_page}`;


        paginationContainer.appendChild(prevButton);
        paginationContainer.appendChild(pageInfo);
        paginationContainer.appendChild(nextButton);
    }

    // --- Event Listeners ---
    historyModalEl.addEventListener('show.bs.modal', () => {
        searchInput.value = '';
        fetchHistory(); // Fetch initial data when modal opens
    });

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchHistory(1, searchInput.value);
    });

    exportBtn.addEventListener('click', () => {
        // Note: This export will respect the current search term.
        const exportUrl = `/employers/${employerId}/export-employees?search=${encodeURIComponent(currentSearchTerm)}`;
        window.open(exportUrl, '_blank');
    });
});
</script>
@endpush