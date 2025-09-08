document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.live-search-input');

    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const targetTableId = this.dataset.targetTable;
            const table = document.getElementById(targetTableId);
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                if (textContent.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
