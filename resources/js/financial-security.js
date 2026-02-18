window.FinancialSecurity = {
    checkAndRun: function(callback) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('/menu-check/finance', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) {
                // If the response is not OK (e.g. 500, 404), throw an error
                // Try to get JSON error message first, otherwise use status text
                return res.text().then(text => {
                    try {
                        const json = JSON.parse(text);
                        throw new Error(json.message || `Server Error (${res.status})`);
                    } catch (e) {
                        throw new Error(`Server Error (${res.status}): ${res.statusText}`);
                    }
                });
            }
            return res.json();
        })
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
                        autocorrect: 'off'
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
                            try {
                                callback();
                            } catch (e) {
                                console.error('Callback Execution Error', e);
                                Swal.fire('Error', 'Action failed: ' + e.message, 'error');
                            }
                        }
                    }
                });

            } else {
                // Not locked, run immediately
                if (typeof callback === 'function') {
                    try {
                        callback();
                    } catch (e) {
                        console.error('Callback Execution Error', e);
                        Swal.fire('Error', 'Action failed: ' + e.message, 'error');
                    }
                }
            }
        })
        .catch(err => {
            console.error('Security Check Error', err);
            // Show more specific error message if possible
            const message = err.message || 'Failed to verify access permissions.';
            Swal.fire('Error', message, 'error');
        });
    }
};
