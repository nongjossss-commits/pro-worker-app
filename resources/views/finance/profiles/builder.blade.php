@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="profileBuilder()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-badge"></i> Financial Profiles Builder</h2>
        <div>
            <button class="btn btn-outline-secondary me-2" @click="loadProfiles('biller')">Manage Biller Profiles</button>
            <button class="btn btn-outline-secondary" @click="loadProfiles('customer')">Manage Customer Profiles</button>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar: Form and Lists -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h5 class="mb-0" x-text="currentMode === 'list' ? (currentType === 'biller' ? 'Biller Profiles' : 'Customer Profiles') : (editingProfileId ? 'Edit Profile' : 'New Profile')"></h5>
                    <button x-show="currentMode === 'list'" class="btn btn-sm btn-light" @click="createNewProfile()"><i class="bi bi-plus-circle"></i> Create</button>
                    <button x-show="currentMode === 'form'" class="btn btn-sm btn-light" @click="currentMode = 'list'">Cancel</button>
                </div>

                <!-- List Mode -->
                <div class="card-body" x-show="currentMode === 'list'">
                    <div class="list-group">
                        <template x-for="profile in profiles" :key="profile.id">
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <strong x-text="profile.name"></strong>
                                    <div class="text-muted small" x-text="profile.tax_id"></div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-warning" @click="editProfile(profile)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-danger" @click="deleteProfile(profile.id)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </template>
                        <div x-show="profiles.length === 0" class="text-muted p-3 text-center">No profiles found.</div>
                    </div>
                </div>

                <!-- Form Mode -->
                <div class="card-body" x-show="currentMode === 'form'">
                    <form @submit.prevent="saveProfile" id="profileForm">
                        <div class="mb-3">
                            <label class="form-label">Profile Type</label>
                            <select class="form-select" x-model="formData.type" :disabled="editingProfileId">
                                <option value="biller">Biller (Issuer)</option>
                                <option value="customer">Customer (Client)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name / Company Name *</label>
                            <input type="text" class="form-control" x-model="formData.name" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Tax ID</label>
                                <input type="text" class="form-control" x-model="formData.tax_id">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Branch</label>
                                <input type="text" class="form-control" x-model="formData.branch">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" x-model="formData.address" rows="2"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" x-model="formData.phone">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" x-model="formData.email">
                            </div>
                        </div>

                        <hr>
                        <h6>Assets (1:1 Drag & Drop placement)</h6>

                        <!-- Logo -->
                        <div class="mb-3">
                            <label class="form-label">Logo (Top Corner)</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" class="form-control form-control-sm" x-ref="logoInput" @change="previewAsset($event, 'logo')">
                                <button type="button" class="btn btn-sm btn-outline-danger" x-show="formData.logo_url" @click="removeAsset('logo')"><i class="bi bi-x"></i></button>
                            </div>
                        </div>

                        <!-- Signature -->
                        <div class="mb-3">
                            <label class="form-label">Signature Image</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" class="form-control form-control-sm" x-ref="signatureInput" @change="previewAsset($event, 'signature')">
                                <button type="button" class="btn btn-sm btn-outline-danger" x-show="formData.signature_url" @click="removeAsset('signature')"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="small text-muted mt-1">Upload and drag on the document to the right.</div>
                        </div>

                        <!-- Stamp -->
                        <div class="mb-3">
                            <label class="form-label">Company Stamp Image</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" class="form-control form-control-sm" x-ref="stampInput" @change="previewAsset($event, 'stamp')">
                                <button type="button" class="btn btn-sm btn-outline-danger" x-show="formData.stamp_url" @click="removeAsset('stamp')"><i class="bi bi-x"></i></button>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success" :disabled="isSaving">
                                <span x-show="!isSaving"><i class="bi bi-save"></i> Save Profile</span>
                                <span x-show="isSaving">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Document Preview / Builder Area (1:1 A4) -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <span>1:1 Document Placement Preview</span>
                    <span class="badge bg-dark">A4 Format</span>
                </div>
                <div class="card-body bg-light overflow-auto p-4 d-flex justify-content-center" style="max-height: 80vh;">

                    <!-- A4 Canvas Area -->
                    <div id="pdf-canvas" class="bg-white shadow-sm position-relative" style="width: 210mm; height: 297mm; border: 1px solid #ccc; overflow: hidden; transform-origin: top center; transform: scale(0.85);">
                        <!-- Dummy Invoice Template Background -->
                        <div class="p-5" style="opacity: 0.6; pointer-events: none; font-family: 'Sarabun', sans-serif;">
                            <div class="text-end mb-4">
                                <h2 class="text-primary mb-0">INVOICE / RECEIPT</h2>
                                <div>No: INV-00001</div>
                                <div>Date: {{ date('Y-m-d') }}</div>
                            </div>
                            <div class="row mb-5">
                                <div class="col-6">
                                    <strong>From:</strong><br>
                                    <span x-text="formData.name || 'Your Company Name'"></span><br>
                                    <span x-text="formData.address || '123 Business Rd'"></span><br>
                                    Tax ID: <span x-text="formData.tax_id || '1234567890123'"></span>
                                </div>
                                <div class="col-6 text-end">
                                    <strong>To:</strong><br>
                                    Customer Name<br>
                                    Customer Address
                                </div>
                            </div>
                            <table class="table table-bordered mb-5">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Consulting Services</td><td class="text-end">1,500.00</td></tr>
                                    <tr><td>Processing Fee</td><td class="text-end">500.00</td></tr>
                                    <tr><th class="text-end">Total</th><th class="text-end">2,000.00</th></tr>
                                </tbody>
                            </table>

                            <div class="row" style="position: absolute; bottom: 80px; left: 0; right: 0;">
                                <div class="col-6 text-center">
                                    _______________________<br>
                                    Customer Signature
                                </div>
                                <div class="col-6 text-center">
                                    _______________________<br>
                                    Authorized Signature
                                </div>
                            </div>
                        </div>

                        <!-- Draggable Assets -->
                        <!-- Logo (Fixed position or slightly draggable if needed, usually fixed top-left) -->
                        <img x-show="formData.logo_url" :src="formData.logo_url" class="position-absolute" style="max-width: 150px; max-height: 80px; top: 40px; left: 40px;">

                        <!-- Signature Draggable -->
                        <img x-show="formData.signature_url" :src="formData.signature_url" id="drag-signature" class="draggable-asset" data-type="signature"
                             :style="getAssetStyle('signature') + ' z-index: 10;'">

                        <!-- Stamp Draggable -->
                        <img x-show="formData.stamp_url" :src="formData.stamp_url" id="drag-stamp" class="draggable-asset" data-type="stamp"
                             :style="getAssetStyle('stamp') + ' z-index: 10;'">
                    </div>

                </div>
            </div>

            <div class="alert alert-info mt-3" x-show="currentMode === 'form'">
                <i class="bi bi-info-circle"></i> <strong>Tip:</strong> You can Drag, Resize, and Rotate the Signature and Stamp images directly on the document preview above. They will be printed in exactly these positions.
            </div>
        </div>
    </div>
