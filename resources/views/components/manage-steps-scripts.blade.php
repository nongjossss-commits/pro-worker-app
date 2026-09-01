{{-- resources/views/components/manage-steps-scripts.blade.php
     Shared JS for the "Manage Steps" modal (manage-steps-modal.blade.php) —
     add / edit / reorder / delete, none of them reload the page. Reads the
     per-menu endpoint URLs off the modal's own data-* attributes so the
     same functions work for Pre-Production, Workflow, Registration
     Resolution and Renewal Resolution unchanged. @once guards against
     double-declaring if a page somehow includes this twice. --}}
@once
@push('scripts')
<script>
(function () {
    function modalConfig() {
        const el = document.getElementById('manageStepsModal');
        if (!el) return null;
        let extraPayload = {};
        try {
            extraPayload = JSON.parse(el.dataset.extraPayload || '{}');
        } catch (e) {
            extraPayload = {};
        }
        return {
            el,
            storeUrl: el.dataset.storeUrl,
            updateUrlBase: el.dataset.updateUrlBase,
            destroyUrlBase: el.dataset.destroyUrlBase,
            reorderUrl: el.dataset.reorderUrl,
            supportsStepOnePrompt: el.dataset.supportsStepOnePrompt === '1',
            extraPayload: extraPayload,
        };
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : (window.csrfToken || '');
    }

    function buildStepListItem(step) {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center py-3';
        li.id = `step-item-${step.id}`;
        li.innerHTML = `
            <div class="d-flex align-items-center gap-3 flex-grow-1">
                <span class="badge bg-secondary rounded-pill">${step.order}</span>
                <div class="d-flex align-items-center gap-2 step-display">
                    <span class="fw-bold step-name-text"></span>
                </div>
                <div class="step-edit d-none flex-grow-1 d-flex gap-2 align-items-center">
                    <input type="text" class="form-control form-control-sm step-edit-input">
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep(${step.id}, 'up')" title="{{ __('Move Up') }}"><i class="bi bi-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep(${step.id}, 'down')" title="{{ __('Move Down') }}"><i class="bi bi-arrow-down"></i></button>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary btn-edit-step" onclick="toggleEditStep(${step.id})"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-success d-none btn-save-step" onclick="saveStep(${step.id})"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteStep(${step.id})"><i class="bi bi-trash"></i></button>
                </div>
            </div>`;
        // Set the name via textContent (not string-interpolated into the
        // template) so a step name can never be interpreted as HTML.
        li.querySelector('.step-name-text').textContent = step.name;
        li.querySelector('.step-edit-input').value = step.name;
        return li;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const cfg = modalConfig();
        if (!cfg) return;

        const addForm = document.getElementById('addStepForm');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const input = document.getElementById('newStepName');
                const name = input.value.trim();
                if (!name) return;

                fetch(cfg.storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(Object.assign({ name: name }, cfg.extraPayload)),
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.step) {
                        document.getElementById('stepsList').appendChild(buildStepListItem(data.step));
                        // Clear and refocus so the next step name can be typed
                        // straight away — this is the whole point of the fix.
                        input.value = '';
                        input.focus();
                        if (typeof showToast === 'function') showToast('{{ __("Step added.") }}', 'success');
                    } else if (typeof showToast === 'function') {
                        showToast(data.message || '{{ __("Failed to add step.") }}', 'danger');
                    }
                })
                .catch(() => { if (typeof showToast === 'function') showToast('{{ __("Failed to add step.") }}', 'danger'); });
            });
        }
    });

    window.toggleEditStep = function (id) {
        const item = document.getElementById(`step-item-${id}`);
        if (!item) return;
        item.querySelector('.step-display').classList.toggle('d-none');
        item.querySelector('.step-edit').classList.toggle('d-none');
        item.querySelector('.btn-edit-step').classList.toggle('d-none');
        item.querySelector('.btn-save-step').classList.toggle('d-none');
    };

    window.saveStep = function (id) {
        const cfg = modalConfig();
        if (!cfg) return;
        const item = document.getElementById(`step-item-${id}`);
        const newName = item.querySelector('.step-edit-input').value.trim();
        if (!newName) return;

        fetch(`${cfg.updateUrlBase}/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: newName }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                item.querySelector('.step-name-text').textContent = newName;
                window.toggleEditStep(id);
                if (typeof showToast === 'function') showToast('{{ __("Saved.") }}', 'success');
            } else if (typeof showToast === 'function') {
                showToast(data.message || '{{ __("Failed to save.") }}', 'danger');
            }
        })
        .catch(() => { if (typeof showToast === 'function') showToast('{{ __("Failed to save.") }}', 'danger'); });
    };

    window.deleteStep = function (id) {
        const cfg = modalConfig();
        if (!cfg) return;
        Swal.fire({
            title: '{{ __("Delete Step?") }}',
            text: "{{ __('This will remove this step from all employees.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: '{{ __("Yes, delete it!") }}',
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch(`${cfg.destroyUrlBase}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const li = document.getElementById(`step-item-${id}`);
                    if (li) li.remove();
                    if (typeof showToast === 'function') showToast('{{ __("Step deleted.") }}', 'success');
                } else if (typeof showToast === 'function') {
                    showToast(data.message || '{{ __("Failed to delete step.") }}', 'danger');
                }
            })
            .catch(() => { if (typeof showToast === 'function') showToast('{{ __("Failed to delete step.") }}', 'danger'); });
        });
    };

    function submitReorder(order, behavior, onDone) {
        const cfg = modalConfig();
        if (!cfg) return;
        const payload = { order: order };
        if (cfg.supportsStepOnePrompt) {
            payload.handle_step_one_behavior = behavior;
        }
        fetch(cfg.reorderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (behavior === 'auto_tick' && typeof showToast === 'function') {
                    showToast('{{ __("Order updated and employees processed.") }}', 'success');
                }
                if (onDone) onDone(true);
            } else {
                if (typeof showToast === 'function') showToast(data.message || '{{ __("Failed to reorder.") }}', 'danger');
                if (onDone) onDone(false);
            }
        })
        .catch(() => {
            if (typeof showToast === 'function') showToast('{{ __("Failed to reorder.") }}', 'danger');
            if (onDone) onDone(false);
        });
    }

    window.moveStep = function (id, direction) {
        const cfg = modalConfig();
        if (!cfg) return;
        const stepsList = document.getElementById('stepsList');
        const currentItem = document.getElementById(`step-item-${id}`);
        if (!stepsList || !currentItem) return;

        const firstLi = stepsList.querySelector('li');
        const currentFirstId = firstLi ? firstLi.id.replace('step-item-', '') : null;
        const previousSibling = currentItem.previousElementSibling;
        const nextSibling = currentItem.nextElementSibling;

        // Optimistic DOM move — happens immediately, no reload either way.
        if (direction === 'up') {
            if (previousSibling) stepsList.insertBefore(currentItem, previousSibling);
        } else {
            if (nextSibling) stepsList.insertBefore(nextSibling, currentItem);
        }

        const revertMove = function () {
            if (direction === 'up' && previousSibling) {
                stepsList.insertBefore(previousSibling, currentItem);
            } else if (direction === 'down' && nextSibling) {
                stepsList.insertBefore(currentItem, nextSibling);
            }
        };

        const newOrder = [];
        stepsList.querySelectorAll('li').forEach(li => newOrder.push(li.id.replace('step-item-', '')));
        const newFirstId = newOrder[0];

        if (cfg.supportsStepOnePrompt && currentFirstId && newFirstId && currentFirstId !== newFirstId) {
            Swal.fire({
                title: '{{ __("Change First Step?") }}',
                text: '{{ __("You are changing the first step. Select how to handle existing active employees:") }}',
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '{{ __("Auto-tick New Step 1") }}',
                denyButtonText: '{{ __("Just Move (No Data Change)") }}',
                cancelButtonText: '{{ __("Cancel") }}',
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#6c757d',
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    submitReorder(newOrder, 'auto_tick');
                } else if (result.isDenied) {
                    submitReorder(newOrder, 'none');
                } else {
                    // Cancelled — revert the optimistic DOM move in place,
                    // no reload needed.
                    revertMove();
                }
            });
        } else {
            submitReorder(newOrder, 'none', function (ok) {
                if (!ok) revertMove();
            });
        }
    };
})();
</script>
@endpush
@endonce
