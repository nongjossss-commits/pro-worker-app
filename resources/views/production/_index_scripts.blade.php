@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const activeTabId = @json($activeTab->id ?? null);

    // ส่ง ProductionOrder ทั้งใบไป Workflow (MOU demand card)
    window.sendOrderToWorkflow = function(orderId) {
        Swal.fire({
            title: @json(__('Send to Workflow?')),
            text: @json(__('This will move the entire demand card to Workflow.')),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: @json(__('Yes, send')),
            cancelButtonText: @json(__('Cancel'))
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch(`/production/order/${orderId}/send-to-workflow`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1800, showConfirmButton: false })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed', 'error');
                }
            })
            .catch(err => Swal.fire('Error', err.message, 'error'));
        });
    };

    // Order custom fields drawer — implementation อยู่ใน production/partials/order_fields_drawer.blade.php

    // --- Pre-Production Flag Setting ---
    document.addEventListener('DOMContentLoaded', function() {
        // Set Create Job Modal Flag
        const createJobInput = document.getElementById('create_job_is_pre_production');
        if(createJobInput) createJobInput.value = '1';
    });

    // --- Dynamic Numbering ---
    // DEPRECATED: CSS Counters are now used for robust numbering.
    // function updateSequenceNumbers() { ... }

        window.loadBatchStats = function() {
        const containers = document.querySelectorAll('.production-order-card-container');
        const orderIds = Array.from(containers).map(el => {
            const collapse = el.querySelector('.accordion-collapse');
            return collapse ? collapse.id.replace('collapse-', '') : null;
        }).filter(id => id);

        if (orderIds.length === 0) return;

        const urlParams = new URLSearchParams(window.location.search);

        fetch('{{ route("production.stats.batch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                order_ids: orderIds,
                search: urlParams.get('search'),
                filter: urlParams.get('filter'),
                operator_filter: urlParams.get('operator_filter')
            })
        })
        .then(res => res.json())
        .then(data => {
            for (const [orderId, stats] of Object.entries(data)) {
                const totalEl = document.getElementById(`order-${orderId}-total`);
                if(totalEl) totalEl.innerText = stats.activeCount;

                // Update Step badges
                if (stats.stepStats) {
                    for (const [stepId, count] of Object.entries(stats.stepStats)) {
                        const badge = document.getElementById(`order-${orderId}-step-${stepId}`);
                        if (badge) {
                            badge.innerText = count;
                            if (count > 0) {
                                badge.classList.replace('bg-secondary', 'bg-info');
                                badge.classList.replace('bg-opacity-10', 'text-dark');
                                badge.classList.replace('text-muted', 'text-dark');
                            }
                        }
                    }
                }
            }
        })
        .catch(err => console.error('Stats loading failed', err));
    }

    // โหลด batch stats หลังจาก browser ว่าง (ไม่บล็อกการ render หน้าเว็บ)
    document.addEventListener('DOMContentLoaded', function() {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => loadBatchStats(), { timeout: 2000 });
        } else {
            setTimeout(() => loadBatchStats(), 300);
        }
    });

    // --- Lazy Load ---
    const loadedOrders = {};
    document.getElementById('productionAccordion')?.addEventListener('show.bs.collapse', function (e) {
        if (e.target.classList.contains('accordion-collapse')) {
            const orderId = e.target.id.replace('collapse-', '');
            if (!loadedOrders[orderId]) {
                const container = document.getElementById(`order-content-${orderId}`);

                // Construct URL with current params (filter, search)
                const baseUrl = `{{ url('production/order') }}/${orderId}/employees`;
                const url = new URL(baseUrl, window.location.origin);
                const currentParams = new URLSearchParams(window.location.search);
                currentParams.forEach((value, key) => url.searchParams.append(key, value));

                fetch(url)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    loadedOrders[orderId] = true;
                    if(window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
                });
            } else {
                 if(window.refreshGlobalSelectionUI) setTimeout(window.refreshGlobalSelectionUI, 100);
            }
        }
    });

    // After expansion completes (and the previous card has fully collapsed,
    // shifting the layout), focus the heading of the just-opened card so
    // the user doesn't need to scroll up to find it.
    document.getElementById('productionAccordion')?.addEventListener('shown.bs.collapse', function (e) {
        if (!e.target.classList.contains('accordion-collapse')) return;
        const headingId = e.target.getAttribute('aria-labelledby');
        const heading = headingId ? document.getElementById(headingId) : null;
        if (heading) {
            heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // --- Filter Logic ---
    const currentStepFilter = @json(request('filter'));

    document.addEventListener('DOMContentLoaded', function() {
        if (currentStepFilter) {
            if (currentStepFilter === 'not_started') document.getElementById('filter-not-started')?.classList.add('filter-active');
            else if (currentStepFilter === 'cancelled') document.getElementById('filter-cancelled')?.classList.add('filter-active');
            else if (currentStepFilter === 'completed') document.getElementById('filter-completed')?.classList.add('filter-active');
            else {
                const pill = document.getElementById(`filter-step-${currentStepFilter}`);
                if (pill) pill.classList.add('filter-active');
            }
        }

        // Appointment-status dropdown (replaces the old 3 clickable stat
        // cards) — reflect whatever `filter` is already in the URL so a
        // reload/shared link shows the right option selected.
        const apptSelect = document.getElementById('appointment-status-filter');
        if (apptSelect && ['appointment_not_scheduled', 'appointment_pending', 'appointment_completed'].includes(currentStepFilter)) {
            apptSelect.value = currentStepFilter;
        }
    });

    window.toggleFilter = function(filterKey) {
        const url = new URL(window.location.href);
        const currentFilter = url.searchParams.get('filter');

        if (!filterKey || currentFilter === filterKey) {
            url.searchParams.delete('filter');
        } else {
            url.searchParams.set('filter', filterKey);
        }
        window.location.href = url.toString();
    }

    // --- Employer-level Select All ---
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employer-select-all')) {
            const orderId = e.target.dataset.employerId;
            const isChecked = e.target.checked;

            // Find the container for this order to only select visible items within it
            const container = document.getElementById(`order-content-${orderId}`);
            if (!container) return;

            const checkboxes = container.querySelectorAll('.employee-checkbox');

            // Select-all should skip employees that are no longer actionable:
            // completed work, cancelled at any of the four resolution statuses,
            // or terminated (terminated_at is set). The data attributes are
            // emitted by the employee card partials.
            const NON_SELECTABLE_STATUSES = [
                'completed',
                'cancelled',
                'registration_cancelled',
                'renewal_cancelled',
            ];
            checkboxes.forEach(cb => {
                const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper');
                const isHidden = cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled'));
                const status = cardWrapper ? (cardWrapper.dataset.status || '') : '';
                const isTerminated = cardWrapper && cardWrapper.dataset.terminated === 'true';
                const isSelectable = !NON_SELECTABLE_STATUSES.includes(status) && !isTerminated;

                if (!isHidden && isSelectable) {
                    if (cb.checked !== isChecked) {
                        cb.checked = isChecked;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        }
    });

    // --- Sync Employer Select All state when individual employees change ---
    // ใช้ debounce เพื่อป้องกันการรัน function นี้ซ้ำๆ หลายครั้งในช่วงเวลาสั้นๆ
    // เช่น ตอน Select All กด checkbox ทีเดียว 50 อัน จะรันแค่ครั้งเดียวแทน 50 ครั้ง
    let _employerCbTimer = null;
    function updateEmployerCheckboxesState() {
        clearTimeout(_employerCbTimer);
        _employerCbTimer = setTimeout(function() {
            document.querySelectorAll('.employer-select-all').forEach(masterCb => {
                const orderId = masterCb.dataset.employerId;
                const container = document.getElementById(`order-content-${orderId}`);
                if (!container) return;

                const allCheckboxes = container.querySelectorAll('.employee-checkbox');
                const relevantCheckboxes = Array.from(allCheckboxes).filter(cb => {
                    const cw = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper');
                    const isHidden = cw && (cw.classList.contains('d-none') || cw.classList.contains('hide-cancelled'));
                    const status = cw ? cw.dataset.status : '';
                    return !isHidden && status !== 'completed';
                });

                if (relevantCheckboxes.length > 0) {
                    const allChecked = relevantCheckboxes.every(cb => cb.checked);
                    masterCb.checked = allChecked;
                    masterCb.indeterminate = !allChecked && relevantCheckboxes.some(cb => cb.checked);
                } else {
                    masterCb.checked = false;
                    masterCb.indeterminate = false;
                }
            });
        }, 60);
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employee-checkbox')) {
            updateEmployerCheckboxesState();
        }
    });

    document.addEventListener('global-selection-updated', function() {
        updateEmployerCheckboxesState();
    });

    // --- Bulk Actions ---
    document.getElementById('bulk-advanced-export-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();
        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }
        document.getElementById('export_employee_ids').value = JSON.stringify(selected);
        if (document.getElementById('export_source_menu')) {
            document.getElementById('export_source_menu').value = 'pre_production';
        }
        new bootstrap.Modal(document.getElementById('advancedExportModal')).show();
    });

    document.getElementById('bulk-download-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();
        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }
        if (window.openBulkDownloadModal) window.openBulkDownloadModal(selected);
    });

    document.getElementById('bulk-generate-pdf-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();

        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }

        // Create form to post to generation modal setup
        const form = document.createElement('form');
        form.method = 'POST';
        // Use relative path to avoid protocol mismatch (http vs https) redirects which strip POST data
        form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect_url';
        redirectInput.value = window.location.href;
        form.appendChild(redirectInput);

        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'employees[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });

    document.getElementById('bulk-advanced-edit-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();
        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('employees.bulk_edit.select_fields') }}';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect_to';
        redirectInput.value = window.location.href;
        form.appendChild(redirectInput);

        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'employee_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    });

    // --- Step Management ---
    document.getElementById('addStepForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        if(!activeTabId) return;
        const name = document.getElementById('newStepName').value;

        fetch('{{ route("production.steps.store") }}', { // New Route
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ work_type_id: activeTabId, name: name })
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    });

    window.deleteStep = function(id) {
         if(!confirm('Delete this step?')) return;
        fetch(`/workflow/steps/${id}`, { // Can reuse existing delete endpoint as ID is unique
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    }

    // --- Send to Workflow ---
    window.sendToWorkflow = function(itemId) {
        Swal.fire({
            title: '{{ __("Send to Workflow?") }}',
            text: '{{ __("Employee will be moved to the Active Job list.") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Send") }}',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/production/${itemId}/send-to-workflow`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Remove card from UI
                        let card = document.getElementById(`item-card-${itemId}`);
                        let wrapper = null;

                        if (!card) {
                            const checkbox = document.querySelector(`.employee-checkbox[data-production-item-id="${itemId}"]`);
                            if (checkbox) {
                                card = checkbox.closest('.employee-card-wrapper');
                            }
                        }

                        if (card) {
                            wrapper = card.closest('.order-content-wrapper');
                            card.remove();

                            // Check if wrapper is empty
                            if(wrapper && wrapper.querySelectorAll('.item-card-wrapper').length === 0 && wrapper.querySelectorAll('.employee-card-wrapper').length === 0) {
                                const orderCard = wrapper.closest('.production-order-card');
                                if(orderCard) {
                                    orderCard.classList.add('grayscale-mode');
                                }
                            }
                        }

                        Swal.fire('{{ __("Sent!") }}', '{{ __("Employee moved to Workflow.") }}', 'success');

                        if(data.order_stats) {
                             if(wrapper) {
                                 const orderId = wrapper.id.replace('order-content-', '');
                                 updateOrderHeaderStats(orderId, data.order_stats);
                             }
                        }
                    } else {
                        Swal.fire('{{ __("Error") }}', data.message, 'error');
                    }
                });
            }
        });
    }

    // --- Bulk Send to Workflow ---
    document.getElementById('bulk-send-to-workflow-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedData = window.getGlobalSelectedData();
        const selectedIds = selectedData.map(item => item.production_item_id || item.id); // For Pre-Prod, the item has the prod_item_id

        if (selectedIds.length === 0) {
            Swal.fire('{{ __("Warning") }}', '{{ __("Please select employees first.") }}', 'warning');
            return;
        }

        Swal.fire({
            title: '{{ __("Send to Workflow?") }}',
            text: `{{ __("Move") }} ${selectedIds.length} {{ __("employees to the Active Job list?") }}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Send All") }}',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '{{ __("Processing...") }}',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('{{ route("production.bulk_send_to_workflow") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ item_ids: selectedIds })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('{{ __("Success") }}', data.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('{{ __("Error") }}', data.message || 'Unknown error', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('{{ __("Error") }}', 'An error occurred during bulk operation.', 'error');
                });
            }
        });
    });

    // --- Helper to Refresh Order Content (List) ---
    window.refreshOrderContent = function(orderId) {
        const container = document.getElementById(`order-content-${orderId}`);
        if (container) {
            container.style.minHeight = container.offsetHeight + 'px';
            container.style.opacity = '0.5';

            fetch(`{{ route('workflow.index') }}/${orderId}/items`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
                container.style.minHeight = '';
            });
        }
    };

    // --- Helper to Refresh Card ---
    window.refreshItemCard = function(itemId) {
        fetch(`/workflow/item/${itemId}/card`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if(data.html) {
                const card = document.getElementById(`item-card-${itemId}`);
                if(card) {
                    card.outerHTML = data.html;
                }
            }
        });
    }

    // --- Helper to Remove Card ---
    window.removeItemCard = function(itemId) {
        const card = document.getElementById(`item-card-${itemId}`);
        if(card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                card.remove();
            }, 300);
        }
    }

    window.updateOrderHeaderStats = function(orderId, stats) {
        if (!stats) return;
        // Basic implementation for Pre-Production if needed (badges are slightly different)
        // Usually Pre-Prod doesn't have detailed stats like Workflow, but if badges exist:

        const setText = (id, text) => {
            const el = document.getElementById(id);
            if(el) el.innerText = text;
        };

        // Steps
        if (stats.step_stats) {
            for (const [stepId, count] of Object.entries(stats.step_stats)) {
                const badge = document.getElementById(`order-${orderId}-step-${stepId}`);
                if (badge) {
                    badge.innerText = count;
                    if (count > 0) {
                        badge.classList.remove('bg-secondary', 'bg-opacity-10', 'text-muted');
                        badge.classList.add('bg-info', 'text-dark');
                    } else {
                        badge.classList.add('bg-secondary', 'bg-opacity-10', 'text-muted');
                        badge.classList.remove('bg-info', 'text-dark');
                    }
                }
            }
        }
    }

    // --- Toggle Cancelled ---
    window.toggleCancelled = function(orderId, btn) {
        const container = document.getElementById(`order-content-${orderId}`);
        const icon = btn.querySelector('i');
        const isHidden = btn.dataset.hidden === "true";

        if (isHidden) {
            // Show them
            container.classList.remove('hide-cancelled');
            btn.dataset.hidden = "false";
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            // Hide them
            container.classList.add('hide-cancelled');
            btn.dataset.hidden = "true";
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    // --- Actions for Pre-Production ---
    window.deleteEmployee = function(employeeId, itemId) {
        if (!itemId) return;
        Swal.fire({
            title: '{{ __("Delete Item?") }}',
            text: '{{ __("This will move the employee to the trash.") }}',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: '{{ __("Delete") }}',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const card = document.getElementById(`employee-card-${employeeId}`);
                        if(card) {
                            card.style.transition = 'all 0.5s ease';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.9)';
                            setTimeout(() => {
                                card.remove();
                            }, 500);
                        }
                    }
                });
            }
        });
    }

    // "Save to Database" (Finish)
    window.finalizeItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Save to Database?") }}',
            text: '{{ __("Mark this employee as saved/completed?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Save") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/finalize`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        refreshItemCard(itemId);
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Saved") }}',
                            text: '{{ __("Employee data saved successfully.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if(data.order_stats) {
                             const card = document.getElementById(`item-card-${itemId}`);
                             if(card) {
                                 const wrapper = card.closest('.order-content-wrapper');
                                 if(wrapper) {
                                     const orderId = wrapper.id.replace('order-content-', '');
                                     updateOrderHeaderStats(orderId, data.order_stats);
                                 }
                             }
                        }
                    }
                });
            }
        });
    }

    window.cancelEmployee = function(employeeId, itemId) {
        if (!itemId) return;
        Swal.fire({
            title: '{{ __("Cancel Registration?") }}',
            text: '{{ __("The employee card will be grayed out.") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            confirmButtonText: '{{ __("Yes, Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/cancel`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const card = document.getElementById(`employee-card-${employeeId}`);
                        if (card) {
                            // Update attributes and styles
                            card.dataset.status = 'registration_cancelled';
                            card.style.filter = 'grayscale(100%)';

                            const innerCard = card.querySelector('.card');
                            if (innerCard) {
                                innerCard.classList.remove('bg-white', 'border', 'shadow-sm', 'bg-success', 'bg-opacity-10');
                                innerCard.classList.add('bg-light', 'border-0', 'text-secondary', 'grayscale-mode');
                            }

                            // Adjust opacity and pointer events
                            const infoContainer = document.getElementById(`info-container-${employeeId}`);
                            if (infoContainer) infoContainer.classList.add('opacity-50', 'pointer-events-none');

                            const stepsContainer = document.getElementById(`steps-container-${employeeId}`);
                            if (stepsContainer) {
                                stepsContainer.classList.add('opacity-50', 'pointer-events-none');
                                stepsContainer.querySelectorAll('button').forEach(btn => {
                                    btn.setAttribute('disabled', 'disabled');
                                });
                            }

                            // Hide checkbox
                            const checkboxContainer = document.getElementById(`checkbox-container-${employeeId}`);
                            if (checkboxContainer) checkboxContainer.classList.add('d-none');

                            // Badges
                            const badgeCompleted = document.getElementById(`badge-completed-${employeeId}`);
                            if (badgeCompleted) badgeCompleted.classList.add('d-none');
                            const badgeCancelled = document.getElementById(`badge-cancelled-${employeeId}`);
                            if (badgeCancelled) badgeCancelled.classList.remove('d-none');

                            // Buttons toggle
                            const btnSave = document.getElementById(`btn-save-${employeeId}`);
                            if (btnSave) btnSave.classList.add('d-none');
                            const btnCancel = document.getElementById(`btn-cancel-${employeeId}`);
                            if (btnCancel) btnCancel.classList.add('d-none');
                            const btnRestore = document.getElementById(`btn-restore-${employeeId}`);
                            if (btnRestore) btnRestore.classList.remove('d-none');
                            const btnUndo = document.getElementById(`btn-undo-${employeeId}`);
                            if (btnUndo) btnUndo.classList.add('d-none');
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Cancelled") }}',
                            text: '{{ __("Registration cancelled.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if(data.order_stats) {
                             if(card) {
                                 const wrapper = card.closest('.order-content-wrapper');
                                 if(wrapper) {
                                     const orderId = wrapper.id.replace('order-content-', '');
                                     updateOrderHeaderStats(orderId, data.order_stats);
                                 }
                             }
                        }
                    }
                });
            }
        });
    }

    window.restoreEmployeeState = function(employeeId, itemId) {
        if (!itemId) return;
        Swal.fire({
            title: '{{ __("Restore to Pending?") }}',
            text: '{{ __("This will move the employee back to the active list.") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Restore") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/restore`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const card = document.getElementById(`employee-card-${employeeId}`);
                        if (card) {
                            // Revert attributes and styles
                            card.dataset.status = 'registration_pending';
                            card.style.filter = '';

                            const innerCard = card.querySelector('.card');
                            if (innerCard) {
                                innerCard.classList.remove('bg-light', 'border-0', 'text-secondary', 'grayscale-mode', 'bg-success', 'bg-opacity-10');
                                innerCard.classList.add('bg-white', 'border', 'shadow-sm');
                            }

                            // Revert opacity and pointer events
                            const infoContainer = document.getElementById(`info-container-${employeeId}`);
                            if (infoContainer) infoContainer.classList.remove('opacity-50', 'pointer-events-none');

                            const stepsContainer = document.getElementById(`steps-container-${employeeId}`);
                            if (stepsContainer) {
                                stepsContainer.classList.remove('opacity-50', 'pointer-events-none');
                                stepsContainer.querySelectorAll('button').forEach(btn => {
                                    btn.removeAttribute('disabled');
                                });
                            }

                            // Show checkbox
                            const checkboxContainer = document.getElementById(`checkbox-container-${employeeId}`);
                            if (checkboxContainer) checkboxContainer.classList.remove('d-none');

                            // Badges
                            const badgeCompleted = document.getElementById(`badge-completed-${employeeId}`);
                            if (badgeCompleted) badgeCompleted.classList.add('d-none');
                            const badgeCancelled = document.getElementById(`badge-cancelled-${employeeId}`);
                            if (badgeCancelled) badgeCancelled.classList.add('d-none');

                            // Buttons toggle
                            const btnSave = document.getElementById(`btn-save-${employeeId}`);
                            if (btnSave) btnSave.classList.remove('d-none');
                            const btnCancel = document.getElementById(`btn-cancel-${employeeId}`);
                            if (btnCancel) btnCancel.classList.remove('d-none');
                            const btnRestore = document.getElementById(`btn-restore-${employeeId}`);
                            if (btnRestore) btnRestore.classList.add('d-none');
                            const btnUndo = document.getElementById(`btn-undo-${employeeId}`);
                            if (btnUndo) btnUndo.classList.add('d-none');
                        }

                         Swal.fire({
                            icon: 'success',
                            title: '{{ __("Restored") }}',
                            text: '{{ __("Employee is back to pending.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if(data.order_stats) {
                             if(card) {
                                 const wrapper = card.closest('.order-content-wrapper');
                                 if(wrapper) {
                                     const orderId = wrapper.id.replace('order-content-', '');
                                     updateOrderHeaderStats(orderId, data.order_stats);
                                 }
                             }
                        }
                    }
                });
            }
        });
    }

    // --- Manage Team JS (Copied from Workflow) ---
    window.openManageTeamModal = function(itemId, btn) {
        const groupName = btn.dataset.groupName || '';
        const orderId = btn.dataset.orderId;

        document.getElementById('team_item_id').value = itemId;
        const nameInput = document.getElementById('workflow_team_name');
        nameInput.value = groupName;

        // Existing Teams (Scan DOM)
        const wrapper = document.getElementById('existing-teams-wrapper');
        const list = document.getElementById('existing-teams-list');
        list.innerHTML = '';
        wrapper.classList.add('d-none');

        if(orderId) {
            const container = document.getElementById(`order-content-${orderId}`);
            if(container) {
                // Find group headers (h6.fw-bold.text-dark.mb-0)
                const headers = container.querySelectorAll('h6.fw-bold.text-dark.mb-0');
                const uniqueGroups = new Set();
                headers.forEach(h => uniqueGroups.add(h.innerText.trim()));

                if(uniqueGroups.size > 0) {
                    wrapper.classList.remove('d-none');
                    uniqueGroups.forEach(name => {
                        const badge = document.createElement('button');
                        badge.className = 'btn btn-sm btn-outline-secondary rounded-pill px-3';
                        badge.type = 'button';
                        badge.innerText = name;
                        badge.onclick = () => { nameInput.value = name; };
                        list.appendChild(badge);
                    });
                }
            }
        }

        const modal = new bootstrap.Modal(document.getElementById('manageTeamModal'));
        modal.show();
    }

    window.saveItemTeam = function() {
        const itemId = document.getElementById('team_item_id').value;
        const groupName = document.getElementById('workflow_team_name').value;

        fetch(`/workflow/item/${itemId}/group`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ group_name: groupName })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('manageTeamModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: '{{ __('Saved') }}',
                    text: '{{ __('Team assigned successfully.') }}',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Update UI: Reload the order items to reflect new grouping
                // Find Order ID
                const card = document.getElementById(`item-card-${itemId}`);
                if (card) {
                    const wrapper = card.closest('.order-content-wrapper');
                    if (wrapper) {
                        const orderId = wrapper.id.replace('order-content-', '');
                        refreshOrderContent(orderId);
                    }
                }
            } else {
                 Swal.fire('{{ __('Error') }}', data.message || '{{ __('Failed to assign team.') }}', 'error');
            }
        });
    }

    // --- Operator Toggle ---
    window.toggleOperator = function(itemId, btn, hasOperator) {
        // Fetch operators first
        Swal.fire({
            title: '{{ __("Loading...") }}',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('{{ route("api-web.operators.list") }}')
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error('Failed to load operators');

                let operators = data.data;
                let optionsHtml = `<option value="">-- {{ __("None / Clear Operator") }} --</option>`;

                // For Pre-production we need the current operator id from the button if possible,
                // but if not, we rely on the backend. Since the button doesn't currently pass the operator ID,
                // we'll just not pre-select, or users will just see the list.
                operators.forEach(op => {
                    optionsHtml += `<option value="${op.id}">${op.name}</option>`;
                });

                const htmlContent = `
                    <div class="form-group text-start">
                        <label class="form-label mb-2">{{ __("Select Operator") }}</label>
                        <select id="operator-select-${itemId}" class="form-select form-select-lg">
                            ${optionsHtml}
                        </select>
                    </div>
                `;

                Swal.fire({
                    title: '{{ __("Assign Operator") }}',
                    html: htmlContent,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save") }}',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonText: '{{ __("Cancel") }}',
                    showLoaderOnConfirm: true,
                    didOpen: () => {},
                    preConfirm: () => {
                        const selectedId = document.getElementById(`operator-select-${itemId}`).value;

                        return fetch(`/workflow/item/${itemId}/toggle-operator`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ operator_id: selectedId })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error(response.statusText);
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        const resData = result.value;
                        if(resData && resData.success) {
                            refreshItemCard(itemId);
                            if(typeof showToast === 'function') {
                                showToast(resData.message, 'success');
                            }
                        } else {
                            Swal.fire('{{ __("Error") }}', resData ? resData.message : 'Unknown error', 'error');
                        }
                    }
                });
            })
            .catch(err => {
                Swal.fire('Error', '{{ __("Could not load operators list.") }}', 'error');
                console.error(err);
            });
    }

    // --- Reuse Toggle Step from Workflow (Global Function) ---
    // Make sure toggleWorkStep exists or include it here if not globally available.
    // It is defined in workflow/index.blade.php. We should copy it or extract to shared JS.
    // For now, I will include a minimal version here.

    window.toggleWorkStep = function(itemId, stepId, completed) {
        // Optimistic UI Update
        const btn = document.querySelector(`.step-btn-${itemId}-${stepId}`);
        if(btn) {
            if(completed) {
                btn.classList.remove('btn-light', 'text-secondary');
                // Use original color logic if it has hex-color data attribute
                const hexColor = btn.getAttribute('data-hex-color');
                const bsColor = btn.getAttribute('data-color');

                if (hexColor) {
                    btn.classList.add('text-white', 'border-0');
                    btn.style.setProperty('background-color', hexColor, 'important');
                    btn.style.setProperty('border-color', hexColor, 'important');
                } else if (bsColor && ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'].includes(bsColor)) {
                    btn.classList.add(`btn-${bsColor}`);
                    if(bsColor === 'warning' || bsColor === 'light') {
                        btn.classList.add('text-dark');
                    } else {
                        btn.classList.add('text-white');
                    }
                } else {
                    btn.classList.add('btn-success', 'text-white');
                }

                if(!btn.innerHTML.includes('bi-check')) btn.innerHTML += ' <i class="bi bi-check-circle-fill ms-1"></i>';
                btn.setAttribute('onclick', `toggleWorkStep(${itemId}, ${stepId}, false)`);
            } else {
                // Remove custom colors and classes
                btn.className = `btn btn-sm btn-light border text-secondary rounded-pill px-3 step-btn-${itemId}-${stepId}`;
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
                const icon = btn.querySelector('i');
                if(icon) icon.remove();
                btn.setAttribute('onclick', `toggleWorkStep(${itemId}, ${stepId}, true)`);
            }
        }

        fetch(`/workflow/item/${itemId}/step-toggle`, { // Reuse Workflow endpoint
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                location.reload(); // Revert on failure
            } else {
                if (data.order_stats) {
                     // Find order ID
                     const card = document.getElementById(`item-card-${itemId}`);
                     if (card) {
                         const wrapper = card.closest('.order-content-wrapper');
                         if (wrapper) {
                             const orderId = wrapper.id.replace('order-content-', '');
                             updateOrderHeaderStats(orderId, data.order_stats);
                         }
                     }
                }
                if (data.tab_stats) {
                    updateGlobalStats(data.tab_stats);
                }
            }
        })
        .catch(err => console.error(err));
    }

    window.updateGlobalStats = function(stats) {
        if (!stats) return;

        // Scoreboard
        const setHtml = (id, html) => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = html;
        };

        const setText = (selector, text) => {
            const el = document.querySelector(selector);
            if(el) el.innerText = text;
        };

        // Counters
        setHtml('stats-total-employees', stats.total_employees);
        setHtml('stats-total-projects', stats.total_projects);
        setText('#filter-not-started h1', stats.not_started);
        setText('#filter-cancelled h1', stats.cancelled);
        setText('#filter-completed h1', stats.completed);

        // Global Progress Bar Pills
        if (stats.step_stats) {
            for (const [stepId, count] of Object.entries(stats.step_stats)) {
                const badge = document.querySelector(`#filter-step-${stepId} .stat-badge`);
                if (badge) {
                    badge.innerText = count;
                    if (count > 0) {
                        badge.classList.remove('bg-secondary', 'bg-opacity-50', 'text-white');
                        badge.classList.add('bg-success');
                    } else {
                        badge.classList.add('bg-secondary', 'bg-opacity-50', 'text-white');
                        badge.classList.remove('bg-success');
                    }
                }
            }
        }
    }

    // --- Trash Feature ---
    window.loadTrashContent = function(url) {
        const body = document.getElementById('trashModalBody');
        body.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-danger" role="status"></div></div>';

        fetch(url || '{{ route("workflow.trash") }}?is_pre_production=1')
            .then(res => res.text())
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load trash.</div>';
            });
    }

    window.openTrashModal = function() {
        const el = document.getElementById('trashModal');
        const modal = new bootstrap.Modal(el);
        modal.show();
        loadTrashContent();
    }

    window.restoreTrashItem = function(id) {
        Swal.fire({
            title: '{{ __("Restore Item?") }}',
            text: '{{ __("Restore this item from trash?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Restore") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/trash/${id}/restore`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Refresh modal content
                        loadTrashContent();

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Restored") }}',
                            text: '{{ __("Item restored successfully.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    }

    // Intercept Pagination Clicks inside Trash Modal
    const trashBody = document.getElementById('trashModalBody');
    if (trashBody) {
        trashBody.addEventListener('click', function(e) {
            // Check if clicked element is pagination link or inside one
            const link = e.target.closest('.pagination a, .page-link');
            if (link && link.href) {
                e.preventDefault();
                // Append is_pre_production flag if not present in pagination link
                let fetchUrl = link.href;
                if (!fetchUrl.includes('is_pre_production')) {
                    const urlObj = new URL(fetchUrl);
                    urlObj.searchParams.set('is_pre_production', '1');
                    fetchUrl = urlObj.toString();
                }
                loadTrashContent(fetchUrl);
            }
        });
    }

    // --- GPS / Deep Linking ---
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const targetOrderId = urlParams.get('order');
        const targetItemId = urlParams.get('item');
        const highlightEmployeeId = urlParams.get('highlight_employee_id');
        const highlightEmployerId = urlParams.get('highlight_employer_id');

        // Note: Production index uses order_id to expand accordions.
        // We will fallback to highlighting by employer id if passed and order is missing.
        let actualOrderId = targetOrderId;
        if (!actualOrderId && highlightEmployerId) {
             const collapseEls = document.querySelectorAll('.accordion-collapse');
             collapseEls.forEach(el => {
                  if (el.getAttribute('data-employer-id') === highlightEmployerId) {
                      actualOrderId = el.id.replace('collapse-', '');
                  }
             });
        }

        if (actualOrderId && (targetItemId || highlightEmployeeId)) {
            const orderHeading = document.getElementById('heading-' + actualOrderId);
            const collapseElement = document.getElementById('collapse-' + actualOrderId);

            if (orderHeading && collapseElement) {
                // Scroll to Order
                setTimeout(() => {
                    orderHeading.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);

                // Check if collapsed
                if (!collapseElement.classList.contains('show')) {
                    // Trigger click on the button
                    const btn = orderHeading.querySelector('button[data-bs-toggle="collapse"]');
                    if(btn) btn.click();
                }

                const highlightTarget = (card) => {
                    setTimeout(() => {
                         card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                         const innerCard = card.querySelector('.card') || card;
                         innerCard.classList.add('border-info', 'border-3', 'shadow-lg');
                         // For Employee cards specifically
                         if (highlightEmployeeId) {
                             innerCard.classList.add('filter-active');
                         }
                         setTimeout(() => {
                             innerCard.classList.remove('border-info', 'border-3', 'shadow-lg', 'filter-active');
                             innerCard.classList.add('shadow-sm');
                         }, 5000);
                    }, 500);
                };

                // Wait for Item to Load
                const observer = new MutationObserver(function(mutations, obs) {
                    let targetCard = document.getElementById('item-card-' + targetItemId);
                    // If highlighting an employee, look for the employee card inside the item card or on its own depending on the view
                    if (highlightEmployeeId) {
                        const empCard = document.getElementById('employee-card-' + highlightEmployeeId) ||
                                        document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.employee-card-wrapper') ||
                                        document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.list-group-item');
                        if (empCard) targetCard = empCard;
                    }

                    if (targetCard) {
                        highlightTarget(targetCard);
                        obs.disconnect();
                    }
                });

                // Start observing the content wrapper
                const contentWrapper = document.getElementById('order-content-' + actualOrderId);
                if (contentWrapper) {
                    observer.observe(contentWrapper, { childList: true, subtree: true });

                    // Fallback check
                    setTimeout(() => {
                         let targetCard = document.getElementById('item-card-' + targetItemId);
                         if (highlightEmployeeId) {
                            const empCard = document.getElementById('employee-card-' + highlightEmployeeId) ||
                                            document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.employee-card-wrapper') ||
                                            document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.list-group-item');
                            if (empCard) targetCard = empCard;
                         }

                         if(targetCard) {
                             highlightTarget(targetCard);
                             observer.disconnect();
                         }
                    }, 1500);
                }
            }
        }
    });

    // ─────────────────────────────────────────────────────────────
    // Finance Modal — Lazy Loading
    // โหลดข้อมูล Finance ของแต่ละ order เฉพาะตอนที่คลิกเปิด
    // ไม่โหลดพร้อมกันทุก order ตอนเปิดหน้า ช่วยลดภาระหน้าเว็บมาก
    // ─────────────────────────────────────────────────────────────
    let _fmScriptPromise = null;

    function _ensureFinancialManagerScript() {
        if (!_fmScriptPromise) {
            _fmScriptPromise = new Promise((resolve, reject) => {
                // ถ้า financialManager ถูกโหลดไปแล้ว (เช่น เปิด modal ที่สองขึ้นไป) ก็ไม่ต้องโหลดซ้ำ
                if (typeof financialManager === 'function') { resolve(); return; }
                const s = document.createElement('script');
                s.src = '{{ asset("js/financial-manager.js") }}?v={{ @filemtime(public_path("js/financial-manager.js")) }}';
                s.onload  = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        return _fmScriptPromise;
    }

    const _financeTabCache = {};

    window.openFinanceModal = async function(orderId) {
        const modal = document.getElementById('financeModal-' + orderId);
        if (!modal) return;

        // เปิด modal ทันที (แสดง spinner ก่อน)
        bootstrap.Modal.getOrCreateInstance(modal).show();

        // ถ้าโหลดไปแล้ว ไม่ต้องโหลดซ้ำ
        if (_financeTabCache[orderId]) return;

        const body = document.getElementById('finance-modal-body-' + orderId);

        try {
            // โหลด HTML ของ finance tab และ financial-manager.js พร้อมกัน
            const [html] = await Promise.all([
                fetch('/production/' + orderId + '/finance-tab', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                }).then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                }),
                _ensureFinancialManagerScript()
            ]);

            // ดึง HTML แล้วลบ <script src> ออกก่อน inject
            // เพราะ financial-manager.js โหลดจาก _ensureFinancialManagerScript แล้ว
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            doc.querySelectorAll('script').forEach(s => s.remove());
            body.innerHTML = doc.body.innerHTML;

            _financeTabCache[orderId] = true;

            // ให้ Alpine.js รับรู้ component ที่ inject เข้ามาใหม่
            if (window.Alpine) Alpine.initTree(body);

        } catch (err) {
            body.innerHTML = [
                '<div class="text-center py-5 text-danger">',
                '<i class="bi bi-exclamation-triangle-fill fs-2 mb-3 d-block"></i>',
                '<p class="fw-bold">โหลดข้อมูลไม่สำเร็จ</p>',
                '<button class="btn btn-outline-danger btn-sm" onclick="_financeTabCache[' + orderId + ']=false; window.openFinanceModal(' + orderId + ')">',
                '<i class="bi bi-arrow-clockwise me-1"></i>ลองใหม่</button>',
                '</div>'
            ].join('');
        }
    };

</script>
@endpush
