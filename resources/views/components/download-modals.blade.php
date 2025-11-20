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
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Files to Include</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="photo" id="chkPhoto" checked>
                                    <label class="form-check-label" for="chkPhoto">Photo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="passport" id="chkPassport" checked>
                                    <label class="form-check-label" for="chkPassport">Passport</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="visa" id="chkVisa" checked>
                                    <label class="form-check-label" for="chkVisa">Visa</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="work_permit" id="chkWorkPermit" checked>
                                    <label class="form-check-label" for="chkWorkPermit">Work Permit</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="pink_card" id="chkPinkCard" checked>
                                    <label class="form-check-label" for="chkPinkCard">Pink Card</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="insurance" id="chkInsurance" checked>
                                    <label class="form-check-label" for="chkInsurance">Insurance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="files[]" value="other_docs" id="chkOther">
                                    <label class="form-check-label" for="chkOther">Other Docs</label>
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
    let downloadModal = new bootstrap.Modal(document.getElementById('downloadOptionsModal'));
    let downloadCenterModal = new bootstrap.Modal(document.getElementById('downloadCenterModal'));

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
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';

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
                        actionBtn = `<a href="${url}" class="btn btn-sm btn-success" target="_blank"><i class="bi bi-download"></i> Download</a>`;
                    } else if (task.status === 'failed') {
                        actionBtn = `<span class="text-danger" title="${task.error_message}">Failed</span>`;
                    } else {
                        actionBtn = `<span class="text-muted">Wait...</span>`;
                    }

                    const date = new Date(task.created_at).toLocaleString('th-TH');

                    tbody.innerHTML += `
                        <tr>
                            <td>${task.id}</td>
                            <td>${task.type.toUpperCase()}</td>
                            <td>${date}</td>
                            <td><span class="badge ${badgeClass}">${task.status}</span></td>
                            <td>${actionBtn}</td>
                        </tr>
                    `;
                });
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading tasks.</td></tr>';
            });
    }

    document.getElementById('btnRefreshDownloads').addEventListener('click', loadDownloadTasks);

    // Expose function globally if needed or just rely on button click
    window.openDownloadCenter = function() {
        loadDownloadTasks();
        downloadCenterModal.show();
    };

    // Listen for a custom event (optional, if you add a navbar link)
    document.addEventListener('open-download-center', function() {
        window.openDownloadCenter();
    });
});
</script>
@endpush
