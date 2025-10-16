document.addEventListener('DOMContentLoaded', function () {
    const terminateEmployeeModal = document.getElementById('terminateEmployeeModal');
    if (terminateEmployeeModal) {
        terminateEmployeeModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            // Extract info from data-* attributes
            const employeeId = button.getAttribute('data-employee-id');

            // Update the modal's content.
            const form = terminateEmployeeModal.querySelector('#terminate-form');
            if (form) {
                let actionUrl = form.getAttribute('action');
                // Replace the placeholder {employeeId} with the actual ID
                form.action = `/employees/${employeeId}/terminate`;
            }
        });
    }
});