<script>
    // Global function to open the Edit Modal
    window.openEditEmployeeModal = function(employeeId) {
        const modalEl = document.getElementById('editEmployeeModal');
        const modalBody = document.getElementById('editEmployeeModalBody');
        const modal = new bootstrap.Modal(modalEl);

        // Show loading spinner first
        modalBody.innerHTML = `
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        modal.show();

        // Fetch the Edit Form Partial
        fetch(`/employees/${employeeId}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load edit form');
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;

            // Re-initialize scripts for the form
            if (window.initEmployeeEditForm) {
                window.initEmployeeEditForm();
            }

            // Attach submit handler for AJAX submission
            const form = modalBody.querySelector('form');
            if (form) {
                form.addEventListener('submit', handleEditFormSubmit);
            }
        })
        .catch(error => {
            console.error(error);
            modalBody.innerHTML = `<div class="alert alert-danger">Error loading form: ${error.message}</div>`;
        });
    };

    function handleEditFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = 'Saving...';

        // Clear previous errors
        form.querySelectorAll('.alert-danger').forEach(el => el.remove());

        fetch(form.action, {
            method: 'POST', // Method override is in formData (_method=PUT)
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                 // Check if it's a validation error (422)
                 if (response.status === 422) {
                     return response.json().then(data => {
                         const errors = Object.values(data.errors).flat();
                         throw new Error(errors.join('<br>'));
                     });
                 }
                 return response.json().then(data => { throw new Error(data.message || 'Server error'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Success!
                // 1. Close Modal
                const modalEl = document.getElementById('editEmployeeModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) modal.hide();

                // 2. Show Toast
                if(typeof showToast === 'function') {
                    showToast('Employee updated successfully', 'success');
                } else {
                    alert('Employee updated successfully');
                }

                // 3. Reload Page to reflect changes (as per request "Load on same page")
                // Capture state before reload
                const formAction = form.action; // e.g., .../employees/123
                const segments = formAction.split('/');
                const employeeId = segments[segments.length - 1] || segments[segments.length - 2]; // Handle trailing slash if any

                // Find the card to get Employer ID
                // Note: The ID might be passed in differently, but extracting from URL is safer if DOM is gone?
                // Actually DOM is still here.
                // The modal is open, but we need the background card.
                // We can't rely on 'employeeId' variable scope here easily unless we parse it.
                // Let's try to find the card using the ID from the form action which is typically /employees/{id}

                let empIdVal = null;
                // Try to find ID from form action .../employees/{id}
                const match = formAction.match(/\/employees\/(\d+)/);
                if (match && match[1]) {
                    empIdVal = match[1];
                }

                if (empIdVal) {
                    const card = document.getElementById(`employee-card-${empIdVal}`);
                    if (card && card.dataset.employerId) {
                        sessionStorage.setItem('registration_restore_employer_id', card.dataset.employerId);
                        sessionStorage.setItem('registration_restore_employee_id', empIdVal);
                    }
                }

                window.location.reload();

            }
        })
        .catch(error => {
            console.error(error);
            // Show error in form
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger mt-3';
            errorDiv.innerHTML = `<strong>Error:</strong><br>${error.message}`;
            form.prepend(errorDiv);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalBtnText;
        });
    }
</script>
