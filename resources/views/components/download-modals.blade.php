<!-- Download Options Modal -->
<div class="modal fade" id="downloadOptionsModal" tabindex="-1" aria-labelledby="downloadOptionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadOptionsModalLabel">Download Employee Files</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="downloadOptionsForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Download Mode</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="download_type" id="dlTypeZip" value="zip" checked>
                            <label class="form-check-label" for="dlTypeZip">
                                Separate Files (ZIP)
                                <small class="d-block text-muted">Creates folders for each employee with individual files.</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="download_type" id="dlTypePdf" value="pdf">
                            <label class="form-check-label" for="dlTypePdf">
                                Merged PDF
                                <small class="d-block text-muted">Combines all selected files into a single PDF per employee (or one giant PDF).</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="download_type" id="dlTypeZipSingle" value="zip_single">
                            <label class="form-check-label" for="dlTypeZipSingle">
                                Single Folder (ZIP)
                                <small class="d-block text-muted">Combines all files from all selected employees into one single folder.</small>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Files to Include</label>
                        <div class="row">
                            <!-- Column 1 -->
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="photo" id="chkPhoto" checked>
                                    <label class="form-check-label" for="chkPhoto">รูปถ่าย (Photo)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="passport" id="chkPassport" checked>
                                    <label class="form-check-label" for="chkPassport">1. พาสปอร์ต (Passport)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="visa" id="chkVisa" checked>
                                    <label class="form-check-label" for="chkVisa">2. วีซ่า (Visa)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="work_permit" id="chkWorkPermit" checked>
                                    <label class="form-check-label" for="chkWorkPermit">3. ใบอนุญาต Work Permit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="pink_card" id="chkPinkCard" checked>
                                    <label class="form-check-label" for="chkPinkCard">4. บัตรชมพู (Pink Card)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="tor_ror_38" id="chkTorRor38">
                                    <label class="form-check-label" for="chkTorRor38">5. ทร. 38</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="insurance" id="chkInsurance" checked>
                                    <label class="form-check-label" for="chkInsurance">ไฟล์แนบประกัน (Insurance)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="medical_certificate" id="chkMedicalCertificate" checked>
                                    <label class="form-check-label" for="chkMedicalCertificate">ใบรับรองแพทย์ (Medical Certificate)</label>
                                </div>
                            </div>
                            <!-- Column 2 -->
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="report_90_day" id="chkReport90Day">
                                    <label class="form-check-label" for="chkReport90Day">6. รายงานตัว 90 วัน</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="residence_notification" id="chkResidenceNotification">
                                    <label class="form-check-label" for="chkResidenceNotification">7. ใบแจ้งที่พักอาศัย</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="hometown_doc" id="chkHometownDoc">
                                    <label class="form-check-label" for="chkHometownDoc">8. เอกสารบ้านเกิด</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_1" id="chkOther1">
                                    <label class="form-check-label" for="chkOther1">9. เอกสารอื่นๆ 1</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_2" id="chkOther2">
                                    <label class="form-check-label" for="chkOther2">10. เอกสารอื่นๆ 2</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_3" id="chkOther3">
                                    <label class="form-check-label" for="chkOther3">11. เอกสารอื่นๆ 3</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_4" id="chkOther4">
                                    <label class="form-check-label" for="chkOther4">12. เอกสารอื่นๆ 4</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_5" id="chkOther5">
                                    <label class="form-check-label" for="chkOther5">13. เอกสารอื่นๆ 5</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_6" id="chkOther6">
                                    <label class="form-check-label" for="chkOther6">14. เอกสารอื่นๆ 6</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_7" id="chkOther7">
                                    <label class="form-check-label" for="chkOther7">15. เอกสารอื่นๆ 7</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_8" id="chkOther8">
                                    <label class="form-check-label" for="chkOther8">16. เอกสารอื่นๆ 8</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_9" id="chkOther9">
                                    <label class="form-check-label" for="chkOther9">17. เอกสารอื่นๆ 9</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_doc_10" id="chkOther10">
                                    <label class="form-check-label" for="chkOther10">18. เอกสารอื่นๆ 10</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="downloadEmployeeIds">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnConfirmDownload">Start Download</button>
            </div>
        </div>
    </div>
</div>

