const fs = require('fs');
const file = 'resources/views/production/partials/financial-tab.blade.php';
let content = fs.readFileSync(file, 'utf8');

const oldSummary = `                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Service Base (Excl. VAT)') }}:</span>
                    <span x-text="formatCurrency(subtotalAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">VAT (<span x-text="vatRate"></span>%):</span>
                    <span x-text="formatCurrency(vatAmount)"></span>
                </div>

                <div class="d-flex justify-content-between mb-2 fw-bold bg-light p-1 rounded">
                    <span>Service Total (Inc. VAT):</span>
                    <span x-text="formatCurrency(totalAmount)"></span>
                </div>`;

const newSummary = `                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" x-show="vatEnabled">{{ __('Service Base (Excl. VAT)') }}:</span>
                    <span class="text-muted" x-show="!vatEnabled">{{ __('Net Amount') }}:</span>
                    <span x-text="formatCurrency(subtotalAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1" x-show="vatEnabled">
                    <span class="text-muted">VAT (<span x-text="vatRate"></span>%):</span>
                    <span x-text="formatCurrency(vatAmount)"></span>
                </div>

                <div class="d-flex justify-content-between mb-2 fw-bold bg-light p-1 rounded">
                    <span x-show="vatEnabled">Service Total (Inc. VAT):</span>
                    <span x-show="!vatEnabled">Service Total:</span>
                    <span x-text="formatCurrency(totalAmount)"></span>
                </div>`;

content = content.replace(oldSummary, newSummary);
fs.writeFileSync(file, content);
