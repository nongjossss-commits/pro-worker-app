<script>
    // Global function to open the Edit Modal
    window.openEditEmployeeModal = function(employeeId, itemId = null) {
        window.currentEditItemId = itemId; // Store Item ID for refresh logic
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

                // 3. Update DOM in-place (No Reload)
                if (window.currentEditItemId) {
                    // Fetch fresh card HTML and replace
                    fetch(`/workflow/item/${window.currentEditItemId}/card`)
                        .then(r => r.json())
                        .then(json => {
                            const oldCard = document.getElementById(`item-card-${window.currentEditItemId}`);
                            if (oldCard && json.html) {
                                oldCard.outerHTML = json.html;
                                // Optional: Re-init Alpine if necessary (Alpine v3 usually handles mutations)
                            }
                        })
                        .catch(err => console.error('Failed to refresh card:', err));
                } else if (data.employee) {
                    updateEmployeeCardInDom(data.employee);
                } else {
                    console.warn('No refresh context available.');
                }
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

    function updateEmployeeCardInDom(emp) {
        const card = document.getElementById(`employee-card-${emp.id}`);
        if (!card) return;

        // 1. Update Names
        const nameDiv = card.querySelector('.fw-bold.text-dark');
        if (nameDiv) {
            nameDiv.innerText = `${emp.employeeTitleEn || ''} ${emp.employeeNameEn || '-'}`;
        }
        const thaiNameDiv = card.querySelector('.text-muted.small');
        if (thaiNameDiv) {
            // Be careful to target the correct div, structure: Avatar -> Info -> [NameEn, NameTh, Age, Passport]
            // The structure in _employee_card.blade.php is:
            /*
                <div>
                    <div class="fw-bold text-dark">...</div>
                    <div class="text-muted small">...</div> (Thai Name)
                    <div class="small text-muted mt-1">...</div> (DOB/Age)
                    <div class="small text-muted mt-1">...</div> (Passport/Flag)
                </div>
            */
           // Select by order to be safe or text content match? Structure is safer if consistent.
           // Since we have reference to `nameDiv`, the next sibling is Thai Name.
           if (nameDiv.nextElementSibling) {
               nameDiv.nextElementSibling.innerText = `${emp.employeeTitleTh || ''} ${emp.employeeNameTh || '-'}`;
           }
        }

        // 2. Update Age / DOB
        const dobDiv = card.querySelector('div.small.text-muted.mt-1 span[title="Date of Birth"]');
        // Or generic selector: third div in info container
        if (dobDiv) {
            // We need to format date. JS doesn't have Laravel's format.
            // Let's rely on raw date or simple formatting.
            let dobStr = '-';
            if (emp.employeeDob) {
                const date = new Date(emp.employeeDob);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                dobStr = `${day}/${month}/${year}`;
            }
            dobDiv.innerHTML = `<i class="bi bi-calendar-event me-1"></i> ${dobStr} (Age : ${emp.age || '-'} Years)`;
        }

        // 3. Update Passport & Nationality
        // Fourth div in info block
        // This is tricky because it has mixed content (Passport icon, text, flag icon, text)
        // Structure:
        /*
            <div class="small text-muted mt-1">
                <span class="me-2"><i class="bi bi-passport..."></i> ... </span>
                <span class="d-inline-flex..."><i class="bi bi-geo..."></i> <img flag> ... </span>
            </div>
        */
       // Let's find the container that holds passport info
       const passportSpan = card.querySelector('.bi-passport')?.closest('span');
       if (passportSpan) {
           passportSpan.innerHTML = `<i class="bi bi-passport text-primary me-1"></i> ${emp.employeePassport || '-'}`;
       }

       const nationalitySpan = card.querySelector('.bi-geo-alt-fill')?.closest('span');
       if (nationalitySpan) {
           // We need country code for flag.
           // We don't have the CountryHelper in JS.
           // Map manually or pass from backend.
           // Simple mapping for common ones:
           const natMap = { 'เมียนมา': 'mm', 'ลาว': 'la', 'กัมพูชา': 'kh', 'เวียดนาม': 'vn', 'ไทย': 'th' };
           const code = natMap[emp.employeeNationality] || '';
           let imgHtml = '';
           if (code) {
               imgHtml = `<img src="/images/flags/${code}.png" alt="${code}" style="width: 16px; height: 12px; margin-right: 5px;">`;
           }
           nationalitySpan.innerHTML = `<i class="bi bi-geo-alt-fill text-danger me-1"></i> ${imgHtml} ${emp.employeeNationality || '-'}`;
       }

       // 4. Update Photo
       const avatarImg = card.querySelector('.avatar-container img');
       if (avatarImg && emp.employeePhoto) {
           // Update src. Note: emp.employeePhoto is relative path "employee_files/...".
           // We need full URL.
           // In blade we used Storage::disk('public')->url().
           // Usually /storage/...
           avatarImg.src = `/storage/${emp.employeePhoto}?t=${new Date().getTime()}`; // Cache bust
       } else if (emp.employeePhoto) {
           // If avatar was placeholder div, replace with img
           const avatarContainer = card.querySelector('.avatar-container');
           if (avatarContainer) {
               // Remove existing content (placeholder div)
               // Keep badges! Badges are absolute positioned.
               // It's safer to just set innerHTML but we lose badges if not careful.
               // Let's just find the placeholder div and replace it.
               const placeholder = avatarContainer.querySelector('.rounded-circle.bg-secondary');
               if (placeholder) {
                   const newImg = document.createElement('img');
                   newImg.src = `/storage/${emp.employeePhoto}?t=${new Date().getTime()}`;
                   newImg.className = 'rounded-circle shadow-sm';
                   newImg.style.width = '50px';
                   newImg.style.height = '50px';
                   newImg.style.objectFit = 'cover';
                   placeholder.replaceWith(newImg);
               }
           }
       }

       // 5. Update Checkbox Data Attributes (For bulk actions)
       const checkbox = card.querySelector('.employee-checkbox');
       if (checkbox) {
           checkbox.dataset.nameTh = emp.employeeNameTh || '';
           checkbox.dataset.nameEn = emp.employeeNameEn || '';
           checkbox.dataset.photo = emp.employeePhoto ? `/storage/${emp.employeePhoto}` : '';
       }
    }
</script>
