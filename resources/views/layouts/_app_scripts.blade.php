<script>
document.addEventListener('hidden.bs.modal', function() {
    // If no modal is currently shown, clean up all orphaned backdrops
    if (!document.querySelector('.modal.show')) {
        document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
});
</script>

<!-- Universal Preview Modal -->
{{-- modal-fullscreen-sm-down: phones (<576px) get an edge-to-edge fullscreen
     modal so the compact preview grid actually uses the whole viewport
     instead of a narrow center column. modal-xl on tablet+ / wide screens is
     expanded further via a media query inside the preview partial. --}}
<div class="modal fade" id="universalPreviewModal" tabindex="-1" aria-labelledby="universalPreviewModalLabel" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalPreviewModalLabel">{{ __('Preview Data') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewModalLabel">{{ __('PDF Preview') }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <!-- Image Zoom Controls (Initially Hidden) -->
                    <div id="imageZoomControls" class="d-none btn-group btn-group-sm me-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="zoomImage(-0.1)" title="Zoom Out"><i class="bi bi-dash-lg"></i></button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetZoom()" title="Reset Zoom"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button type="button" class="btn btn-outline-secondary" onclick="zoomImage(0.1)" title="Zoom In"><i class="bi bi-plus-lg"></i></button>
                    </div>

                    <a href="" id="pdfDownloadBtn" class="btn btn-sm btn-primary" download>
                        <i class="bi bi-download"></i> {{ __('Download') }}
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 h-100 overflow-hidden bg-dark position-relative">
                <iframe id="pdfPreviewFrame" src="" class="w-100 h-100" style="border: none;"></iframe>

                <!-- Image Preview Container -->
                <div id="imagePreviewContainer" class="d-none w-100 h-100 bg-dark" style="position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <div id="imageLoadingSpinner" class="spinner-border text-light position-absolute top-50 start-50 translate-middle" role="status" style="z-index: 5;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <!-- Image will be manipulated via CSS transform -->
                    <img id="imagePreview" src="" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 0 20px rgba(0,0,0,0.5); transform-origin: 0 0; transition: transform 0.1s ease-out;">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let imageZoomLevel = 1.0;
let isImageFitToScreen = true;

window.viewPDF = function(url, title = 'PDF Preview') {
    // Check if URL is valid
    if (!url) {
        console.error('viewPDF called with empty URL');
        return;
    }

    // Fallback if bootstrap is not loaded yet
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap not loaded, opening in new tab');
        window.open(url, '_blank');
        return;
    }

    const modalEl = document.getElementById('pdfPreviewModal');
    if (!modalEl) {
        console.error('PDF Preview Modal element not found');
        window.open(url, '_blank');
        return;
    }

    const iframe = document.getElementById('pdfPreviewFrame');
    const imageContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const imageSpinner = document.getElementById('imageLoadingSpinner');
    const zoomControls = document.getElementById('imageZoomControls');
    const downloadBtn = document.getElementById('pdfDownloadBtn');
    const modalTitle = document.getElementById('pdfPreviewModalLabel');

    try {
        // Construct URL object to parse parts
        // Use window.location.origin as base for relative URLs
        const pdfUrl = new URL(url, window.location.origin);

        // Add disposition=inline for local URLs or routes
        // We check if hostname matches to allow protocol mismatches (http vs https behind proxy)
        if (pdfUrl.hostname === window.location.hostname) {
             pdfUrl.searchParams.set('disposition', 'inline');
        }

        if(modalTitle) modalTitle.textContent = title;
        if(downloadBtn) downloadBtn.href = url; // Keep original URL for download button

        // Detect if file is an image based on extension
        const pathname = pdfUrl.pathname.toLowerCase();
        const isImage = /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(pathname);

        if (isImage) {
            // Image Mode
            if(iframe) iframe.style.display = 'none';
            if(imageContainer) imageContainer.classList.remove('d-none');
            if (zoomControls) zoomControls.classList.remove('d-none');

            // Reset Zoom
            isImageFitToScreen = true;
            imageZoomLevel = 1.0;
            if (typeof updateImageStyle === 'function') updateImageStyle();

            // Load Image
            if(imageSpinner) imageSpinner.classList.remove('d-none');
            if(imagePreview) {
                imagePreview.style.opacity = '0.5';
                imagePreview.src = pdfUrl.toString();

                imagePreview.onload = function() {
                    if(imageSpinner) imageSpinner.classList.add('d-none');
                    imagePreview.style.opacity = '1';
                };
                imagePreview.onerror = function() {
                    if(imageSpinner) imageSpinner.classList.add('d-none');
                    console.error('Image failed to load');
                };
            }

        } else {
            // PDF / Other Mode
            if(iframe) {
                iframe.style.display = 'block';
                iframe.src = pdfUrl.toString();
            }
            if(imageContainer) imageContainer.classList.add('d-none');
            if (zoomControls) zoomControls.classList.add('d-none');
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

    } catch (e) {
        console.error('Error opening preview:', e);
        // Fallback to new tab
        window.open(url, '_blank');
    }
};

(function() {
    let isPanning = false;
    let startX = 0, startY = 0;
    let translateX = 0, translateY = 0;
    let panStartX = 0, panStartY = 0;

    // Set initial transform state
    window.updateImageStyle = function() {
        const img = document.getElementById('imagePreview');
        if (!img) return;

        if (isImageFitToScreen) {
            translateX = 0;
            translateY = 0;
            img.style.transform = `translate(0px, 0px) scale(1)`;
            img.style.cursor = 'zoom-in';
        } else {
            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${imageZoomLevel})`;
            img.style.cursor = isPanning ? 'grabbing' : 'grab';
        }
    };

    window.zoomImage = function(delta, mouseX = null, mouseY = null) {
        const img = document.getElementById('imagePreview');
        const imgContainer = document.getElementById('imagePreviewContainer');
        if (!img || !imgContainer) return;

        // Container bounding rect
        const rect = imgContainer.getBoundingClientRect();

        // If no mouse coordinates provided, zoom into the center of the container
        if (mouseX === null || mouseY === null) {
            mouseX = rect.width / 2;
            mouseY = rect.height / 2;
        }

        const oldZoomLevel = isImageFitToScreen ? 1.0 : imageZoomLevel;
        let newZoomLevel = oldZoomLevel + delta;

        if (newZoomLevel < 0.1) newZoomLevel = 0.1;
        if (newZoomLevel > 10.0) newZoomLevel = 10.0;

        // Prevent action if zoom limit reached
        if (newZoomLevel === oldZoomLevel) return;

        isImageFitToScreen = false;
        imageZoomLevel = newZoomLevel;

        // Image coordinates relative to the screen
        const imgRect = img.getBoundingClientRect();

        // Cursor position relative to the image's top-left corner (in screen pixels)
        const cursorX_relativeToImg = (rect.left + mouseX) - imgRect.left;
        const cursorY_relativeToImg = (rect.top + mouseY) - imgRect.top;

        // Calculate how much the pixel under the cursor moves due to scaling
        const ratio = newZoomLevel / oldZoomLevel;
        const shiftX = cursorX_relativeToImg * (1 - ratio);
        const shiftY = cursorY_relativeToImg * (1 - ratio);

        // Adjust translation to keep the pixel exactly under the cursor
        translateX += shiftX;
        translateY += shiftY;

        window.updateImageStyle();
    };

    window.resetZoom = function() {
        isImageFitToScreen = true;
        imageZoomLevel = 1.0;
        translateX = 0;
        translateY = 0;
        window.updateImageStyle();
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Prevent default pinch zoom on the page if container hovered
        document.body.addEventListener('wheel', function(e) {
            const imgContainer = document.getElementById('imagePreviewContainer');
            if (imgContainer && imgContainer.contains(e.target)) {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    // Zoom amount
                    const delta = e.deltaY < 0 ? 0.2 : -0.2;

                    const rect = imgContainer.getBoundingClientRect();
                    const mouseX = e.clientX - rect.left;
                    const mouseY = e.clientY - rect.top;

                    window.zoomImage(delta, mouseX, mouseY);
                }
            }
        }, { passive: false });

        // Handle Panning
        document.body.addEventListener('mousedown', function(e) {
            const imgContainer = document.getElementById('imagePreviewContainer');
            if (imgContainer && imgContainer.contains(e.target)) {
                if (isImageFitToScreen) return;

                if (e.target.tagName === 'IMG') {
                    e.preventDefault(); // Prevent native image drag
                }

                isPanning = true;
                startX = e.clientX;
                startY = e.clientY;
                panStartX = translateX;
                panStartY = translateY;

                // Temporarily disable transition during drag for smoothness
                const img = document.getElementById('imagePreview');
                if (img) img.style.transition = 'none';

                window.updateImageStyle();
            }
        });

        // Use window for mouseup/mousemove so dragging doesn't break if mouse leaves container
        window.addEventListener('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                const img = document.getElementById('imagePreview');
                if (img) img.style.transition = 'transform 0.1s ease-out';
                window.updateImageStyle();
            }
        });

        window.addEventListener('mousemove', function(e) {
            if (!isPanning) return;
            e.preventDefault();

            // Calculate pixel movement
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            // Apply new translation
            translateX = panStartX + dx;
            translateY = panStartY + dy;

            window.updateImageStyle();
        });
    });
})();

// Full-Featured Address Management Script
document.addEventListener('DOMContentLoaded', function () {
    (async () => {
    // --- Configuration & Global State ---
    const thaiDataUrl = "/thai-addresses"; // Hardcoded URL
    let thaiAddressData = [];
    let dataLoaded = false;
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return; // Exit if the modal isn't on the page

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveBtn = document.getElementById('saveAddressBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Form Fields ---
    const fields = {
        id: document.getElementById('address_id'),
        addressable_id: document.getElementById('addressable_id'),
        addressable_type: document.getElementById('addressable_type'),
        type: document.getElementById('address_type'),
        addrNo: document.getElementById('addrNo'),
        addrNoEn: document.getElementById('addrNoEn'),
        addrMoo: document.getElementById('addrMoo'),
        addrMooEn: document.getElementById('addrMooEn'),
        addrSoi: document.getElementById('addrSoi'),
        addrSoiEn: document.getElementById('addrSoiEn'),
        addrRoad: document.getElementById('addrRoad'),
        addrRoadEn: document.getElementById('addrRoadEn'),
        addrProvince: document.getElementById('addrProvince'),
        addrProvinceEn: document.getElementById('addrProvinceEn'),
        addrDistrict: document.getElementById('addrDistrict'),
        addrDistrictEn: document.getElementById('addrDistrictEn'),
        addrSubDistrict: document.getElementById('addrSubDistrict'),
        addrSubDistrictEn: document.getElementById('addrSubDistrictEn'),
        addrZipCode: document.getElementById('addrZipCode')
    };

    await fetchThaiAddressData();

    // --- Data Loading ---
    async function fetchThaiAddressData() {
        if (dataLoaded) return;
        try {
            const response = await fetch(thaiDataUrl);
            if (!response.ok) throw new Error('Network response was not ok.');
            thaiAddressData = await response.json();
            dataLoaded = true;
            populateProvinces();
        } catch (error) {
            console.error('Failed to fetch Thai address data:', error);
            showToast('{{ __('Failed to load address data') }}', 'danger');
        }
    }

    // --- Dropdown Population ---
    function populateProvinces() {
        fields.addrProvince.innerHTML = '<option value="">{{ __('Select Province') }}</option>';
        const uniqueProvinces = [...new Map(thaiAddressData.map(item => [item['province_th'].trim(), item])).values()];
        uniqueProvinces.sort((a, b) => a.province_th.trim().localeCompare(b.province_th.trim(), 'th'));
        uniqueProvinces.forEach(item => {
            const option = new Option(item.province_th.trim(), item.province_th.trim());
            fields.addrProvince.add(option);
        });

        // Notify Alpine.js about options update
        fields.addrProvince.dispatchEvent(new Event('options-updated'));
    }

    function populateDistricts(province) {
        fields.addrDistrict.innerHTML = '<option value="">{{ __('Select District') }}</option>';
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>'; // Reset sub-districts
        fields.addrDistrict.disabled = true;
        fields.addrSubDistrict.disabled = true;

        if (!province) {
            // Trigger Alpine.js observer explicitly if returning early
            fields.addrDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrDistrict.dispatchEvent(new Event('change'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));
            return;
        }

        const districts = [...new Set(thaiAddressData.filter(d => d.province_th.trim() === province.trim()).map(d => d.district_th.trim()))];
        districts.sort((a, b) => a.localeCompare(b, 'th'));
        districts.forEach(district => {
            const option = new Option(district, district);
            fields.addrDistrict.add(option);
        });

        if (districts.length > 0) {
            fields.addrDistrict.disabled = false;
        }

        // Notify Alpine.js about the new options and state change
        fields.addrDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrDistrict.dispatchEvent(new Event('change'));
        fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrSubDistrict.dispatchEvent(new Event('change'));
    }

    function populateSubDistricts(province, district) {
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>';
        fields.addrSubDistrict.disabled = true;

        if (!province || !district) {
            fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));
            return;
        }

        const subDistricts = thaiAddressData.filter(d => d.province_th.trim() === province.trim() && d.district_th.trim() === district.trim());
        const uniqueSubDistricts = [...new Map(subDistricts.map(item => [item['subdistrict_th'].trim(), item])).values()];
        uniqueSubDistricts.sort((a, b) => a.subdistrict_th.trim().localeCompare(b.subdistrict_th.trim(), 'th'));
        uniqueSubDistricts.forEach(sub => {
            const option = new Option(sub.subdistrict_th.trim(), sub.subdistrict_th.trim());
            fields.addrSubDistrict.add(option);
        });

        if (uniqueSubDistricts.length > 0) {
            fields.addrSubDistrict.disabled = false;
        }

        // Notify Alpine.js
        fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrSubDistrict.dispatchEvent(new Event('change'));
    }

    // --- Event Listeners for Dropdowns ---
    fields.addrProvince.addEventListener('change', function() {
        populateDistricts(this.value);
        const selectedData = thaiAddressData.find(d => d.province_th.trim() === this.value.trim());
        fields.addrProvinceEn.value = selectedData ? selectedData.province_en.trim() : '';
        fields.addrDistrictEn.value = '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrDistrict.addEventListener('change', function() {
        populateSubDistricts(fields.addrProvince.value, this.value);
        const selectedData = thaiAddressData.find(d => d.province_th.trim() === fields.addrProvince.value.trim() && d.district_th.trim() === this.value.trim());
        fields.addrDistrictEn.value = selectedData ? selectedData.district_en.trim() : '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrSubDistrict.addEventListener('change', function() {
        const selectedData = thaiAddressData.find(d =>
            d.province_th.trim() === fields.addrProvince.value.trim() &&
            d.district_th.trim() === fields.addrDistrict.value.trim() &&
            d.subdistrict_th.trim() === this.value.trim()
        );
        if (selectedData) {
            fields.addrSubDistrictEn.value = selectedData.subdistrict_en.trim();
            fields.addrZipCode.value = selectedData.zip_code ? String(selectedData.zip_code).trim() : '';
        }
    });

    // --- Modal Opening Logic ---
    addressModalEl.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        if (!button) return;

        const isAddButton = button.matches('.add-address-btn') || button.matches('.temp-edit-address-btn') === false && button.closest('.add-address-btn');
        const isEditButton = button.matches('.edit-address-btn') || button.closest('.edit-address-btn');

        // Identify the actual button element in case a child element (like an icon) was clicked
        const targetBtn = isAddButton ? (button.matches('.add-address-btn') ? button : button.closest('.add-address-btn')) :
                          isEditButton ? (button.matches('.edit-address-btn') ? button : button.closest('.edit-address-btn')) : null;

        if (isAddButton && targetBtn) {
            addressForm.reset();
            fields.id.value = '';
            fields.addressable_id.value = targetBtn.dataset.addressableId;
            if (targetBtn.dataset.addressableType) {
                fields.addressable_type.value = targetBtn.dataset.addressableType;
            } else {
                fields.addressable_type.value = 'App\\Models\\Employer';
            }
            fields.type.value = targetBtn.dataset.type;

            // Re-trigger Alpine's reactivity by firing a change event
            fields.addrProvince.dispatchEvent(new Event('change'));
            fields.addrDistrict.dispatchEvent(new Event('change'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));

            document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
        } else if (isEditButton && targetBtn) {
            const addressId = targetBtn.dataset.addressId;
            document.getElementById('addressModalLabel').textContent = '{{ __('Loading...') }}';

            // Disable save button while loading
            saveBtn.disabled = true;

            // Perform fetch without blocking the modal rendering (no await in the event listener)
            fetch(`/addresses/${addressId}/edit`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch address data.');
                    return response.json();
                })
                .then(data => {
                    addressForm.reset();
                    for (const key in data) {
                        if (fields[key]) {
                           fields[key].value = data[key];
                        }
                    }

                    populateDistricts(data.addrProvince);
                    fields.addrDistrict.value = data.addrDistrict;

                    populateSubDistricts(data.addrProvince, data.addrDistrict);
                    fields.addrSubDistrict.value = data.addrSubDistrict;

                    // Trigger Alpine.js to re-read values
                    fields.addrProvince.dispatchEvent(new Event('change'));
                    fields.addrDistrict.dispatchEvent(new Event('change'));
                    fields.addrSubDistrict.dispatchEvent(new Event('change'));

                    document.getElementById('addressModalLabel').textContent = '{{ __('Edit Address') }}';
                })
                .catch(error => {
                    console.error('Error fetching address for edit:', error);
                    showToast('{{ __('Failed to fetch address data') }}', 'danger');
                    addressModal.hide();
                })
                .finally(() => {
                    saveBtn.disabled = false;
                });
        }
    });

    // --- Save Logic ---
    saveBtn.addEventListener('click', async function() {
        const formData = new FormData(addressForm);
        const addressId = fields.id.value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = 'POST';

        if (addressId) {
            formData.append('_method', 'PUT');
        }

        try {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Saving...') }}`;

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                     let errorMsg = Object.values(result.errors).flat().join('\\n');
                     throw new Error(errorMsg);
                }
                throw new Error(result.message || '{{ __('An unknown error occurred') }}');
            }

            showToast(result.message || '{{ __('Address saved successfully') }}', 'success');
            addressModal.hide();

            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            console.error('Save address error:', error);
            showToast(error.message || '{{ __('Error saving address') }}', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '{{ __('Save') }}';
        }
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        populateDistricts('');
        document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
    });
    })();
});
</script>

