@extends('layouts.app')

@section('content')
<div class="container py-4" x-data="financialSettings({{ json_encode($profiles) }})">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Bill Header Settings</h2>
        <button class="btn btn-primary" @click="openAddModal()">
            <i class="bi bi-plus-lg"></i> Add New Profile
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <template x-for="profile in profiles" :key="profile.id">
            <div class="col">
                <div class="card h-100 shadow-sm" :class="{ 'border-primary': profile.is_default }">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <template x-if="profile.logo_path">
                                    <img :src="'/storage/' + profile.logo_path" style="height: 40px; object-fit: contain;">
                                </template>
                                <template x-if="!profile.logo_path">
                                    <div class="bg-light p-2 rounded text-muted small">No Logo</div>
                                </template>
                            </div>
                            <template x-if="profile.is_default">
                                <span class="badge bg-primary">Default</span>
                            </template>
                        </div>
                        <h5 class="card-title fw-bold" x-text="profile.name"></h5>
                        <p class="card-text small text-muted" x-text="profile.address"></p>
                        <p class="card-text small">Tax ID: <span x-text="profile.tax_id"></span></p>

                        <div class="mt-3 pt-2 border-top d-flex justify-content-end">
                            <button class="btn btn-sm btn-outline-secondary" @click="openEditModal(profile)">
                                <i class="bi bi-pencil"></i> Configure
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Edit/Add Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form :action="isEditing ? '/settings/financial/' + form.id : '/settings/financial'" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" x-text="isEditing ? 'Edit Profile: ' + form.name : 'New Company Profile'"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">General Info</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-signatures" type="button">Signatures & Stamps</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- GENERAL TAB -->
                            <div class="tab-pane fade show active" id="tab-general">
                                <div class="mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="name" class="form-control" x-model="form.name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="3" x-model="form.address"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tax ID</label>
                                        <input type="text" name="tax_id" class="form-control" x-model="form.tax_id">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" x-model="form.phone">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Logo</label>
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                    <div class="form-text small" x-show="form.logo_path">Current: <span x-text="form.logo_path"></span></div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault" x-model="form.is_default">
                                    <label class="form-check-label" for="isDefault">Set as Default Profile</label>
                                </div>
                            </div>

                            <!-- SIGNATURES TAB -->
                            <div class="tab-pane fade" id="tab-signatures">
                                <div class="row">
                                    <!-- Controls -->
                                    <div class="col-md-5 border-end">
                                        <h6 class="fw-bold mb-3">Configuration</h6>

                                        <!-- Signature Config -->
                                        <div class="card mb-3 bg-light border-0">
                                            <div class="card-body p-2">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="use_signature" id="useSig" x-model="form.use_signature">
                                                    <label class="form-check-label fw-bold" for="useSig">Show Signature</label>
                                                </div>
                                                <div x-show="form.use_signature">
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">Signature Image (PNG transparent)</label>
                                                        <input type="file" name="signature" class="form-control form-control-sm" accept="image/*" @change="previewImage($event, 'sigPreview')">
                                                    </div>

                                                    <label class="form-label small mb-0">Position X (%)</label>
                                                    <input type="range" class="form-range" min="0" max="100" step="1" x-model="sigPos.x">
                                                    <label class="form-label small mb-0">Position Y (%)</label>
                                                    <input type="range" class="form-range" min="0" max="100" step="1" x-model="sigPos.y">
                                                    <label class="form-label small mb-0">Size (Width %)</label>
                                                    <input type="range" class="form-range" min="5" max="50" step="1" x-model="sigPos.w">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Stamp Config -->
                                        <div class="card mb-3 bg-light border-0">
                                            <div class="card-body p-2">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="use_stamp" id="useStamp" x-model="form.use_stamp">
                                                    <label class="form-check-label fw-bold" for="useStamp">Show Stamp</label>
                                                </div>
                                                <div x-show="form.use_stamp">
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">Stamp Image</label>
                                                        <input type="file" name="stamp" class="form-control form-control-sm" accept="image/*" @change="previewImage($event, 'stampPreview')">
                                                    </div>

                                                    <label class="form-label small mb-0">Position X (%)</label>
                                                    <input type="range" class="form-range" min="0" max="100" step="1" x-model="stampPos.x">
                                                    <label class="form-label small mb-0">Position Y (%)</label>
                                                    <input type="range" class="form-range" min="0" max="100" step="1" x-model="stampPos.y">
                                                    <label class="form-label small mb-0">Size (Width %)</label>
                                                    <input type="range" class="form-range" min="5" max="50" step="1" x-model="stampPos.w">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Hidden Inputs for JSON -->
                                        <input type="hidden" name="signature_pos" :value="JSON.stringify(sigPos)">
                                        <input type="hidden" name="stamp_pos" :value="JSON.stringify(stampPos)">

                                    </div>

                                    <!-- Simulator / Preview -->
                                    <div class="col-md-7">
                                        <h6 class="fw-bold mb-2">A4 Footer Simulation</h6>
                                        <div class="alert alert-info py-1 small">
                                            <i class="bi bi-info-circle"></i> Drag sliders to position elements.
                                        </div>

                                        <div class="border shadow-sm position-relative bg-white mx-auto overflow-hidden"
                                             style="width: 100%; padding-bottom: 141.4%; /* A4 Aspect Ratio */ transform-origin: top left;">

                                            <!-- Dummy Header Content -->
                                            <div class="position-absolute top-0 start-0 w-100 p-4" style="opacity: 0.3;">
                                                <div class="d-flex gap-3">
                                                    <div style="width: 60px; height: 60px; background: #ddd;"></div>
                                                    <div>
                                                        <div class="fw-bold fs-5" x-text="form.name || 'Company Name'"></div>
                                                        <div class="small" x-text="form.address || 'Address...'"></div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="mt-5">
                                                    <div class="bg-light w-100 mb-2" style="height: 20px;"></div>
                                                    <div class="bg-light w-100 mb-2" style="height: 20px;"></div>
                                                    <div class="bg-light w-100 mb-2" style="height: 20px;"></div>
                                                </div>
                                            </div>

                                            <!-- Signature Line (Static Ref) -->
                                            <div class="position-absolute w-100 d-flex justify-content-between px-5" style="bottom: 15%; pointer-events: none;">
                                                <div class="text-center" style="width: 40%;">
                                                    <div style="border-bottom: 1px solid #ccc; height: 30px;"></div>
                                                    <div class="small text-muted">Received By</div>
                                                </div>
                                                <div class="text-center" style="width: 40%;">
                                                    <div style="border-bottom: 1px solid #ccc; height: 30px;"></div>
                                                    <div class="small text-muted">Authorized Signature</div>
                                                </div>
                                            </div>

                                            <!-- Draggable Signature -->
                                            <div x-show="form.use_signature" class="position-absolute border border-primary border-dashed"
                                                 :style="{
                                                     left: sigPos.x + '%',
                                                     top: sigPos.y + '%',
                                                     width: sigPos.w + '%'
                                                 }"
                                                 style="cursor: move;">
                                                <img :src="sigPreview || (form.signature_path ? '/storage/' + form.signature_path : 'https://via.placeholder.com/150?text=Signature')"
                                                     class="w-100 d-block" style="pointer-events: none;">
                                                <div class="position-absolute top-0 start-0 bg-primary text-white px-1" style="font-size: 10px;">Sig</div>
                                            </div>

                                            <!-- Draggable Stamp -->
                                            <div x-show="form.use_stamp" class="position-absolute border border-danger border-dashed"
                                                 :style="{
                                                     left: stampPos.x + '%',
                                                     top: stampPos.y + '%',
                                                     width: stampPos.w + '%'
                                                 }"
                                                 style="cursor: move;">
                                                <img :src="stampPreview || (form.stamp_path ? '/storage/' + form.stamp_path : 'https://via.placeholder.com/150?text=Stamp')"
                                                     class="w-100 d-block" style="pointer-events: none; opacity: 0.8;">
                                                <div class="position-absolute top-0 start-0 bg-danger text-white px-1" style="font-size: 10px;">Stamp</div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function financialSettings(profiles) {
        return {
            profiles: profiles,
            isEditing: false,
            form: {
                id: null, name: '', address: '', tax_id: '', phone: '', logo_path: '', is_default: false,
                use_signature: false, use_stamp: false,
                signature_path: '', stamp_path: ''
            },
            sigPos: { x: 50, y: 75, w: 20 },
            stampPos: { x: 55, y: 70, w: 20 },
            sigPreview: null,
            stampPreview: null,

            openAddModal() {
                this.isEditing = false;
                this.resetForm();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('profileModal')).show();
            },

            openEditModal(profile) {
                this.isEditing = true;
                this.form = { ...profile }; // Copy

                // Parse Positions
                this.sigPos = profile.signature_pos ?
                    (typeof profile.signature_pos === 'string' ? JSON.parse(profile.signature_pos) : profile.signature_pos)
                    : { x: 60, y: 78, w: 20 };

                this.stampPos = profile.stamp_pos ?
                    (typeof profile.stamp_pos === 'string' ? JSON.parse(profile.stamp_pos) : profile.stamp_pos)
                    : { x: 65, y: 75, w: 15 };

                this.sigPreview = null;
                this.stampPreview = null;

                bootstrap.Modal.getOrCreateInstance(document.getElementById('profileModal')).show();
            },

            resetForm() {
                this.form = {
                    id: null, name: '', address: '', tax_id: '', phone: '', logo_path: '', is_default: false,
                    use_signature: false, use_stamp: false,
                    signature_path: '', stamp_path: ''
                };
                this.sigPos = { x: 60, y: 78, w: 20 };
                this.stampPos = { x: 65, y: 75, w: 15 };
                this.sigPreview = null;
                this.stampPreview = null;
            },

            previewImage(event, targetVar) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this[targetVar] = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    }
</script>
@endpush
@endsection
