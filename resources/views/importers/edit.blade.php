@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลบริษัทนำเข้า')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลบริษัทนำเข้า</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="saveImporterForm" action="{{ route('importers.update', $importer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerNameTh" class="form-label">ชื่อ บนจ. (ไทย)</label>
                <input type="text" class="form-control" id="importerNameTh" name="importerNameTh" value="{{ $importer->importerNameTh }}" required>
            </div>
            <div class="col-md-6">
                <label for="importerNameEn" class="form-label">ชื่อ บนจ. (อังกฤษ)</label>
                <input type="text" class="form-control" id="importerNameEn" name="importerNameEn" value="{{ old('importerNameEn', $importer->importerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerId" class="form-label">เลขประจำตัว</label>
                <input type="text" class="form-control" id="importerId" name="importerId" value="{{ old('importerId', $importer->importerId) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="importerLicenseNo" class="form-label">เลขที่ใบอนุญาต</label>
                <input type="text" class="form-control" id="importerLicenseNo" name="importerLicenseNo" value="{{ old('importerLicenseNo', $importer->importerLicenseNo) }}">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseIssueDate" class="form-label">วันที่ออกใบอนุญาต</label>
                <input type="date" class="form-control" id="importerLicenseIssueDate" name="importerLicenseIssueDate" value="{{ old('importerLicenseIssueDate', $importer->importerLicenseIssueDate) }}">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseExpiryDate" class="form-label">วันสิ้นสุดใบอนุญาต</label>
                <input type="date" class="form-control" id="importerLicenseExpiryDate" name="importerLicenseExpiryDate" value="{{ old('importerLicenseExpiryDate', $importer->importerLicenseExpiryDate) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerSignerTh" class="form-label">คนเซ็น (ไทย)</label>
                <input type="text" class="form-control" id="importerSignerTh" name="importerSignerTh" value="{{ old('importerSignerTh', $importer->importerSignerTh) }}">
            </div>
            <div class="col-md-6">
                <label for="importerSignerEn" class="form-label">คนเซ็น (อังกฤษ)</label>
                <input type="text" class="form-control" id="importerSignerEn" name="importerSignerEn" value="{{ old('importerSignerEn', $importer->importerSignerEn) }}">
            </div>
        </div>

        <hr>
        <h5>{{ __('Other Documents') }}</h5>
        <div class="row mb-3">
            @for($i=1; $i<=3; $i++)
            @php $field = "importer_doc_other_$i"; $descField = "importer_doc_other_{$i}_desc"; @endphp
            <div class="col-md-4">
                <label for="{{ $field }}" class="form-label">{{ $i }}. {{ __('Other Document') }} {{ $i }} <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm mb-2">
                    <input type="file" class="form-control form-control-sm" id="{{ $field }}" name="{{ $field }}" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $field }}' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @if($importer->$field)
                <div class="mb-2">
                    <a href="{{ route('importers.documents.pdf', ['importer' => $importer->id, 'field' => $field]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Preview PDF">
                        <i class="bi bi-file-earmark-pdf"></i> Preview
                    </a>
                    <a href="{{ route('importers.documents.pdf', ['importer' => $importer->id, 'field' => $field]) }}?disposition=attachment" class="btn btn-sm btn-outline-secondary" title="Download PDF">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
                @endif
                <input type="text" class="form-control form-control-sm" id="{{ $descField }}" name="{{ $descField }}" value="{{ old($descField, $importer->$descField) }}" placeholder="{{ __('Specify description...') }}">
            </div>
            @endfor
        </div>

        <hr>

        {{-- Signatures & Stamp Section (Replicated from Employer) --}}
        <div class="content-section mt-4">
            <h5 class="mb-3">{{ __('Signatures & Stamp') }}</h5>

            <div class="row">
                <!-- Signer 1 -->
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100" x-data="signatureField('signature_1', '{{ $importer->signature_1_path ? Storage::url($importer->signature_1_path) : '' }}')">
                        <div class="card-body">
                            <h6 class="card-title text-muted fw-bold">{{ __('Signer 1') }}</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="importerSignerTh" class="form-label">{{ __('Name (Thai)') }}</label>
                                    <input type="text" class="form-control form-control-sm" id="importerSignerTh" name="importerSignerTh" value="{{ old('importerSignerTh', $importer->importerSignerTh) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="importerSignerEn" class="form-label">{{ __('Name (English)') }}</label>
                                    <input type="text" class="form-control form-control-sm" id="importerSignerEn" name="importerSignerEn" value="{{ old('importerSignerEn', $importer->importerSignerEn) }}">
                                </div>
                            </div>

                            <!-- Hidden Inputs for State Persistence -->
                            <input type="hidden" name="signature_1_action" x-model="action">
                            <input type="hidden" name="signature_1_base64" x-model="base64">
                            <div x-ref="fileInputContainer" class="d-none"></div>

                            <!-- Preview and Trigger -->
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3 mt-2">
                                 <div class="d-flex flex-column">
                                    <div class="border rounded p-2 bg-white d-flex align-items-center justify-content-center position-relative" style="width: 200px; height: 100px;">
                                         <template x-if="action === 'generate'">
                                             <span class="text-muted small"><i class="bi bi-magic me-1"></i> {{ __('Auto Generate') }}</span>
                                         </template>
                                         <template x-if="action !== 'generate' && !previewUrl">
                                             <span class="text-muted small">{{ __('No Signature') }}</span>
                                         </template>
                                         <template x-if="action !== 'generate' && previewUrl">
                                             <img :src="previewUrl" alt="Signature" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                         </template>

                                         <template x-if="base64 || uploadedFile">
                                             <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-1" style="font-size: 0.6rem;">{{ __('Pending Save') }}</span>
                                         </template>
                                    </div>
                                 </div>
                                 <div class="mt-md-4">
                                    <button type="button" class="btn btn-outline-primary" @click="$dispatch('open-signature-modal', { field: 'signature_1', currentAction: action, currentUrl: previewUrl, currentBase64: base64 })">
                                        <i class="bi bi-pen me-2"></i> {{ __('Signature Settings') }}
                                    </button>
                                    <div class="form-text mt-1">{{ __('Click to edit, upload, or draw signature.') }}</div>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signer 2 -->
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100" x-data="signatureField('signature_2', '{{ $importer->signature_2_path ? Storage::url($importer->signature_2_path) : '' }}')">
                        <div class="card-body">
                            <h6 class="card-title text-muted fw-bold">{{ __('Signer 2 (Optional)') }}</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="signer_2_name_th" class="form-label">{{ __('Name (Thai)') }}</label>
                                    <input type="text" class="form-control form-control-sm" id="signer_2_name_th" name="signer_2_name_th" value="{{ old('signer_2_name_th', $importer->signer_2_name_th) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="signer_2_name_en" class="form-label">{{ __('Name (English)') }}</label>
                                    <input type="text" class="form-control form-control-sm" id="signer_2_name_en" name="signer_2_name_en" value="{{ old('signer_2_name_en', $importer->signer_2_name_en) }}">
                                </div>
                            </div>

                             <!-- Hidden Inputs for State Persistence -->
                             <input type="hidden" name="signature_2_action" x-model="action">
                             <input type="hidden" name="signature_2_base64" x-model="base64">
                             <div x-ref="fileInputContainer" class="d-none"></div>

                             <!-- Preview and Trigger -->
                             <div class="d-flex flex-column flex-md-row align-items-start gap-3 mt-2">
                                  <div class="d-flex flex-column">
                                     <div class="border rounded p-2 bg-white d-flex align-items-center justify-content-center position-relative" style="width: 200px; height: 100px;">
                                          <template x-if="action === 'generate'">
                                              <span class="text-muted small"><i class="bi bi-magic me-1"></i> {{ __('Auto Generate') }}</span>
                                          </template>
                                          <template x-if="action !== 'generate' && !previewUrl">
                                              <span class="text-muted small">{{ __('No Signature') }}</span>
                                          </template>
                                          <template x-if="action !== 'generate' && previewUrl">
                                              <img :src="previewUrl" alt="Signature" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                          </template>

                                          <template x-if="base64 || uploadedFile">
                                              <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-1" style="font-size: 0.6rem;">{{ __('Pending Save') }}</span>
                                          </template>
                                     </div>
                                  </div>
                                  <div class="mt-md-4">
                                     <button type="button" class="btn btn-outline-primary" @click="$dispatch('open-signature-modal', { field: 'signature_2', currentAction: action, currentUrl: previewUrl, currentBase64: base64 })">
                                         <i class="bi bi-pen me-2"></i> {{ __('Signature Settings') }}
                                     </button>
                                     <div class="form-text mt-1">{{ __('Click to edit, upload, or draw signature.') }}</div>
                                  </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Importer Stamp -->
            <div class="card bg-light mb-3" x-data="signatureField('importer_stamp', '{{ $importer->importer_stamp_path ? Storage::url($importer->importer_stamp_path) : '' }}')">
                <div class="card-body">
                    <h6 class="card-title text-muted fw-bold">{{ __('Importer Stamp (Optional)') }}</h6>

                     <!-- Hidden Inputs for State Persistence -->
                     <input type="hidden" name="importer_stamp_action" x-model="action">
                     <input type="hidden" name="importer_stamp_base64" x-model="base64">
                     <div x-ref="fileInputContainer" class="d-none"></div>

                     <!-- Preview and Trigger -->
                     <div class="d-flex flex-column flex-md-row align-items-start gap-3 mt-2">
                          <div class="d-flex flex-column">
                             <div class="border rounded p-2 bg-white d-flex align-items-center justify-content-center position-relative" style="width: 150px; height: 150px;">
                                  <template x-if="action === 'generate'">
                                      <span class="text-muted small">{{ __('N/A') }}</span> <!-- Cannot auto-generate stamp -->
                                  </template>
                                  <template x-if="action !== 'generate' && !previewUrl">
                                      <span class="text-muted small">{{ __('No Stamp') }}</span>
                                  </template>
                                  <template x-if="action !== 'generate' && previewUrl">
                                      <img :src="previewUrl" alt="Stamp" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                  </template>

                                  <template x-if="base64 || uploadedFile">
                                      <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-1" style="font-size: 0.6rem;">{{ __('Pending Save') }}</span>
                                  </template>
                             </div>
                          </div>
                          <div class="mt-md-4">
                             <button type="button" class="btn btn-outline-primary" @click="$dispatch('open-signature-modal', { field: 'importer_stamp', currentAction: action, currentUrl: previewUrl, currentBase64: base64 })">
                                 <i class="bi bi-pen me-2"></i> {{ __('Stamp Settings') }}
                             </button>
                             <div class="form-text mt-1">{{ __('Click to upload or draw stamp.') }}</div>
                          </div>
                     </div>
                </div>
            </div>
        </div>

        <hr>
        <div id="addressListsContainer">
            {{-- Registered Address Section --}}
            <div class="content-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ __('Registered Address') }}</h5>
                    <button type="button" class="btn btn-sm btn-primary add-address-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal"
                            data-addressable-id="{{ $importer->id }}"
                            data-addressable-type="App\Models\Importer"
                            data-type="registered">
                        {{ __('Add Address') }}
                    </button>
                </div>
                <div id="registeredAddressList" class="vstack gap-3">
                    @forelse($importer->addresses->where('type', 'registered') as $addr)
                        <div class="address-card d-flex justify-content-between align-items-start border p-2 mb-2 rounded">
                            <div>
                                <p class="mb-0">{{ $addr->full_address_th }}</p>
                                <p class="mb-0 text-muted small">{{ $addr->full_address_en }}</p>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary edit-existing-address-btn" data-id="{{ $addr->id }}" data-type="registered" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-existing-address-btn" data-id="{{ $addr->id }}" data-type="registered" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No address yet') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Workplace Address Section --}}
            <div class="content-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ __('Workplace Address') }}</h5>
                    <button type="button" class="btn btn-sm btn-primary add-address-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal"
                            data-addressable-id="{{ $importer->id }}"
                            data-addressable-type="App\Models\Importer"
                            data-type="workplace">
                        {{ __('Add Address') }}
                    </button>
                </div>
                <div id="workplaceAddressList" class="vstack gap-3">
                     @forelse($importer->addresses->where('type', 'workplace') as $addr)
                        <div class="address-card d-flex justify-content-between align-items-start border p-2 mb-2 rounded">
                            <div>
                                <p class="mb-0">{{ $addr->full_address_th }}</p>
                                <p class="mb-0 text-muted small">{{ $addr->full_address_en }}</p>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary edit-existing-address-btn" data-id="{{ $addr->id }}" data-type="workplace" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-existing-address-btn" data-id="{{ $addr->id }}" data-type="workplace" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No address yet') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
            <a href="{{ route('importers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>