<script>
// --- Global Selection Manager (Persists across pages/searches) ---
const STORAGE_KEY = 'selectedEmployeeData';

// Helper: Get all stored data
window.getGlobalSelectedData = function() {
    const stored = sessionStorage.getItem(STORAGE_KEY);
    try {
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        console.error('Error parsing selectedEmployeeData', e);
        return [];
    }
};

// Helper: Get just IDs sorted by selection order
window.getGlobalSelectedIds = function() {
    return window.getGlobalSelectedData()
        .sort((a, b) => (a.selection_order || 0) - (b.selection_order || 0))
        .map(item => item.id);
};

document.addEventListener('DOMContentLoaded', function () {
    // Stores array of objects: { id: "1", employer_id: "5", selection_order: 1 }
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const bulkActionBar = document.querySelector('.bulk-action-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionButton = bulkActionBar ? bulkActionBar.querySelector('button') : null;

    // Counter for tracking selection order
    window._selectionOrderCounter = (function() {
        const existing = window.getGlobalSelectedData();
        let maxOrder = 0;
        existing.forEach(item => {
            if (item.selection_order && item.selection_order > maxOrder) {
                maxOrder = item.selection_order;
            }
        });
        return maxOrder;
    })();

    // Helper: Save data
    window.setGlobalSelectedData = function(data) {
        // Ensure uniqueness by ID
        const unique = [];
        const map = new Map();
        for (const item of data) {
            if(!map.has(String(item.id))) {
                map.set(String(item.id), true);
                unique.push(item);
            }
        }
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(unique));
        updateUI();
    };

    // Helper: Add items (accepts array of {id, employer_id})
    function addItems(newItems) {
        const current = window.getGlobalSelectedData();
        // Filter out existing items that are being re-added (to update them with potentially newer data)
        const newIds = newItems.map(i => String(i.id));
        const currentFiltered = current.filter(i => !newIds.includes(String(i.id)));
        // Assign selection_order to new items that don't have one
        newItems.forEach(item => {
            if (!item.selection_order) {
                window._selectionOrderCounter++;
                item.selection_order = window._selectionOrderCounter;
            }
        });
        const combined = [...currentFiltered, ...newItems];
        window.setGlobalSelectedData(combined);
    }

    // Helper: Remove items by ID
    function removeItemsByIds(idsToRemove) {
        const current = window.getGlobalSelectedData();
        const filtered = current.filter(item => !idsToRemove.includes(String(item.id)));
        window.setGlobalSelectedData(filtered);
    }
    window.removeItemsByIds = removeItemsByIds;

    // Clear all selections
    window.clearGlobalSelection = function() {
        sessionStorage.removeItem(STORAGE_KEY);
        // Uncheck all visible
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        if(selectAllCheckbox) selectAllCheckbox.checked = false;
        updateUI();
    };

    // UI Updater
    function updateUI() {
        const allData = window.getGlobalSelectedData();
        const count = allData.length;
        const allIds = allData.map(item => String(item.id));

        if (bulkActionBar) {
            if (count > 0) {
                bulkActionBar.style.display = 'flex';
                if (selectedCountSpan) selectedCountSpan.textContent = count;
                if (bulkActionButton) bulkActionButton.disabled = false;
            } else {
                bulkActionBar.style.display = 'none';
                if (bulkActionButton) bulkActionButton.disabled = true;
            }
        }

        // Update View Selected Button Count (Global Badge)
        const viewSelectedBadge = document.getElementById('view-selected-count');
        if (viewSelectedBadge) viewSelectedBadge.textContent = count;

        // Sync individual checkboxes dynamically across the page
        const currentCheckboxes = document.querySelectorAll('.employee-checkbox');
        let visibleCount = 0;
        let visibleCheckedCount = 0;

        currentCheckboxes.forEach(cb => {
            cb.checked = allIds.includes(String(cb.value));

            // Check if it's visible to determine "Select All" state
            const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper') || cb.closest('tr');
            const accordionCollapse = cb.closest('.accordion-collapse');
            const isHiddenByAccordion = accordionCollapse && !accordionCollapse.classList.contains('show');
            const isHidden = (cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled') || cardWrapper.style.display === 'none')) || isHiddenByAccordion;

            if (!isHidden) {
                visibleCount++;
                if (cb.checked) {
                    visibleCheckedCount++;
                }
            }
        });

        // Sync "Select All" checkbox state based on VISIBLE items
        if (selectAllCheckbox) {
            if (visibleCount > 0 && visibleCheckedCount === visibleCount) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (visibleCheckedCount > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }

        // Also fire a custom event so other components (like employer-select-all) can update
        document.dispatchEvent(new Event('global-selection-updated'));
    }

    // --- Initialization ---

    // Note: We use Event Delegation for the individual checkboxes to support dynamic content (AJAX)
    // The 'employeeCheckboxes' variable defined above is static. We will re-query it when needed inside updateUI.

    // Helper to extract rich data
    function getEmployeeData(cb) {
        return {
            id: cb.value,
            employer_id: cb.dataset.employerId || '',
            name_th: cb.dataset.nameTh || '',
            name_en: cb.dataset.nameEn || '',
            title_th: cb.dataset.titleTh || '',
            title_en: cb.dataset.titleEn || '',
            nationality: cb.dataset.nationality || '',
            photo: cb.dataset.photo || '',
            employer_name: cb.dataset.employerName || '',
            title_th: cb.dataset.titleTh || '',
            title_en: cb.dataset.titleEn || '',
            nationality: cb.dataset.nationality || '',
            country_code: cb.dataset.countryCode || '',
            gender: cb.dataset.gender || '',
            insurance_type: cb.dataset.insuranceType || '',
            passport: cb.dataset.passport || '',
            production_item_id: cb.dataset.productionItemId || ''
        };
    }

    // Expose UI refresh globally (for AJAX loaded content)
    window.refreshGlobalSelectionUI = function() {
        const savedIds = window.getGlobalSelectedIds();
        // Re-query checkboxes to include newly added ones
        const currentCheckboxes = document.querySelectorAll('.employee-checkbox');
        currentCheckboxes.forEach(cb => {
            if (savedIds.includes(String(cb.value))) {
                cb.checked = true;
            } else {
                cb.checked = false; // Ensure unchecked if not in state
            }
        });
        updateUI();
    };

    // 1. Restore state from storage on load
    window.refreshGlobalSelectionUI();

    // 2. Handle Individual Checkbox Changes (Event Delegation)
    document.body.addEventListener('change', function (e) {
        if (e.target.matches('.employee-checkbox')) {
            const checkbox = e.target;
            const data = getEmployeeData(checkbox);
            if (checkbox.checked) {
                addItems([data]);
            } else {
                removeItemsByIds([data.id]);
            }
        }
    });

    // 2.5 ═══ Selection Mode — Click empty card surface to toggle checkbox ═══
    // After user manually checks the FIRST checkbox, clicking on an empty
    // (non-interactive) part of another employee card toggles its selection.
    // Clicks on buttons, links, inputs, badges, drag handles, Alpine @click
    // bindings, etc. are explicitly excluded so they perform their own action
    // without ALSO selecting the row.
    (function() {
        let mouseDownPos = null;
        const DRAG_THRESHOLD = 6; // px — anything beyond this is treated as a drag, not a click

        // CSS selectors for elements that should NEVER trigger a card toggle.
        const INTERACTIVE_SELECTOR = [
            'a', 'button', 'input', 'textarea', 'select', 'label',
            '[contenteditable]', '[role="button"]', '[role="link"]',
            '[onclick]', '[draggable="true"]',
            '[data-bs-toggle]', '[data-bs-dismiss]', '[data-bs-target]',
            '.form-check-input',
            '.btn', '.btn-group',
            '.dropdown', '.dropdown-toggle', '.dropdown-menu', '.dropdown-item',
            '.swal2-container', '.modal',
            '.accordion-button',
            '.cursor-pointer', '.cursor-grab',
            '.btn-preview',
            'img', '.badge'
        ].join(', ');

        // Alpine.js (and similar libraries) attach click handlers via attributes
        // such as @click, @click.stop, x-on:click, x-on:click.away, etc.
        // CSS attribute selectors can't match attribute-name prefixes so we
        // walk the ancestor chain and inspect attribute names manually.
        function hasAlpineClickHandler(el) {
            let n = el;
            while (n && n.nodeType === 1 && n !== document.body) {
                const attrs = n.attributes;
                for (let i = 0; i < attrs.length; i++) {
                    const name = attrs[i].name;
                    if (name.startsWith('@click') || name.startsWith('x-on:click')) {
                        return true;
                    }
                }
                n = n.parentElement;
            }
            return false;
        }

        function isInSelectionMode() {
            return (window.getGlobalSelectedData?.() || []).length > 0;
        }

        // Walk up from click target to find the smallest containing element
        // that has exactly one .employee-checkbox descendant — that's the card.
        function findCard(target) {
            let el = target;
            let safety = 0;
            while (el && el !== document.body && safety++ < 20) {
                // Looking for the immediate ancestor that contains an .employee-checkbox
                const cb = el.querySelector('.employee-checkbox');
                if (cb) {
                    // Make sure this is the only checkbox under this element
                    // (otherwise we'd be matching the page-level container)
                    const allCbs = el.querySelectorAll('.employee-checkbox');
                    if (allCbs.length === 1) {
                        return { card: el, checkbox: cb };
                    }
                    // If multiple, we've gone too far up — return null
                    return null;
                }
                el = el.parentElement;
            }
            return null;
        }

        function recordMouseDown(e) {
            if (e.button !== 0) return;
            mouseDownPos = { x: e.clientX, y: e.clientY };
        }

        function handleCardClick(e) {
            if (!isInSelectionMode()) return;

            // Skip if the click was on (or inside) any interactive element.
            if (e.target.closest(INTERACTIVE_SELECTOR)) return;

            // Skip if any ancestor has an Alpine @click / x-on:click handler.
            if (hasAlpineClickHandler(e.target)) return;

            // Skip if some inner handler already handled this click
            // (e.g. preventDefault'd or stopPropagation'd at a deeper level).
            if (e.defaultPrevented) return;

            // Find the card by walking up from the click target
            const result = findCard(e.target);
            if (!result) return;
            const { checkbox } = result;

            // Skip cancelled cards (employees in cancelled state shouldn't be selectable here)
            if (checkbox.disabled || checkbox.closest('.d-none, [style*="display: none"], [style*="display:none"]')) return;

            // Skip if user was dragging (text selection)
            if (mouseDownPos) {
                const dx = e.clientX - mouseDownPos.x;
                const dy = e.clientY - mouseDownPos.y;
                if (Math.sqrt(dx * dx + dy * dy) > DRAG_THRESHOLD) return;
            }

            // Skip if user has selected text (don't break copy)
            const selection = window.getSelection();
            if (selection && selection.toString().trim().length > 0) return;

            // Toggle the checkbox
            e.preventDefault();
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        document.body.addEventListener('mousedown', recordMouseDown, true);
        document.body.addEventListener('touchstart', function(e) {
            if (e.touches && e.touches[0]) {
                mouseDownPos = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            }
        }, { passive: true });

        document.body.addEventListener('click', handleCardClick);
    })();

    // 3. Handle "Select All" Checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            // Re-query currently visible checkboxes
            const currentCheckboxes = document.querySelectorAll('.employee-checkbox');

            const visibleCheckboxes = Array.from(currentCheckboxes).filter(cb => {
                const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper') || cb.closest('tr');
                const accordionCollapse = cb.closest('.accordion-collapse');
                const isHiddenByAccordion = accordionCollapse && !accordionCollapse.classList.contains('show');
                const isHidden = (cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled') || cardWrapper.style.display === 'none')) || isHiddenByAccordion;
                return !isHidden;
            });

            const visibleItems = visibleCheckboxes.map((cb, index) => {
                const data = getEmployeeData(cb);
                // Select All: assign order based on DOM position (1-based)
                data.selection_order = index + 1;
                return data;
            });
            const visibleIds = visibleItems.map(item => item.id);

            if (this.checked) {
                // Check all visible and add to storage
                // Reset counter since Select All replaces all selections
                window._selectionOrderCounter = visibleItems.length;
                visibleCheckboxes.forEach(cb => cb.checked = true);
                // Clear existing and set fresh with DOM order
                const currentData = window.getGlobalSelectedData();
                const nonVisibleData = currentData.filter(i => !visibleIds.includes(String(i.id)));
                window.setGlobalSelectedData([...nonVisibleData, ...visibleItems]);
            } else {
                // Uncheck all visible and remove from storage
                visibleCheckboxes.forEach(cb => cb.checked = false);
                removeItemsByIds(visibleIds);
            }

            // Trigger custom event for specific employers
            document.dispatchEvent(new Event('global-selection-updated'));
        });
    }

    // --- New Feature: View Selected Modal Logic ---
    window.openViewSelectedModal = function(customData = null, customTitle = null, customStorageKey = null) {
        const data = customData || window.getGlobalSelectedData();
        const container = document.getElementById('selected-items-container');
        const countBadge = document.getElementById('modal-selected-count');
        const modalEl = document.getElementById('viewSelectedModal');
        const titleEl = document.getElementById('viewSelectedModalLabel');

        if (!container || !modalEl) {
            console.error('Modal elements not found');
            return;
        }

        if (customTitle && titleEl) {
            titleEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>${customTitle}`;
        } else if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>{{ __('Selected Employees') }}`;
        }

        if (countBadge) countBadge.textContent = data.length;

        container.innerHTML = ''; // Clear

        if (data.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-muted w-100">
                    <i class="bi bi-check2-circle display-1 opacity-25"></i>
                    <p class="mt-3">{{ __('No employees selected') }}</p>
                </div>
            `;
        } else {
            // Sort by selection order before displaying
            const sortedData = [...data].sort((a, b) => (a.selection_order || 0) - (b.selection_order || 0));
            // When customStorageKey is set (employer page), each item is an employer
            // and item.id IS the employer id. Otherwise items are employees with item.id
            // being the employee id and item.employer_id being the employer id.
            const isEmployerMode = !!customStorageKey;
            sortedData.forEach((item, index) => {
                const orderNum = index + 1;
                const titleTh = item.title_th || '';
                const nameTh = item.name_th || '-';
                const fullNameTh = titleTh + ' ' + nameTh;

                const titleEn = item.title_en || '';
                const nameEn = item.name_en || '-';
                const fullNameEn = titleEn + ' ' + nameEn;

                const nationality = item.nationality || '-';
                const employer = item.employer_name || '-';
                const photo = item.photo || 'https://placehold.co/50x50/e2e8f0/6c757d?text=PIC';

                // Preview buttons — wired to the global universalPreviewModal
                // via the .btn-preview event-delegated handler in app.js.
                let namePreviewBtn = '';
                let employerPreviewBtn = '';
                if (isEmployerMode) {
                    // In employer mode the row itself is an employer, attach the
                    // preview to the name line so the magnifying glass is visible.
                    namePreviewBtn = `<button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-1" data-model-type="employer" data-model-id="${item.id}" title="{{ __('Preview Data') }}"><i class="bi bi-search"></i></button>`;
                } else {
                    namePreviewBtn = `<button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-1" data-model-type="employee" data-model-id="${item.id}" title="{{ __('Preview Employee') }}"><i class="bi bi-search"></i></button>`;
                    if (item.employer_id) {
                        employerPreviewBtn = `<button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-1" data-model-type="employer" data-model-id="${item.employer_id}" title="{{ __('Preview Employer') }}"><i class="bi bi-search"></i></button>`;
                    }
                }

                const cardHtml = `
                    <div class="col" id="modal-item-${item.id}">
                        <div class="card h-100 shadow-sm border position-relative hover-shadow transition-all selected-item-card"
                             draggable="true"
                             data-item-id="${item.id}"
                             style="cursor: grab; user-select: none;">
                            <span class="position-absolute top-0 start-0 m-2 badge bg-primary rounded-pill shadow-sm modal-item-order" style="z-index: 5; font-size: 0.75rem;">${orderNum}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle shadow-sm bg-white"
                                    draggable="false"
                                    onclick="window.removeSelectedItemFromModal('${item.id}', '${customStorageKey || ''}')"
                                    title="{{ __('Remove') }}" style="width: 28px; height: 28px; padding: 0; z-index: 5;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <div class="card-body d-flex align-items-center gap-3 p-3">
                                <div class="flex-shrink-0">
                                    <img src="${photo}" class="rounded-circle shadow-sm border" width="60" height="60" style="object-fit: cover;" draggable="false">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1" title="${fullNameEn}">
                                        <span class="text-truncate">${fullNameEn}</span>${namePreviewBtn}
                                    </div>
                                    <div class="text-muted small text-truncate" title="${fullNameTh}">${fullNameTh}</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-flag me-1"></i>${nationality}</span>
                                    </div>
                                    <div class="small text-primary mt-1 d-flex align-items-center gap-1" title="${employer}">
                                        <i class="bi bi-building"></i><span class="text-truncate">${employer}</span>${employerPreviewBtn}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += cardHtml;
            });

            // --- Drag-and-drop reorder ---
            // Cards are rebuilt every render, so we attach listeners fresh each time.
            window._setupSelectedItemsDnD(container, customData, customTitle, customStorageKey);
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    };

    // Internal helper exposed only because openViewSelectedModal is a window.* function
    // and re-rendering after a drop calls it back through the window scope.
    window._setupSelectedItemsDnD = function(container, customData, customTitle, customStorageKey) {
        let draggedId = null;

        const clearIndicators = () => {
            container.querySelectorAll('.drag-over-before, .drag-over-after').forEach(el => {
                el.classList.remove('drag-over-before', 'drag-over-after');
            });
        };

        const cards = container.querySelectorAll('.selected-item-card');
        cards.forEach(card => {
            card.addEventListener('dragstart', (e) => {
                // Don't start drag from interactive children (the X / preview buttons
                // already have draggable="false" but be defensive).
                if (e.target.closest('button, a, .btn-preview')) {
                    e.preventDefault();
                    return;
                }
                draggedId = card.dataset.itemId;
                card.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
                // Required for Firefox to actually start a drag
                try { e.dataTransfer.setData('text/plain', draggedId); } catch (_) {}
            });

            card.addEventListener('dragend', () => {
                card.style.opacity = '';
                clearIndicators();
                draggedId = null;
            });

            card.addEventListener('dragover', (e) => {
                if (!draggedId || draggedId === card.dataset.itemId) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                clearIndicators();
                const rect = card.getBoundingClientRect();
                const before = (e.clientY - rect.top) < (rect.height / 2);
                card.classList.add(before ? 'drag-over-before' : 'drag-over-after');
            });

            card.addEventListener('dragleave', (e) => {
                // Only clear if leaving the card entirely
                if (!card.contains(e.relatedTarget)) {
                    card.classList.remove('drag-over-before', 'drag-over-after');
                }
            });

            card.addEventListener('drop', (e) => {
                e.preventDefault();
                if (!draggedId || draggedId === card.dataset.itemId) {
                    clearIndicators();
                    return;
                }
                const rect = card.getBoundingClientRect();
                const dropBefore = (e.clientY - rect.top) < (rect.height / 2);
                window._reorderSelectedItems(draggedId, card.dataset.itemId, dropBefore, customData, customTitle, customStorageKey);
                clearIndicators();
            });
        });
    };

    window._reorderSelectedItems = function(draggedId, targetId, dropBefore, customData, customTitle, customStorageKey) {
        // Pull the freshest copy from the source of truth — sessionStorage for
        // custom mode, the global selection for default mode — so concurrent
        // ticking elsewhere doesn't get stomped.
        let data = [];
        if (customStorageKey) {
            try {
                const stored = sessionStorage.getItem(customStorageKey);
                data = stored ? JSON.parse(stored) : (customData || []);
            } catch (_) {
                data = customData || [];
            }
        } else {
            data = window.getGlobalSelectedData();
        }

        // Sort by current selection_order so splicing reflects the visible order
        data.sort((a, b) => (a.selection_order || 0) - (b.selection_order || 0));

        const fromIdx = data.findIndex(i => String(i.id) === String(draggedId));
        if (fromIdx === -1) return;
        const draggedItem = data.splice(fromIdx, 1)[0];

        let toIdx = data.findIndex(i => String(i.id) === String(targetId));
        if (toIdx === -1) {
            data.push(draggedItem);
        } else {
            if (!dropBefore) toIdx++;
            data.splice(toIdx, 0, draggedItem);
        }

        // Re-number selection_order so future renders stay consistent
        data.forEach((it, idx) => { it.selection_order = idx + 1; });

        // Persist
        if (customStorageKey) {
            sessionStorage.setItem(customStorageKey, JSON.stringify(data));
            window.dispatchEvent(new CustomEvent('custom-selection-changed', { detail: { key: customStorageKey } }));
        } else {
            window.setGlobalSelectedData(data);
            if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
        }

        // Re-render the modal in place. Pass customData=data to keep the modal
        // working off the freshly reordered array even in employer/custom mode.
        window.openViewSelectedModal(customStorageKey ? data : null, customTitle, customStorageKey);
    };

    window.removeSelectedItemFromModal = function(id, customStorageKey = null) {
        // removeItemsByIds is defined inside DOMContentLoaded scope previously, so it's not global.
        // We need to access the logic. But wait, `removeItemsByIds` was defined inside the closure.
        // We need to expose it or reimplement it.
        // Let's reimplement a simple version here relying on `getGlobalSelectedData` and `setGlobalSelectedData` which ARE global.

        let current = [];
        let key = STORAGE_KEY;

        if (customStorageKey) {
            key = customStorageKey;
            try {
                const stored = sessionStorage.getItem(key);
                current = stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.error('Error parsing custom storage', e);
                current = [];
            }
        } else {
            current = window.getGlobalSelectedData();
        }

        const filtered = current.filter(item => String(item.id) !== String(id));

        if (customStorageKey) {
            sessionStorage.setItem(key, JSON.stringify(filtered));
            // Trigger a custom event for local UI updates
            window.dispatchEvent(new CustomEvent('custom-selection-changed', { detail: { key: key } }));
        } else {
            window.setGlobalSelectedData(filtered);
            // Sync main UI (Checkboxes)
            if(window.refreshGlobalSelectionUI) {
                window.refreshGlobalSelectionUI();
            }
        }

        // Remove from DOM immediately
        const el = document.getElementById(`modal-item-${id}`);
        if(el) {
            el.remove();
        }

        // Update Modal Count
        const countBadge = document.getElementById('modal-selected-count');
        if (countBadge) countBadge.textContent = filtered.length;

        // If empty, show empty state
        if (filtered.length === 0) {
            const container = document.getElementById('selected-items-container');
            if (container) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted w-100">
                        <i class="bi bi-check2-circle display-1 opacity-25"></i>
                        <p class="mt-3">{{ __('No employees selected') }}</p>
                    </div>
                `;
            }
            // Optional: Close modal if empty? No, user might want to see it cleared.
        }
    };
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.delete-employee-form')) {
            e.preventDefault();
            const form = e.target;

            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: "{{ __('This will move the employee to the Central Trash. You can recover them later.') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Yes, move to Trash!') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    const action = form.getAttribute('action');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                             window.location.reload();
                        } else {
                            showToast(data.message || '{{ __('An error occurred while trying to delete the employee.') }}', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Delete Error:', error);
                        showToast('{{ __('A network error occurred. Please try again.') }}', 'danger');
                    });
                }
            });
        }
    });
});
</script>

