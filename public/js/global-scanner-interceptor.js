document.addEventListener('DOMContentLoaded', function() {
    // Use Event Delegation to handle dynamic inputs
    document.body.addEventListener('click', function(e) {
        // Find closest input[type="file"]
        const target = e.target.closest('input[type="file"]');
        if (!target) return;

        // 1. Skip if it's the scanner's own internal input
        if (target.classList.contains('scanner-internal-input')) return;

        // 2. Skip if explicitly opted out
        if (target.hasAttribute('data-no-scanner')) return;

        // 3. Skip if the file type is strictly incompatible (e.g. Excel only)
        // If accept is empty, we assume it allows everything, so we default to scanner?
        // Or should we only interrupt if it explicitly allows images/pdf?
        // User said "every point in the program". Most are images.
        // Let's check if it EXCLUDES images.
        const accept = (target.getAttribute('accept') || '').toLowerCase();

        // Known non-image types that shouldn't use scanner
        const nonImageTypes = ['.xls', '.xlsx', '.csv', '.doc', '.docx', '.zip', '.rar'];

        // If accept is set and contains ONLY non-image types, skip.
        // But if it contains image/* or .pdf, or is mixed, use scanner.
        // Also if accept is empty, user might want to upload anything.
        // Strategy: If accept includes "image" or "pdf" OR is empty => Scanner.
        // If accept is strictly something else => Skip.

        let shouldUseScanner = true;

        if (accept) {
            const hasImageOrPdf = accept.includes('image') || accept.includes('pdf') || accept.includes('.jpg') || accept.includes('.png');
            if (!hasImageOrPdf) {
                // Check if it's strictly one of the non-image types
                const isNonImage = nonImageTypes.some(t => accept.includes(t));
                if (isNonImage) shouldUseScanner = false;
            }
        }

        if (!shouldUseScanner) return;

        // 4. PREVENT NATIVE PICKER
        e.preventDefault();

        // 5. Ensure ID exists
        if (!target.id) {
            target.id = 'scanner_input_' + Math.random().toString(36).substr(2, 9);
        }

        // 6. Open Scanner
        window.dispatchEvent(new CustomEvent('open-document-scanner', {
            detail: {
                inputId: target.id,
                // Pass existing file URL if available for editing?
                // The global interceptor handles "New Attachment".
                // "Edit" is usually a separate button logic handled by specific views.
            }
        }));
    });
});