<!-- Download Center Modal -->
<div class="modal fade" id="downloadCenterModal" tabindex="-1" aria-labelledby="downloadCenterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadCenterModalLabel">Download Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Background tasks are listed here. Click 'Download' when ready.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="downloadTasksTableBody">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-primary" id="btnRefreshDownloads">Refresh</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const initDownloadModals = () => {
        // Wait for bootstrap to be available (deferred loading)
        if (typeof bootstrap === 'undefined') {
            setTimeout(initDownloadModals, 100);
            return;
        }

        let downloadModal = new bootstrap.Modal(document.getElementById('downloadOptionsModal'));
        let downloadCenterModal = new bootstrap.Modal(document.getElementById('downloadCenterModal'));
        let downloadCenterInterval = null;

        // --- 1. Handle Triggering Download Options ---
        // From Single Action
        document.querySelectorAll('.btn-download-single').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const empId = this.dataset.employeeId;
                document.getElementById('downloadEmployeeIds').value = JSON.stringify([empId]);
                downloadModal.show();
            });
        });

        // From Bulk Action (This function should be called by your bulk action handler)
        window.openBulkDownloadModal = function(employeeIds) {
            if (!employeeIds || employeeIds.length === 0) {
                showToast('Please select employees first.', 'danger');
                return;
            }
            document.getElementById('downloadEmployeeIds').value = JSON.stringify(employeeIds);
            downloadModal.show();
        };

        // --- 2. Handle Confirm Download ---
        document.getElementById('btnConfirmDownload').addEventListener('click', function() {
            const type = document.querySelector('input[name="download_type"]:checked').value;
            const files = Array.from(document.querySelectorAll('input[name="files[]"]:checked')).map(cb => cb.value);
            const empIds = JSON.parse(document.getElementById('downloadEmployeeIds').value || '[]');

            if (files.length === 0) {
                showToast('Please select at least one file type.', 'danger');
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerText = 'Starting...';

            fetch('{{ route("admin.downloads.initiate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_ids: empIds,
                    selected_files: files,
                    type: type
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    downloadModal.hide();
                    showToast(data.message, 'success');

                    // Open Download Center to show progress
                    loadDownloadTasks();
                    downloadCenterModal.show();

                    // Auto download if ready
                    if (data.download_url) {
                        // Direct Download for all types using iframe method to prevent page reload/navigation
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = data.download_url;
                        document.body.appendChild(iframe);
                        // Cleanup iframe after a bit
                        setTimeout(() => document.body.removeChild(iframe), 60000);
                    }
                } else {
                    showToast('Error starting download.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('An unexpected error occurred.', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerText = 'Start Download';
            });
        });

        // --- 3. Handle Download Center ---
        function loadDownloadTasks() {
            const tbody = document.getElementById('downloadTasksTableBody');
            // Only show loading if empty, to avoid flickering on refresh
            if (!tbody.innerHTML.trim() || tbody.innerText.includes('Loading') || tbody.innerText.includes('No tasks')) {
                 tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
            }

            fetch('{{ route("admin.downloads.index") }}')
            .then(res => res.json())
            .then(tasks => {
                tbody.innerHTML = '';
                if (tasks.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No tasks found.</td></tr>';
                    return;
                }
                tasks.forEach(task => {
                    let badgeClass = 'bg-secondary';
                    if (task.status === 'completed') badgeClass = 'bg-success';
                    else if (task.status === 'processing') badgeClass = 'bg-warning text-dark';
                    else if (task.status === 'failed') badgeClass = 'bg-danger';

                    let actionBtn = '';
                    if (task.status === 'completed') {
                        const url = '{{ route("admin.downloads.download", ":id") }}'.replace(':id', task.id);
                        actionBtn = `<a href="${url}" class="btn btn-sm btn-success" download><i class="bi bi-download"></i> Download</a>`;
                    } else if (task.status === 'failed') {
                        actionBtn = `
                            <span class="text-danger fw-bold">Failed</span>
                            <i class="bi bi-info-circle ms-1 text-muted" data-bs-toggle="tooltip" title="${task.error_message}"></i>
                        `;
                    } else {
                        actionBtn = `<span class="text-muted spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span class="text-muted">Processing...</span>`;
                    }

                    const date = new Date(task.created_at).toLocaleString('th-TH');
                    // Pretty type
                    let displayType = task.type.toUpperCase();
                    if(task.type === 'zip_single') displayType = 'ZIP (Single Folder)';

                    tbody.innerHTML += `
                        <tr>
                            <td>${task.id}</td>
                            <td>${displayType}</td>
                            <td>${date}</td>
                            <td><span class="badge ${badgeClass}">${task.status}</span></td>
                            <td>${actionBtn}</td>
                        </tr>
                    `;
                });

                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            })
            .catch(err => {
                console.error(err);
                // Only show error if list is empty, otherwise keep old list
                if(tbody.children.length === 1 && tbody.innerText.includes('Loading')) {
                     tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading tasks.</td></tr>';
                }
            });
        }

        document.getElementById('btnRefreshDownloads').addEventListener('click', loadDownloadTasks);

        // Auto-refresh when modal is open
        const centerModalEl = document.getElementById('downloadCenterModal');
        centerModalEl.addEventListener('shown.bs.modal', function () {
            loadDownloadTasks();
            // Poll every 3 seconds
            downloadCenterInterval = setInterval(loadDownloadTasks, 3000);
        });

        centerModalEl.addEventListener('hidden.bs.modal', function () {
            if (downloadCenterInterval) {
                clearInterval(downloadCenterInterval);
                downloadCenterInterval = null;
            }
        });

        // Expose function globally if needed or just rely on button click
        window.openDownloadCenter = function() {
            loadDownloadTasks();
            downloadCenterModal.show();
        };

        // Listen for a custom event (optional, if you add a navbar link)
        document.addEventListener('open-download-center', function() {
            window.openDownloadCenter();
        });
    };
    initDownloadModals();
});
</script>
@endpush