@include('partials._address_management')

<!-- Signature Settings Modal -->
<div class="modal fade" id="signatureSettingsModal" tabindex="-1" aria-labelledby="signatureSettingsModalLabel" aria-hidden="true" x-data="signatureModalController">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signatureSettingsModalLabel">{{ __('Signature Settings') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'generate' }" @click="activeTab = 'generate'" type="button">{{ __('Auto Generate') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'upload' }" @click="activeTab = 'upload'" type="button">{{ __('Upload File') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'draw' }" @click="activeTab = 'draw'" type="button">{{ __('Draw Signature') }}</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Auto Generate Tab -->
                    <div class="tab-pane fade" :class="{ 'show active': activeTab === 'generate' }">
                        <div class="text-center p-4 border rounded bg-light">
                             <p class="mb-3">{{ __('A signature will be automatically generated and saved for this user.') }}</p>
                             <div class="alert alert-info small mb-0">
                                <i class="bi bi-info-circle me-1"></i> {{ __('If a generated signature already exists, it will be kept unless you choose to regenerate.') }}
                             </div>
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div class="tab-pane fade" :class="{ 'show active': activeTab === 'upload' }">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Upload Image') }}</label>
                            <input type="file" class="form-control" accept="image/png, image/jpeg, image/jpg" @change="handleFileSelect">
                            <div class="form-text">{{ __('Max size: 2MB. Allowed formats: PNG, JPG.') }}</div>
                        </div>
                    </div>

                    <!-- Draw Tab -->
                    <div class="tab-pane fade" :class="{ 'show active': activeTab === 'draw' }">
                        <div class="mb-2">
                             <label class="form-label">{{ __('Draw below') }}</label>
                             <div class="position-relative">
                                 <canvas id="signature-pad" width="450" height="200" class="bg-white w-100"></canvas>
                             </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearCanvas">
                                <i class="bi bi-eraser"></i> {{ __('Clear') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" @click="saveSettings">{{ __('Save Temporary') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('signatureField', (fieldName, initialUrl) => ({
            action: 'keep', // keep, generate, upload, draw
            base64: '',
            previewUrl: initialUrl || '',

            init() {
                // Listen for save event from the modal
                window.addEventListener('signature-saved', (event) => {
                    if (event.detail.field === fieldName) {
                        this.action = event.detail.action;
                        this.base64 = event.detail.base64 || '';

                        // Update preview
                        if (this.action === 'draw' && this.base64) {
                            this.previewUrl = this.base64;
                        } else if (this.action === 'upload' && event.detail.file) {
                             // Create a local preview for the uploaded file
                             const reader = new FileReader();
                             reader.onload = (e) => { this.previewUrl = e.target.result; };
                             reader.readAsDataURL(event.detail.file);

                             // Instead of moving the DOM element which can be problematic,
                             // create a new input with DataTransfer to hold the file
                             const container = this.$refs.fileInputContainer;
                             container.innerHTML = ''; // clear previous

                             const dt = new DataTransfer();
                             dt.items.add(event.detail.file);

                             const newFileInput = document.createElement('input');
                             newFileInput.type = 'file';
                             newFileInput.name = fieldName + '_file';
                             newFileInput.classList.add('d-none');
                             newFileInput.files = dt.files;

                             container.appendChild(newFileInput);
                        } else if (this.action === 'generate') {
                            this.previewUrl = ''; // Clear it or show a placeholder icon
                        }
                    }
                });
            }
        }));

        Alpine.data('signatureModalController', () => ({
            activeTab: 'generate',
            targetField: '',
            canvas: null,
            ctx: null,
            isDrawing: false,
            uploadedFile: null,
            uploadedFileInput: null,
            modalInstance: null,

            init() {
                window.addEventListener('open-signature-modal', (event) => {
                    if (!this.modalInstance) {
                        this.modalInstance = new bootstrap.Modal(document.getElementById('signatureSettingsModal'));
                    }

                    this.targetField = event.detail.field;
                    const currentAction = event.detail.currentAction;

                    // Set initial tab based on current action
                    if (currentAction === 'keep') this.activeTab = 'generate'; // Default to generate if keeping
                    else if (currentAction === 'draw') this.activeTab = 'draw';
                    else if (currentAction === 'upload') this.activeTab = 'upload';
                    else this.activeTab = currentAction; // fallback

                    this.modalInstance.show();

                    // Init canvas after modal is shown
                    setTimeout(() => {
                        this.initCanvas();
                        if (currentAction === 'draw' && event.detail.currentBase64) {
                            this.loadCanvas(event.detail.currentBase64);
                        }
                    }, 500);
                });
            },

            initCanvas() {
                this.canvas = document.getElementById('signature-pad');
                this.ctx = this.canvas.getContext('2d');
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.strokeStyle = '#000';

                const getPos = (e) => {
                    const rect = this.canvas.getBoundingClientRect();
                    const scaleX = this.canvas.width / rect.width;
                    const scaleY = this.canvas.height / rect.height;

                    let clientX, clientY;
                    if (e.changedTouches) {
                         clientX = e.changedTouches[0].clientX;
                         clientY = e.changedTouches[0].clientY;
                    } else {
                         clientX = e.clientX;
                         clientY = e.clientY;
                    }

                    return {
                        x: (clientX - rect.left) * scaleX,
                        y: (clientY - rect.top) * scaleY
                    };
                };

                // Mouse Events
                this.canvas.onmousedown = (e) => {
                    this.isDrawing = true;
                    this.ctx.beginPath();
                    const pos = getPos(e);
                    this.ctx.moveTo(pos.x, pos.y);
                };
                this.canvas.onmousemove = (e) => {
                    if (this.isDrawing) {
                        const pos = getPos(e);
                        this.ctx.lineTo(pos.x, pos.y);
                        this.ctx.stroke();
                    }
                };
                this.canvas.onmouseup = () => { this.isDrawing = false; };
                this.canvas.onmouseout = () => { this.isDrawing = false; };

                // Touch Events
                this.canvas.ontouchstart = (e) => {
                    e.preventDefault();
                    this.isDrawing = true;
                    this.ctx.beginPath();
                    const pos = getPos(e);
                    this.ctx.moveTo(pos.x, pos.y);
                };
                this.canvas.ontouchmove = (e) => {
                    if (this.isDrawing) {
                        e.preventDefault();
                        const pos = getPos(e);
                        this.ctx.lineTo(pos.x, pos.y);
                        this.ctx.stroke();
                    }
                };
                this.canvas.ontouchend = () => { this.isDrawing = false; };
            },

            clearCanvas() {
                if(this.ctx) this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            },

            loadCanvas(base64) {
                 const img = new Image();
                 img.onload = () => {
                     this.clearCanvas();
                     this.ctx.drawImage(img, 0, 0);
                 };
                 img.src = base64;
            },

            handleFileSelect(e) {
                if (e.target.files.length > 0) {
                    this.uploadedFile = e.target.files[0];
                    this.uploadedFileInput = e.target;
                }
            },

            saveSettings() {
                let eventData = {
                    field: this.targetField,
                    action: this.activeTab,
                };

                if (this.activeTab === 'draw') {
                    eventData.base64 = this.canvas.toDataURL('image/png');
                } else if (this.activeTab === 'upload') {
                    if (this.uploadedFile) {
                        eventData.file = this.uploadedFile;

                        if (this.uploadedFileInput) {
                            this.uploadedFileInput.value = '';
                        }
                    } else {
                        alert('{{ __("Please select a file to upload.") }}');
                        return;
                    }
                }

                window.dispatchEvent(new CustomEvent('signature-saved', { detail: eventData }));
                this.modalInstance.hide();

                this.clearCanvas();
                this.uploadedFile = null;
                this.uploadedFileInput = null;
            }
        }));
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const importerId = {{ $importer->id }};

    document.getElementById('addressable_id').value = importerId;
    document.getElementById('addressable_type').value = 'App\\Models\\Importer';

    // Set address type when "Add Address" is clicked
    document.querySelectorAll('.add-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            const addressType = this.getAttribute('data-type');
            document.getElementById('address_type').value = addressType;
            document.getElementById('addressFormMethod').value = 'POST';
            addressForm.action = '{{ route('addresses.store') }}';
        });
    });

    document.getElementById('addressListsContainer').addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-existing-address-btn');
        const deleteBtn = e.target.closest('.delete-existing-address-btn');

        if (editBtn) {
            const id = editBtn.dataset.id;
            const type = editBtn.dataset.type;
            fetch(`/addresses/${id}/edit`)
                .then(response => response.json())
                .then(data => {
                    addressForm.reset();
                    for (const key in data) {
                        if (addressForm.elements[key]) {
                            addressForm.elements[key].value = data[key];
                            if(key === 'addrProvince' || key === 'addrDistrict' || key === 'addrSubDistrict') {
                                addressForm.elements[key].disabled = false;
                                addressForm.elements[key].dispatchEvent(new Event('options-updated'));
                            }
                        }
                    }
                    document.getElementById('address_id').value = id;
                    document.getElementById('address_type').value = type;
                    document.getElementById('addressFormMethod').value = 'PUT';
                    addressForm.action = `/addresses/${id}`;
                    addressModal.show();
                });
        }

        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            if (confirm('{{ __('Are you sure you want to delete this address?') }}')) {
                fetch(`/addresses/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    }
                });
            }
        }
    });

    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const action = this.action;
        const method = document.getElementById('addressFormMethod').value || 'POST';

        fetch(action, {
            method: 'POST', // Always POST for FormData, override with _method
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Error saving address');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        document.getElementById('address_id').value = '';
        document.getElementById('addressable_id').value = importerId;
        document.getElementById('addressable_type').value = 'App\\Models\\Importer';
        document.getElementById('addressFormMethod').value = '';
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').disabled = true;
    });
});
</script>
@endpush