</div>

<!-- Load interact.js for Drag/Resize/Rotate -->
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<style>
    .draggable-asset {
        position: absolute;
        touch-action: none;
        user-select: none;
        border: 1px dashed transparent;
        cursor: move;
        transform-origin: center center;
    }
    .draggable-asset:hover {
        border-color: #0d6efd;
    }
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('profileBuilder', () => ({
        currentMode: 'list', // 'list' or 'form'
        currentType: 'biller', // 'biller' or 'customer'
        profiles: [],
        editingProfileId: null,
        isSaving: false,

        formData: {
            type: 'biller',
            name: '',
            tax_id: '',
            branch: '',
            address: '',
            phone: '',
            email: '',
            logo_url: null,
            signature_url: null,
            stamp_url: null,
            signature_position: { x: 400, y: 950, width: 150, height: 75, rotate: 0 },
            stamp_position: { x: 500, y: 930, width: 100, height: 100, rotate: 0 },
        },

        // Tracking items to remove on backend
        removals: {
            logo: false,
            signature: false,
            stamp: false
        },

        init() {
            this.loadProfiles('biller');
            this.initInteractJs();
        },

        async loadProfiles(type) {
            this.currentType = type;
            this.currentMode = 'list';
            try {
                const res = await fetch(`/finance/api/profiles?type=${type}`);
                this.profiles = await res.json();
            } catch (e) {
                console.error(e);
            }
        },

        createNewProfile() {
            this.editingProfileId = null;
            this.formData = {
                type: this.currentType,
                name: '', tax_id: '', branch: '', address: '', phone: '', email: '',
                logo_url: null, signature_url: null, stamp_url: null,
                signature_position: { x: 400, y: 950, width: 150, height: 75, rotate: 0 },
                stamp_position: { x: 500, y: 930, width: 100, height: 100, rotate: 0 },
            };
            this.removals = { logo: false, signature: false, stamp: false };
            this.currentMode = 'form';
            this.resetFileInputs();
        },

        editProfile(profile) {
            this.editingProfileId = profile.id;
            this.formData = {
                type: profile.type,
                name: profile.name,
                tax_id: profile.tax_id,
                branch: profile.branch,
                address: profile.address,
                phone: profile.phone,
                email: profile.email,
                logo_url: profile.logo_path ? `/storage/${profile.logo_path}` : null,
                signature_url: profile.signature_path ? `/storage/${profile.signature_path}` : null,
                stamp_url: profile.stamp_path ? `/storage/${profile.stamp_path}` : null,
                signature_position: profile.signature_position || { x: 400, y: 950, width: 150, height: 75, rotate: 0 },
                stamp_position: profile.stamp_position || { x: 500, y: 930, width: 100, height: 100, rotate: 0 },
            };
            this.removals = { logo: false, signature: false, stamp: false };
            this.currentMode = 'form';
            this.resetFileInputs();

            // Sync UI attributes to interactjs immediately
            this.$nextTick(() => {
                const sig = document.getElementById('drag-signature');
                const stp = document.getElementById('drag-stamp');
                if(sig) {
                    sig.setAttribute('data-x', this.formData.signature_position.x);
                    sig.setAttribute('data-y', this.formData.signature_position.y);
                    sig.setAttribute('data-angle', this.formData.signature_position.rotate);
                }
                if(stp) {
                    stp.setAttribute('data-x', this.formData.stamp_position.x);
                    stp.setAttribute('data-y', this.formData.stamp_position.y);
                    stp.setAttribute('data-angle', this.formData.stamp_position.rotate);
                }
            });
        },

        resetFileInputs() {
            if(this.$refs.logoInput) this.$refs.logoInput.value = '';
            if(this.$refs.signatureInput) this.$refs.signatureInput.value = '';
            if(this.$refs.stampInput) this.$refs.stampInput.value = '';
        },

        previewAsset(event, type) {
            const file = event.target.files[0];
            if (file) {
                this.formData[`${type}_url`] = URL.createObjectURL(file);
                this.removals[type] = false; // Cancel removal if new one selected

                // Initialize default positions for newly added items
                this.$nextTick(() => {
                    const el = document.getElementById(`drag-${type}`);
                    if(el && this.formData[`${type}_position`]) {
                         el.setAttribute('data-x', this.formData[`${type}_position`].x);
                         el.setAttribute('data-y', this.formData[`${type}_position`].y);
                         el.setAttribute('data-angle', this.formData[`${type}_position`].rotate || 0);
                    }
                });
            }
        },

        removeAsset(type) {
            this.formData[`${type}_url`] = null;
            this.removals[type] = true;
            if(this.$refs[`${type}Input`]) this.$refs[`${type}Input`].value = '';
        },

        getAssetStyle(type) {
            const pos = this.formData[`${type}_position`];
            if (!pos) return '';
            // Instead of using :style directly, we use x-bind:style which is safer.
            // But Alpine's x-show overrides display natively. By returning object-like styles
            // or a clean string without interfering with 'display', it works correctly.
            return `width: ${pos.width}px; height: ${pos.height}px; transform: translate(${pos.x}px, ${pos.y}px) rotate(${pos.rotate || 0}deg);`;
        },

        async saveProfile() {
            this.isSaving = true;
            const data = new FormData();

            // Text fields
            ['type', 'name', 'tax_id', 'branch', 'address', 'phone', 'email'].forEach(f => {
                if(this.formData[f] !== null && this.formData[f] !== undefined) {
                    data.append(f, this.formData[f]);
                }
            });

            // Position data as JSON
            data.append('signature_position', JSON.stringify(this.formData.signature_position));
            data.append('stamp_position', JSON.stringify(this.formData.stamp_position));

            // Files
            if(this.$refs.logoInput && this.$refs.logoInput.files[0]) data.append('logo', this.$refs.logoInput.files[0]);
            if(this.$refs.signatureInput && this.$refs.signatureInput.files[0]) data.append('signature', this.$refs.signatureInput.files[0]);
            if(this.$refs.stampInput && this.$refs.stampInput.files[0]) data.append('stamp', this.$refs.stampInput.files[0]);

            // Removals
            if(this.removals.logo) data.append('remove_logo', 1);
            if(this.removals.signature) data.append('remove_signature', 1);
            if(this.removals.stamp) data.append('remove_stamp', 1);

            let url = '/finance/api/profiles';
            if (this.editingProfileId) {
                url += `/${this.editingProfileId}`;
                data.append('_method', 'PUT'); // Fake PUT for file upload
            }

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: data
                });

                if (res.ok) {
                    Swal.fire('Success', 'Profile saved successfully.', 'success');
                    this.loadProfiles(this.currentType);
                } else {
                    const err = await res.json();
                    Swal.fire('Error', err.message || 'Validation failed', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Server error occurred.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        async deleteProfile(id) {
            if (await Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true }).then(r => !r.isConfirmed)) return;

            try {
                await fetch(`/finance/api/profiles/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                this.loadProfiles(this.currentType);
            } catch (e) {
                Swal.fire('Error', 'Failed to delete', 'error');
            }
        },

        initInteractJs() {
            const _this = this;

            interact('.draggable-asset')
                .draggable({
                    modifiers: [
                        interact.modifiers.restrictRect({
                            restriction: 'parent',
                            endOnly: true
                        })
                    ],
                    listeners: {
                        move(event) {
                            const target = event.target;
                            const type = target.getAttribute('data-type');

                            // Get existing coordinates
                            const x = (parseFloat(target.getAttribute('data-x')) || _this.formData[`${type}_position`].x || 0) + event.dx;
                            const y = (parseFloat(target.getAttribute('data-y')) || _this.formData[`${type}_position`].y || 0) + event.dy;

                            // Get current angle
                            const angle = parseFloat(target.getAttribute('data-angle')) || _this.formData[`${type}_position`].rotate || 0;

                            // Apply translation and preserve rotation
                            target.style.transform = `translate(${x}px, ${y}px) rotate(${angle}deg)`;

                            // Update data attributes
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            // Sync state
                            _this.formData[`${type}_position`].x = x;
                            _this.formData[`${type}_position`].y = y;
                        }
                    }
                })
                .resizable({
                    edges: { left: true, right: true, bottom: true, top: true },
                    modifiers: [
                        interact.modifiers.aspectRatio({
                            ratio: 'preserve',
                            modifiers: [
                                interact.modifiers.restrictSize({ max: 'parent' })
                            ]
                        })
                    ],
                    listeners: {
                        move(event) {
                            const target = event.target;
                            const type = target.getAttribute('data-type');
                            let x = (parseFloat(target.getAttribute('data-x')) || 0);
                            let y = (parseFloat(target.getAttribute('data-y')) || 0);
                            const angle = parseFloat(target.getAttribute('data-angle')) || 0;

                            // update the element's style
                            target.style.width = event.rect.width + 'px';
                            target.style.height = event.rect.height + 'px';

                            // translate when resizing from top or left edges
                            x += event.deltaRect.left;
                            y += event.deltaRect.top;

                            target.style.transform = `translate(${x}px, ${y}px) rotate(${angle}deg)`;

                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            // Sync state
                            _this.formData[`${type}_position`].x = x;
                            _this.formData[`${type}_position`].y = y;
                            _this.formData[`${type}_position`].width = event.rect.width;
                            _this.formData[`${type}_position`].height = event.rect.height;
                        }
                    }
                });

            // Add simple rotation via mouse wheel or right click (for demonstration/ease of use)
            document.querySelectorAll('.draggable-asset').forEach(el => {
                el.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    const type = el.getAttribute('data-type');
                    let angle = parseFloat(el.getAttribute('data-angle')) || _this.formData[`${type}_position`].rotate || 0;

                    // Rotate by 5 degrees
                    angle += e.deltaY > 0 ? 5 : -5;
                    if(angle >= 360) angle = 0;
                    if(angle < 0) angle = 355;

                    el.setAttribute('data-angle', angle);

                    const x = parseFloat(el.getAttribute('data-x')) || _this.formData[`${type}_position`].x;
                    const y = parseFloat(el.getAttribute('data-y')) || _this.formData[`${type}_position`].y;

                    el.style.transform = `translate(${x}px, ${y}px) rotate(${angle}deg)`;
                    _this.formData[`${type}_position`].rotate = angle;
                });
            });
        }
    }));
});
</script>
@endsection