<!-- Scroll to Top Buttons -->
<div class="scroll-to-top left" id="scrollToTopLeft"><i class="bi bi-chevron-up"></i></div>
<div class="scroll-to-top right" id="scrollToTopRight"><i class="bi bi-chevron-up"></i></div>

<!-- Side Drawer Handle -->
<div id="drawer-handle" class="drawer-handle" title="Open Menu">
    <i class="bi bi-chevron-right"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Scroll to Top Logic ---
    const scrollToTopLeft = document.getElementById('scrollToTopLeft');
    const scrollToTopRight = document.getElementById('scrollToTopRight');

    function checkScroll() {
        if (window.scrollY > 200) {
            scrollToTopLeft.classList.add('show');
            scrollToTopRight.classList.add('show');
        } else {
            scrollToTopLeft.classList.remove('show');
            scrollToTopRight.classList.remove('show');
        }
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    window.addEventListener('scroll', checkScroll);
    scrollToTopLeft.addEventListener('click', scrollToTop);
    scrollToTopRight.addEventListener('click', scrollToTop);

    // --- Drawer Handle Logic ---
    const drawerHandle = document.getElementById('drawer-handle');
    const sidebarElement = document.getElementById('sidebar');

    if (drawerHandle && sidebarElement) {
        // Show handle when scrolled down
        window.addEventListener('scroll', function() {
            // Check if sidebar is currently open to avoid showing handle over it (though CSS z-index/transform handles visibility mostly)
            const isSidebarOpen = sidebarElement.classList.contains('show');

            if (window.scrollY > 100 && !isSidebarOpen) {
                drawerHandle.classList.add('show');
            } else {
                drawerHandle.classList.remove('show');
            }
        });

        // Open sidebar on click
        drawerHandle.addEventListener('click', function() {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarElement);
            bsOffcanvas.show();
        });

        // Hide handle when sidebar is open
        sidebarElement.addEventListener('show.bs.offcanvas', function () {
            drawerHandle.classList.remove('show');
        });

        // Re-check scroll position when sidebar closes
        sidebarElement.addEventListener('hidden.bs.offcanvas', function () {
            if (window.scrollY > 100) {
                drawerHandle.classList.add('show');
            }
        });
    }
});
</script>

