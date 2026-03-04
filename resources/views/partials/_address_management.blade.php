<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่ม/แก้ไขที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Add novalidate to prevent browser's default validation --}}
                <form id="addressForm" novalidate>
                    {{-- The form's action and method will be set dynamically via JS --}}
                    @csrf
                    {{-- Hidden input for method spoofing (for PUT requests) --}}
                    <input type="hidden" name="_method" id="addressFormMethod">
                    <input type="hidden" id="address_id" name="id">
                    <input type="hidden" id="addressable_id" name="addressable_id">
                    <input type="hidden" id="addressable_type" name="addressable_type" value="App\Models\Employer">
                    <input type="hidden" id="address_type" name="type">

                    {{-- Row 1: No --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrNo" class="form-label">บ้านเลขที่ (ไทย)</label>
                            <input type="text" class="form-control" id="addrNo" name="addrNo">
                            <div class="invalid-feedback" id="addrNoError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrNoEn" class="form-label">Address No. (EN)</label>
                            <input type="text" class="form-control" id="addrNoEn" name="addrNoEn">
                             <div class="invalid-feedback" id="addrNoEnError"></div>
                        </div>
                    </div>
                    {{-- Row 2: Moo --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrMoo" class="form-label">หมู่ (ไทย)</label>
                            <input type="text" class="form-control" id="addrMoo" name="addrMoo">
                             <div class="invalid-feedback" id="addrMooError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrMooEn" class="form-label">Moo (EN)</label>
                            <input type="text" class="form-control" id="addrMooEn" name="addrMooEn">
                             <div class="invalid-feedback" id="addrMooEnError"></div>
                        </div>
                    </div>
                    {{-- Row 3: Soi --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrSoi" class="form-label">ซอย (ไทย)</label>
                            <input type="text" class="form-control" id="addrSoi" name="addrSoi">
                             <div class="invalid-feedback" id="addrSoiError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrSoiEn" class="form-label">Soi (EN)</label>
                            <input type="text" class="form-control" id="addrSoiEn" name="addrSoiEn">
                             <div class="invalid-feedback" id="addrSoiEnError"></div>
                        </div>
                    </div>
                    {{-- Row 4: Road --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrRoad" class="form-label">ถนน (ไทย)</label>
                            <input type="text" class="form-control" id="addrRoad" name="addrRoad">
                             <div class="invalid-feedback" id="addrRoadError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrRoadEn" class="form-label">Road (EN)</label>
                            <input type="text" class="form-control" id="addrRoadEn" name="addrRoadEn">
                             <div class="invalid-feedback" id="addrRoadEnError"></div>
                        </div>
                    </div>
                    {{-- Row 5: Province --}}
                    <div class="row mb-3">
                        <div class="col-md-6" x-data="searchableSelect('addrProvince')">
                            <label for="addrProvince" class="form-label">จังหวัด (Thai)</label>
                            <div class="position-relative">
                                <select class="form-select d-none" name="addrProvince" id="addrProvince" x-ref="select">
                                    <option selected disabled value="">-- เลือกจังหวัด --</option>
                                </select>
                                <div class="dropdown">
                                    <input type="text"
                                           class="form-control"
                                           x-model="search"
                                           @focus="open()"
                                           @click="open()"
                                           @input="filter()"
                                           @keydown.escape="close()"
                                           @click.outside="close()"
                                           placeholder="-- เลือกจังหวัด --"
                                           :class="{'is-invalid': hasError}"
                                           :disabled="isDisabled"
                                    >
                                    <ul class="dropdown-menu w-100"
                                        style="max-height: 250px; overflow-y: auto;"
                                        x-show="isOpen"
                                        :class="{'show': isOpen}"
                                        x-transition>
                                        <template x-for="option in filteredOptions" :key="option.value">
                                            <li>
                                                <button type="button"
                                                        class="dropdown-item"
                                                        @click="select(option)"
                                                        :class="{'active': option.value === value}"
                                                        x-text="option.text"></button>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0" class="p-2 text-muted text-center small">ไม่พบข้อมูล (No results)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="invalid-feedback" id="addrProvinceError" x-show="hasError" style="display: block;"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrProvinceEn" class="form-label">Province (EN)</label>
                            <input type="text" class="form-control" id="addrProvinceEn" name="addrProvinceEn" readonly>
                        </div>
                    </div>
                    {{-- Row 6: District --}}
                    <div class="row mb-3">
                        <div class="col-md-6" x-data="searchableSelect('addrDistrict')">
                            <label for="addrDistrict" class="form-label">อำเภอ/เขต (Thai)</label>
                            <div class="position-relative">
                                <select class="form-select d-none" name="addrDistrict" id="addrDistrict" x-ref="select" disabled>
                                    <option selected disabled value="">-- เลือกอำเภอ/เขต --</option>
                                </select>
                                <div class="dropdown">
                                    <input type="text"
                                           class="form-control"
                                           x-model="search"
                                           @focus="open()"
                                           @click="open()"
                                           @input="filter()"
                                           @keydown.escape="close()"
                                           @click.outside="close()"
                                           placeholder="-- เลือกอำเภอ/เขต --"
                                           :class="{'is-invalid': hasError}"
                                           :disabled="isDisabled"
                                    >
                                    <ul class="dropdown-menu w-100"
                                        style="max-height: 250px; overflow-y: auto;"
                                        x-show="isOpen"
                                        :class="{'show': isOpen}"
                                        x-transition>
                                        <template x-for="option in filteredOptions" :key="option.value">
                                            <li>
                                                <button type="button"
                                                        class="dropdown-item"
                                                        @click="select(option)"
                                                        :class="{'active': option.value === value}"
                                                        x-text="option.text"></button>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0" class="p-2 text-muted text-center small">ไม่พบข้อมูล (No results)</li>
                                    </ul>
                                </div>
                            </div>
                             <div class="invalid-feedback" id="addrDistrictError" x-show="hasError" style="display: block;"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrDistrictEn" class="form-label">District (EN)</label>
                            <input type="text" class="form-control" id="addrDistrictEn" name="addrDistrictEn" readonly>
                        </div>
                    </div>
                    {{-- Row 7: SubDistrict --}}
                    <div class="row mb-3">
                        <div class="col-md-6" x-data="searchableSelect('addrSubDistrict')">
                            <label for="addrSubDistrict" class="form-label">ตำบล/แขวง (Thai)</label>
                            <div class="position-relative">
                                <select class="form-select d-none" name="addrSubDistrict" id="addrSubDistrict" x-ref="select" disabled>
                                    <option selected disabled value="">-- เลือกตำบล/แขวง --</option>
                                </select>
                                <div class="dropdown">
                                    <input type="text"
                                           class="form-control"
                                           x-model="search"
                                           @focus="open()"
                                           @click="open()"
                                           @input="filter()"
                                           @keydown.escape="close()"
                                           @click.outside="close()"
                                           placeholder="-- เลือกตำบล/แขวง --"
                                           :class="{'is-invalid': hasError}"
                                           :disabled="isDisabled"
                                    >
                                    <ul class="dropdown-menu w-100"
                                        style="max-height: 250px; overflow-y: auto;"
                                        x-show="isOpen"
                                        :class="{'show': isOpen}"
                                        x-transition>
                                        <template x-for="option in filteredOptions" :key="option.value">
                                            <li>
                                                <button type="button"
                                                        class="dropdown-item"
                                                        @click="select(option)"
                                                        :class="{'active': option.value === value}"
                                                        x-text="option.text"></button>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0" class="p-2 text-muted text-center small">ไม่พบข้อมูล (No results)</li>
                                    </ul>
                                </div>
                            </div>
                             <div class="invalid-feedback" id="addrSubDistrictError" x-show="hasError" style="display: block;"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrSubDistrictEn" class="form-label">Sub-district (EN)</label>
                            <input type="text" class="form-control" id="addrSubDistrictEn" name="addrSubDistrictEn" readonly>
                        </div>
                    </div>
                     <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrZipCode" class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" id="addrZipCode" name="addrZipCode" readonly>
                             <div class="invalid-feedback" id="addrZipCodeError"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                {{-- Changed to type="submit" and linked to the form --}}
                <button type="submit" class="btn btn-primary" id="saveAddressBtn" form="addressForm">บันทึก</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('searchableSelect', (selectId) => ({
            options: [],
            search: '',
            value: '',
            isOpen: false,
            isDisabled: false,
            hasError: false,
            el: null,
            observer: null,

            init() {
                this.el = document.getElementById(selectId);
                // Initial sync
                this.updateFromSelect();

                // 1. Observe changes to options (childList) and attributes (disabled, class)
                this.observer = new MutationObserver((mutations) => {
                    let needsUpdate = false;
                    mutations.forEach(m => {
                         if (m.type === 'attributes' && m.attributeName === 'class') {
                             this.hasError = this.el.classList.contains('is-invalid');
                         }
                         if (m.type === 'childList') {
                             needsUpdate = true;
                         }
                    });
                    if (needsUpdate) {
                        this.updateFromSelect();
                    }
                });
                this.observer.observe(this.el, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'class', 'style'] });

                // 2. Listen for 'change' events dispatched by other scripts
                this.el.addEventListener('change', () => {
                    this.updateValue();
                });

                this.el.addEventListener('options-updated', () => {
                    this.updateFromSelect();
                });

                // 3. Listen for specific modal events if necessary.
                const modal = document.getElementById('addressModal');
                if(modal) {
                     modal.addEventListener('shown.bs.modal', () => {
                         setTimeout(() => this.updateFromSelect(), 100);
                     });
                     // Also on hidden to reset
                     modal.addEventListener('hidden.bs.modal', () => {
                         this.updateFromSelect();
                         this.isOpen = false;
                     });
                }
            },

            updateFromSelect() {
                // Sync Options
                this.options = Array.from(this.el.options)
                    .filter(opt => opt.value !== "") // Filter out placeholder
                    .map(opt => ({
                        value: opt.value,
                        text: opt.text
                    }));

                // Sync Disabled State
                this.isDisabled = this.el.disabled;

                // Sync Error State
                this.hasError = this.el.classList.contains('is-invalid');

                // Sync Value
                this.updateValue();
            },

            updateValue() {
                this.value = this.el.value;
                const selectedOption = this.options.find(o => o.value === this.value);
                if (selectedOption) {
                    this.search = selectedOption.text;
                } else {
                    this.search = '';
                }
            },

            get filteredOptions() {
                if (this.search === '') {
                    return this.options;
                }
                const lowerSearch = this.search.toLowerCase();
                return this.options.filter(o =>
                    o.text.toLowerCase().includes(lowerSearch)
                );
            },

            open() {
                if (this.isDisabled) return;
                this.isOpen = true;
                if(this.search === '') {
                     // If empty, maybe show all? Yes, handled by filteredOptions
                }
            },

            close() {
                this.isOpen = false;
                // If closed without selecting
                const match = this.options.find(o => o.text === this.search);
                if (!match && this.search !== '') {
                     // Revert to last valid value
                     this.updateValue();
                } else if (this.search === '') {
                    if (this.value !== '') {
                        // Clear selection
                        this.select({value: '', text: ''});
                    }
                }
            },

            filter() {
                this.isOpen = true;
            },

            select(option) {
                this.value = option.value;
                this.search = option.text;
                this.el.value = option.value;
                this.isOpen = false;
                // Dispatch change event so vanilla JS cascading works
                this.el.dispatchEvent(new Event('change'));
            }
        }));
    });
</script>
@endpush
