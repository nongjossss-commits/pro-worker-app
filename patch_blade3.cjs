const fs = require('fs');
const file = 'resources/views/production/partials/financial-tab.blade.php';
let content = fs.readFileSync(file, 'utf8');

const oldTotalProjectValue = `<label class="form-label">{{ __('Total Project Value') }} <span x-show="!vatIncluded">{{ __('(Excl. VAT)') }}</span><span x-show="vatIncluded">{{ __('(Incl. VAT)') }}</span></label>`;
const newTotalProjectValue = `<label class="form-label">{{ __('Total Project Value') }} <span x-show="vatEnabled && !vatIncluded">{{ __('(Excl. VAT)') }}</span><span x-show="vatEnabled && vatIncluded">{{ __('(Incl. VAT)') }}</span></label>`;

content = content.replace(oldTotalProjectValue, newTotalProjectValue);
fs.writeFileSync(file, content);
