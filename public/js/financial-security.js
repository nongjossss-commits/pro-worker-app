window.FinancialSecurity = {
    checkAndRun: function(callback) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('/menu-check/finance', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.locked) {
                if (data.reason === 'disabled') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: data.message || 'This feature is disabled.'
                    });
                    return;
                }

                // Prompt for Password
                Swal.fire({
                    title: 'Security Check',
                    text: 'Please enter the password to access Financial features.',
                    input: 'password',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off',
                        // Not the login form — keep the browser from filling in the
                        // saved login password (it showed up as a row of dots) (2026-11-20)
                        autocomplete: 'new-password',
                        name: 'finance-menu-code',
                        'data-lpignore': 'true',
                        'data-1p-ignore': 'true'
                    },
                    inputValue: '',
                    didOpen: () => {
                        // a password manager may still fill it a moment later — empty
                        // it unless the user has started typing, and put the cursor in
                        const field = Swal.getInput();
                        if (!field) return;
                        let typed = false;
                        field.addEventListener('keydown', () => { typed = true; });
                        const clear = () => { if (!typed) field.value = ''; };
                        clear();
                        [60, 250, 600].forEach(ms => setTimeout(clear, ms));
                        field.focus();
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Unlock',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        return fetch('/menu-unlock/finance', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ password: password })
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.message || 'Incorrect password')
                                });
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `${error.message}`
                            )
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Unlocked successfully
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                });

            } else {
                // Not locked, run immediately
                if (typeof callback === 'function') {
                    callback();
                }
            }
        })
        .catch(err => {
            console.error('Security Check Error', err);
            Swal.fire('Error', 'Failed to verify access permissions.', 'error');
        });
    }
};