<script>
    // Global Logout Handler for SweetAlert
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('btn-logout');
        if(logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: @json(__('Ready to Leave?')),
                    text: @json(__('Select "Logout" below if you are ready to end your current session.')),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#F97316',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Logout') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('logout-form').submit();
                    }
                });
            });
        }
    });

    // Global Highlight & Scroll Handler
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash) {
            // Decouple from execution flow to ensure DOM is fully ready
            setTimeout(() => {
                const id = window.location.hash.substring(1);
                const el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('highlight');
                    setTimeout(() => {
                        el.classList.remove('highlight');
                    }, 5000);
                }
            }, 300);
        }
    });
</script>

<script>
    // ═══ Resolution Badge Auto-Fit ═══
    // Auto-shrinks font size so long tab names fit within the uniform-size badge
    window.fitResolutionBadges = function(root = document) {
        const badges = root.querySelectorAll('.resolution-badge .rb-text');
        const maxFont = 13; // px
        const minFont = 9;  // px
        badges.forEach(el => {
            // Reset to max before measuring
            let size = maxFont;
            el.style.fontSize = size + 'px';
            // Shrink until content fits
            let safety = 20;
            while (safety-- > 0 && (el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1) && size > minFont) {
                size -= 0.5;
                el.style.fontSize = size + 'px';
            }
        });
    };

    // Run on page load
    document.addEventListener('DOMContentLoaded', () => window.fitResolutionBadges());

    // Run again when window resizes (size changes between mobile/desktop)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => window.fitResolutionBadges(), 150);
    });

    // Observe DOM for dynamically added badges (AJAX content)
    const badgeObserver = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType === 1 && (node.classList?.contains('resolution-badge') || node.querySelector?.('.resolution-badge'))) {
                    window.fitResolutionBadges(node.parentNode || document);
                    return;
                }
            }
        }
    });
    badgeObserver.observe(document.body, { childList: true, subtree: true });
