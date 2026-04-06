const fs = require('fs');
const file = 'resources/views/production/partials/financial-tab.blade.php';
let content = fs.readFileSync(file, 'utf8');

const oldVatHtml = `                    <!-- VAT -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" :id="'vatIncluded_' + productionId" x-model="vatIncluded" @change="updateTotal()">
                            <label class="form-check-label small" :for="'vatIncluded_' + productionId">{{ __('Price Includes VAT') }}</label>
                        </div>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <span class="input-group-text">VAT</span>
                            <input type="number" step="0.1" class="form-control text-end" x-model="vatRate" @input="updateTotal()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>`;

const newVatHtml = `                    <!-- VAT Toggle -->
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2" :class="vatEnabled ? 'border-bottom' : ''">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" :id="'vatEnabled_' + productionId" x-model="vatEnabled" @change="updateTotal()">
                            <label class="form-check-label small fw-bold" :for="'vatEnabled_' + productionId">{{ __('Enable VAT') }}</label>
                        </div>
                    </div>

                    <!-- VAT Settings -->
                    <div class="d-flex align-items-center justify-content-between mb-3" x-show="vatEnabled">
                        <div class="form-check form-switch ps-4">
                            <input class="form-check-input" type="checkbox" :id="'vatIncluded_' + productionId" x-model="vatIncluded" @change="updateTotal()">
                            <label class="form-check-label small text-muted" :for="'vatIncluded_' + productionId">{{ __('Price Includes VAT') }}</label>
                        </div>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <span class="input-group-text">VAT</span>
                            <input type="number" step="0.1" class="form-control text-end" x-model="vatRate" @input="updateTotal()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>`;

content = content.replace(oldVatHtml, newVatHtml);
fs.writeFileSync(file, content);
