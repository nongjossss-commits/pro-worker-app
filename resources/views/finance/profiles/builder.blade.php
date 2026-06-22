@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="profileBuilder({{ Js::from($thaiBanks) }})">
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
                        <div class="mb-3">
                            <label class="form-label">Authorized Signatory Name</label>
                            <input type="text" class="form-control" x-model="formData.authorized_signatory_name" placeholder="Name of person signing (replaces company name)">
                        </div>

                        <hr>
                        <h6>Assets (1:1 Drag & Drop placement)</h6>

                        <!-- Logo -->
                        <div class="mb-3">
                            <label class="form-label">Logo (Top Corner)</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" accept="image/png, image/jpeg, image/jpg" class="form-control form-control-sm" x-ref="logoInput" @change="previewAsset($event, 'logo')">
                                <button type="button" class="btn btn-sm btn-outline-danger" x-show="formData.logo_url" @click="removeAsset('logo')"><i class="bi bi-x"></i></button>
                            </div>
                        </div>

                        <!-- Signature -->
                        <div class="mb-3">
                            <label class="form-label">Signature Image</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" accept="image/png, image/jpeg, image/jpg" class="form-control form-control-sm" x-ref="signatureInput" @change="previewAsset($event, 'signature')">
                                <button type="button" class="btn btn-sm btn-outline-danger" x-show="formData.signature_url" @click="removeAsset('signature')"><i class="bi bi-x"></i></button>
                            </div>
                            <div class="small text-muted mt-1">Upload and drag on the document to the right.</div>
                        </div>

                        <!-- Stamp -->
                        <div class="mb-3">
                            <label class="form-label">Company Stamp Image</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" accept="image/png, image/jpeg, image/jpg" class="form-control form-control-sm" x-ref="stampInput" @change="previewAsset($event, 'stamp')">
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

                    {{-- Bank Accounts panel — only visible after the profile has been
                         saved (we need its id to attach accounts to). New profiles
                         see this section appear right after the first save. --}}
                    <hr class="mt-4" x-show="editingProfileId">
                    <div x-show="editingProfileId" class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="bi bi-bank2 me-1"></i> {{ __('Bank Accounts') }}</h6>
                            <button type="button" class="btn btn-sm btn-success" @click="openAddBank">
                                <i class="bi bi-plus-circle"></i> {{ __('Add Bank') }}
                            </button>
                        </div>

                        {{-- Existing accounts list --}}
                        <div x-show="bankFormMode === 'closed'">
                            <template x-for="acc in bankAccounts" :key="acc.id">
                                <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 0;">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                              :style="`background:${bankBadge(acc).color}; width:34px; height:34px; font-size:12px;`"
                                              x-text="bankBadge(acc).initial"></span>
                                        <div style="min-width: 0;">
                                            <div class="small fw-bold text-truncate" x-text="bankDisplayName(acc)"></div>
                                            <div class="small text-muted text-truncate" x-text="bankSubLine(acc)"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                        <button type="button" class="btn btn-sm btn-warning" @click="openEditBank(acc)"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteBankAccount(acc)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="bankAccounts.length === 0" class="text-muted small text-center p-3 border rounded">
                                {{ __('No bank accounts yet — click Add Bank above.') }}
                            </div>
                        </div>

                        {{-- Add / Edit Bank form --}}
                        <div x-show="bankFormMode !== 'closed'" class="border rounded p-3 bg-light">
                            <div class="mb-2">
                                <label class="form-label small fw-bold">{{ __('Bank Type') }}</label>
                                <select class="form-select form-select-sm" x-model="bankForm.bank_type">
                                    <option value="thai_bank">{{ __('Thai bank') }}</option>
                                    <option value="promptpay">{{ __('PromptPay') }}</option>
                                    <option value="other">{{ __('Other (custom)') }}</option>
                                </select>
                            </div>

                            {{-- Thai bank picker — collapse to a chip after selection,
                                 expand again on "Change". Without this collapse the
                                 17-row list stays visible after a pick and users think
                                 the dropdown is stuck. --}}
                            <div class="mb-2" x-show="bankForm.bank_type === 'thai_bank'">
                                <label class="form-label small fw-bold">{{ __('Bank') }} *</label>

                                {{-- Selected-state chip --}}
                                <div x-show="bankForm.bank_code && !bankPickerOpen"
                                     class="d-flex align-items-center gap-2 border rounded p-2 bg-white">
                                    <template x-if="bankForm.bank_code">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                              :style="`background:${(thaiBanks.find(b=>b.code===bankForm.bank_code)||{}).color}; width:28px; height:28px; font-size:11px;`"
                                              x-text="(thaiBanks.find(b=>b.code===bankForm.bank_code)||{}).initial"></span>
                                    </template>
                                    <span class="small fw-bold flex-grow-1" x-text="bankForm.bank_name"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="bankPickerOpen = true; bankSearch = '';">
                                        <i class="bi bi-pencil"></i> {{ __('Change') }}
                                    </button>
                                </div>

                                {{-- Picker (open by default when no bank picked yet) --}}
                                <div x-show="!bankForm.bank_code || bankPickerOpen">
                                    <input type="text" class="form-control form-control-sm mb-1" x-model="bankSearch" placeholder="{{ __('Search bank name...') }}">
                                    <div class="border rounded" style="max-height:160px; overflow-y:auto;">
                                        <template x-for="b in filteredBanks" :key="b.code">
                                            <div class="d-flex align-items-center gap-2 p-2 bank-pick"
                                                 :class="bankForm.bank_code === b.code ? 'bg-primary bg-opacity-10 fw-bold' : ''"
                                                 style="cursor:pointer;"
                                                 @click="pickBank(b)">
                                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                                      :style="`background:${b.color}; width:28px; height:28px; font-size:11px;`"
                                                      x-text="b.initial"></span>
                                                <span class="small" x-text="b.name_th"></span>
                                                <span class="small text-muted ms-auto" x-text="b.name_en"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredBanks.length === 0" class="text-muted small text-center p-3">{{ __('No bank matches your search.') }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Custom / Other bank name --}}
                            <div class="mb-2" x-show="bankForm.bank_type === 'other'">
                                <label class="form-label small fw-bold">{{ __('Bank name') }} *</label>
                                <input type="text" class="form-control form-control-sm" x-model="bankForm.bank_name" placeholder="{{ __('e.g., Wise, foreign bank') }}">
                            </div>

                            {{-- PromptPay ID --}}
                            <div class="mb-2" x-show="bankForm.bank_type === 'promptpay'">
                                <label class="form-label small fw-bold">{{ __('PromptPay ID') }} *</label>
                                <input type="text" class="form-control form-control-sm" x-model="bankForm.promptpay_id" placeholder="{{ __('Phone (10 digits) or Tax ID (13 digits)') }}">
                            </div>

                            {{-- Account name / number --}}
                            <div class="mb-2">
                                <label class="form-label small fw-bold">{{ __('Account name') }}</label>
                                <input type="text" class="form-control form-control-sm" x-model="bankForm.account_name">
                            </div>

                            <div class="mb-2" x-show="bankForm.bank_type !== 'promptpay'">
                                <label class="form-label small fw-bold">{{ __('Account number') }} <span x-show="bankForm.bank_type === 'thai_bank'">*</span></label>
                                <input type="text" class="form-control form-control-sm" x-model="bankForm.account_number">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">{{ __('Branch') }}</label>
                                <input type="text" class="form-control form-control-sm" x-model="bankForm.branch">
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-success flex-grow-1" @click="saveBankAccount" :disabled="bankSaving">
                                    <span x-show="!bankSaving"><i class="bi bi-save"></i> {{ __('Save') }}</span>
                                    <span x-show="bankSaving">{{ __('Saving...') }}</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" @click="closeBankForm">{{ __('Cancel') }}</button>
                            </div>
                        </div>
                    </div>
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

                            <div class="row" style="position: absolute; bottom: 80px; left: 40px; right: 40px;">
                                <div class="col-6 text-center" style="position: relative;">
                                    <div style="font-weight: bold; margin-bottom: 40px;">Received By <span style="color: #888; font-weight: normal; font-size: 0.9em; margin-left: 3px;">/ ผู้รับเงิน</span></div>
                                    <div style="border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 10px; width: 80%; margin-left: 10%;"></div>
                                    <div style="font-size: 14px; color: #555;">Date <span style="color: #888; font-weight: normal; font-size: 0.9em; margin-left: 3px;">/ วันที่</span>: ____/____/______</div>
                                </div>
                                <div class="col-6 text-center" style="position: relative;">
                                    <div style="font-weight: bold; margin-bottom: 40px;">Authorized Signature <span style="color: #888; font-weight: normal; font-size: 0.9em; margin-left: 3px;">/ ผู้มีอำนาจลงนาม</span></div>
                                    <div style="border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 10px; width: 80%; margin-left: 10%;"></div>
                                    <div style="font-size: 14px; color: #555;" x-text="formData.authorized_signatory_name || formData.name || 'Company Name'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Draggable Assets -->
                        <!-- Logo (Fixed position or slightly draggable if needed, usually fixed top-left) -->
                        <img x-show="formData.logo_url" :src="formData.logo_url" class="position-absolute" style="max-width: 150px; max-height: 80px; top: 40px; left: 40px;">

                        <!-- Signature Draggable -->
                        <img :src="formData.signature_url" id="drag-signature" class="draggable-asset" data-type="signature"
                             x-bind:style="getAssetStyle('signature')">

                        <!-- Stamp Draggable -->
                        <img :src="formData.stamp_url" id="drag-stamp" class="draggable-asset" data-type="stamp"
                             x-bind:style="getAssetStyle('stamp')">
                    </div>

                </div>
            </div>

            <div class="alert alert-info mt-3" x-show="currentMode === 'form'">
                <i class="bi bi-info-circle"></i> <strong>Tip:</strong> You can Drag, Resize, and Rotate the Signature and Stamp images directly on the document preview above. They will be printed in exactly these positions. <br>
                <small><strong>Note:</strong> To rotate, scroll your mouse wheel while hovering over the image.</small>
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
        top: 0;
        left: 0;
        z-index: 10 !important;
    }
    .draggable-asset:hover {
        border-color: #0d6efd;
    }
    img.position-absolute {
        z-index: 10 !important;
    }
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('profileBuilder', (thaiBanks = []) => ({
        currentMode: 'list', // 'list' or 'form'
        currentType: 'biller', // 'biller' or 'customer'
        profiles: [],
        editingProfileId: null,
        isSaving: false,

        // Bank-account panel state — only matters when editingProfileId is set.
        thaiBanks: thaiBanks,
        bankAccounts: [],
        bankFormMode: 'closed', // 'closed' | 'add' | 'edit'
        bankSaving: false,
        bankSearch: '',
        bankPickerOpen: false,
        bankForm: {
            id: null,
            bank_type: 'thai_bank',
            bank_code: '',
            bank_name: '',
            account_name: '',
            account_number: '',
            branch: '',
            promptpay_id: '',
        },

        formData: {
            type: 'biller',
            name: '',
            tax_id: '',
            branch: '',
            address: '',
            phone: '',
            email: '',
            authorized_signatory_name: '',
            logo_url: null,
            signature_url: null,
            stamp_url: null,
            signature_position: { x: 400, y: 400, width: 150, height: 75, rotate: 0 },
            stamp_position: { x: 500, y: 400, width: 100, height: 100, rotate: 0 },
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
                name: '', tax_id: '', branch: '', address: '', phone: '', email: '', authorized_signatory_name: '',
                logo_url: null, signature_url: null, stamp_url: null,
                signature_position: { x: 400, y: 400, width: 150, height: 75, rotate: 0 },
                stamp_position: { x: 500, y: 400, width: 100, height: 100, rotate: 0 },
            };
            this.removals = { logo: false, signature: false, stamp: false };
            this.currentMode = 'form';
            this.resetFileInputs();
        },

        editProfile(profile) {
            this.editingProfileId = profile.id;
            this.bankAccounts = [];
            this.closeBankForm();
            this.loadBankAccounts();
            this.formData = {
                type: profile.type,
                name: profile.name,
                tax_id: profile.tax_id,
                branch: profile.branch,
                address: profile.address,
                phone: profile.phone,
                email: profile.email,
                authorized_signatory_name: profile.authorized_signatory_name,
                logo_url: profile.logo_path ? `/storage/${profile.logo_path}` : null,
                signature_url: profile.signature_path ? `/storage/${profile.signature_path}` : null,
                stamp_url: profile.stamp_path ? `/storage/${profile.stamp_path}` : null,
                signature_position: profile.signature_position || { x: 400, y: 400, width: 150, height: 75, rotate: 0 },
                stamp_position: profile.stamp_position || { x: 500, y: 400, width: 100, height: 100, rotate: 0 },
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

                // Initialize default positions for newly added items if null
                if (type !== 'logo' && !this.formData[`${type}_position`]) {
                    this.formData[`${type}_position`] = { x: 300, y: 300, width: 150, height: 75, rotate: 0 };
                }

                this.$nextTick(() => {
                    const el = document.getElementById(`drag-${type}`);
                    if(el && this.formData[`${type}_position`]) {
                         el.setAttribute('data-x', this.formData[`${type}_position`].x);
                         el.setAttribute('data-y', this.formData[`${type}_position`].y);
                         el.setAttribute('data-angle', this.formData[`${type}_position`].rotate || 0);

                         el.style.transform = `translate(${this.formData[`${type}_position`].x}px, ${this.formData[`${type}_position`].y}px) rotate(${this.formData[`${type}_position`].rotate || 0}deg)`;
                         el.style.width = this.formData[`${type}_position`].width + 'px';
                         el.style.height = this.formData[`${type}_position`].height + 'px';
                    }

                    Swal.fire({
                        title: 'Success',
                        text: `${type} image loaded. You can now drag, resize, and rotate it on the document.`,
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
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
            const url = this.formData[`${type}_url`];
            if (!pos) return { display: 'none' };
            // Returning an object allows Alpine to safely merge the x-show internal styles (display: none).
            // We manage display explicitly here to avoid x-show conflicts with dynamic styles
            return {
                display: url ? 'block' : 'none',
                width: `${pos.width}px`,
                height: `${pos.height}px`,
                transform: `translate(${pos.x}px, ${pos.y}px) rotate(${pos.rotate || 0}deg)`
            };
        },

        async saveProfile() {
            this.isSaving = true;
            const data = new FormData();

            // Text fields
            ['type', 'name', 'tax_id', 'branch', 'address', 'phone', 'email', 'authorized_signatory_name'].forEach(f => {
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
                    const saved = await res.json();
                    Swal.fire('Success', 'Profile saved successfully.', 'success');
                    // Capture the new id so the Bank Accounts panel becomes
                    // available immediately, without having to re-edit.
                    if (saved && saved.profile && saved.profile.id) {
                        this.editingProfileId = saved.profile.id;
                        this.loadBankAccounts();
                    }
                    this.loadProfiles(this.currentType);
                } else {
                    const err = await res.json();
                    let errMsg = err.message || 'Validation failed';
                    // Extract detailed validation errors if present
                    if (err.errors) {
                        errMsg = Object.values(err.errors).map(val => val.join(' ')).join('\n');
                    }
                    Swal.fire('Error', errMsg, 'error');
                }
            } catch (e) {
                console.error(e);
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

            // Add MutationObserver to attach wheel events dynamically to new elements
            const observer = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList.contains('draggable-asset')) {
                            node.addEventListener('wheel', (e) => {
                                e.preventDefault();
                                const type = node.getAttribute('data-type');
                                let angle = parseFloat(node.getAttribute('data-angle')) || _this.formData[`${type}_position`].rotate || 0;
                                angle += e.deltaY > 0 ? 5 : -5;
                                if(angle >= 360) angle = 0;
                                if(angle < 0) angle = 355;
                                node.setAttribute('data-angle', angle);
                                const x = parseFloat(node.getAttribute('data-x')) || _this.formData[`${type}_position`].x;
                                const y = parseFloat(node.getAttribute('data-y')) || _this.formData[`${type}_position`].y;
                                node.style.transform = `translate(${x}px, ${y}px) rotate(${angle}deg)`;
                                _this.formData[`${type}_position`].rotate = angle;
                            });
                        }
                    });
                });
            });

            const canvas = document.getElementById('pdf-canvas');
            if(canvas) {
                observer.observe(canvas, { childList: true, subtree: true });
            }
        },

        // ---- Bank Accounts panel (only active when editingProfileId is set) ----

        get filteredBanks() {
            const q = (this.bankSearch || '').trim().toLowerCase();
            if (!q) return this.thaiBanks;
            return this.thaiBanks.filter(b =>
                (b.name_th || '').toLowerCase().includes(q) ||
                (b.name_en || '').toLowerCase().includes(q) ||
                (b.code || '').toLowerCase().includes(q)
            );
        },

        bankBadge(acc) {
            if (acc.bank_type === 'thai_bank' && acc.bank_code) {
                const preset = this.thaiBanks.find(b => b.code === acc.bank_code);
                if (preset) return { initial: preset.initial, color: preset.color };
            }
            if (acc.bank_type === 'promptpay') return { initial: 'PP', color: '#0C3CFC' };
            const ch = (acc.bank_name || '?').charAt(0).toUpperCase();
            return { initial: ch, color: '#6C757D' };
        },

        bankDisplayName(acc) {
            if (acc.bank_type === 'thai_bank' && acc.bank_code) {
                const preset = this.thaiBanks.find(b => b.code === acc.bank_code);
                if (preset) return preset.name_th;
            }
            if (acc.bank_type === 'promptpay') return 'PromptPay';
            return acc.bank_name || '—';
        },

        bankSubLine(acc) {
            if (acc.bank_type === 'promptpay') return acc.promptpay_id || '';
            const parts = [];
            if (acc.account_name) parts.push(acc.account_name);
            if (acc.account_number) parts.push(acc.account_number);
            return parts.join(' · ');
        },

        async loadBankAccounts() {
            if (!this.editingProfileId) { this.bankAccounts = []; return; }
            try {
                const res = await fetch(`/finance/api/profiles/${this.editingProfileId}/bank-accounts`);
                this.bankAccounts = await res.json();
            } catch (e) { console.error(e); }
        },

        openAddBank() {
            this.bankFormMode = 'add';
            this.bankSearch = '';
            this.bankPickerOpen = true;
            this.bankForm = {
                id: null,
                bank_type: 'thai_bank',
                bank_code: '',
                bank_name: '',
                account_name: '',
                account_number: '',
                branch: '',
                promptpay_id: '',
            };
        },

        openEditBank(acc) {
            this.bankFormMode = 'edit';
            this.bankSearch = '';
            this.bankPickerOpen = false;
            this.bankForm = {
                id: acc.id,
                bank_type: acc.bank_type || 'thai_bank',
                bank_code: acc.bank_code || '',
                bank_name: acc.bank_name || '',
                account_name: acc.account_name || '',
                account_number: acc.account_number || '',
                branch: acc.branch || '',
                promptpay_id: acc.promptpay_id || '',
            };
        },

        closeBankForm() {
            this.bankFormMode = 'closed';
            this.bankSaving = false;
        },

        pickBank(preset) {
            this.bankForm.bank_code = preset.code;
            this.bankForm.bank_name = preset.name_th;
            this.bankPickerOpen = false;
            this.bankSearch = '';
        },

        async saveBankAccount() {
            this.bankSaving = true;
            try {
                const isEdit = this.bankFormMode === 'edit' && this.bankForm.id;
                const url = isEdit
                    ? `/finance/api/profiles/${this.editingProfileId}/bank-accounts/${this.bankForm.id}`
                    : `/finance/api/profiles/${this.editingProfileId}/bank-accounts`;
                const method = isEdit ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.bankForm),
                });
                if (res.ok) {
                    await this.loadBankAccounts();
                    this.closeBankForm();
                } else {
                    const err = await res.json();
                    let msg = err.message || 'Validation failed';
                    if (err.errors) msg = Object.values(err.errors).flat().join('\n');
                    Swal.fire('Error', msg, 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Server error.', 'error');
            } finally {
                this.bankSaving = false;
            }
        },

        async deleteBankAccount(acc) {
            const confirmed = await Swal.fire({ title: 'Delete this bank account?', icon: 'warning', showCancelButton: true });
            if (!confirmed.isConfirmed) return;
            try {
                await fetch(`/finance/api/profiles/${this.editingProfileId}/bank-accounts/${acc.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                await this.loadBankAccounts();
            } catch (e) { console.error(e); }
        },
    }));
});
</script>
@endsection