</script>

<script>
// ทุก Bootstrap modal ในระบบต้องปิดได้เฉพาะจากปุ่ม (Cancel/Confirm/X) เท่านั้น
// ห้ามปิดเมื่อคลิก backdrop (พื้นที่เทาๆ ด้านหลัง) หรือกด ESC
// เหตุผล: กันการกดผิดตอนกรอกข้อมูล แล้วทำให้ modal ปิดเสียข้อมูลทั้งหมด
(function () {
    // 1) Override Bootstrap Modal defaults — modal ที่ Bootstrap สร้างใหม่จะใช้ค่านี้
    function overrideBootstrapDefaults() {
        if (window.bootstrap && bootstrap.Modal && bootstrap.Modal.Default) {
            bootstrap.Modal.Default.backdrop = 'static';
            bootstrap.Modal.Default.keyboard = false;
        }
    }

    // 2) Set data attributes บน modal elements — Bootstrap จะอ่านตอน init instance
    //    ครอบคลุม modal เดิม + modal ที่ AJAX โหลดมาทีหลัง (preview, financial-tab ฯลฯ)
    function applyStaticToModal(modal) {
        modal.setAttribute('data-bs-backdrop', 'static');
        modal.setAttribute('data-bs-keyboard', 'false');
    }

    function applyStaticToAllModals(root) {
        const modals = (root && root.querySelectorAll) ? root.querySelectorAll('.modal') : [];
        modals.forEach(applyStaticToModal);
    }

    function init() {
        overrideBootstrapDefaults();
        applyStaticToAllModals(document);

        // Watch สำหรับ modal ที่ถูก inject เข้ามาภายหลัง (เช่น preview modal AJAX-loaded)
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return; // ข้าม text/comment nodes
                    if (node.classList && node.classList.contains('modal')) {
                        applyStaticToModal(node);
                    }
                    // ตรวจ children ด้วย — กรณี modal อยู่ภายใน wrapper ที่เพิ่งถูก insert
                    if (node.querySelectorAll) applyStaticToAllModals(node);
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<script>
// Global copy-to-clipboard handler — รองรับ .copy-btn ทุกที่ในระบบ (รวม AJAX-loaded content)
// ใช้ event delegation บน document เพื่อรองรับ element ที่ render ตอน runtime (เช่น preview modal)
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.copy-btn');
    if (!btn) return;

    const targetSelector = btn.dataset.copyTarget;
    if (!targetSelector) return;

    const target = document.querySelector(targetSelector);
    if (!target) return;

    const value = target.value !== undefined ? target.value : target.textContent;
    if (!value) return;

    // ใช้ Clipboard API (modern) — fallback ไป execCommand ถ้าไม่รองรับ
    const copyText = (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        return Promise.resolve();
    };

    copyText(value).then(() => {
        // เปลี่ยน icon เป็นเครื่องหมายถูกชั่วคราว ให้ user รู้ว่า copy แล้ว
        const icon = btn.querySelector('i');
        if (!icon) return;
        const originalClass = icon.className;
        icon.className = 'bi bi-check2 text-success';
        btn.setAttribute('title', @json(__('Copied!')));
        setTimeout(() => {
            icon.className = originalClass;
            btn.setAttribute('title', @json(__('Copy')));
        }, 1500);
    });
});
</script>
