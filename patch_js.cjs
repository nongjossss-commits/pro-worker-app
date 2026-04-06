const fs = require('fs');
const file = 'public/js/financial-manager.js';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
    /vatIncluded: false,/,
    `vatEnabled: true,
            vatIncluded: false,`
);

content = content.replace(
    /this\.vatIncluded = !!data\.vat_included;/,
    `this.vatEnabled = data.vat_enabled !== false;
                this.vatIncluded = !!data.vat_included;`
);

content = content.replace(
    /vat_included: this\.vatIncluded,/,
    `vat_enabled: this.vatEnabled,
                    vat_included: this.vatIncluded,`
);

const updateTotalOld = `                if (this.vatIncluded) {
                    this.totalAmount = netBase;
                    this.subtotalAmount = netBase / (1 + (this.vatRate / 100));
                    this.vatAmount = this.totalAmount - this.subtotalAmount;
                } else {
                    this.subtotalAmount = netBase;
                    this.vatAmount = netBase * (this.vatRate / 100);
                    this.totalAmount = this.subtotalAmount + this.vatAmount;
                }`;

const updateTotalNew = `                if (!this.vatEnabled) {
                    this.totalAmount = netBase;
                    this.subtotalAmount = netBase;
                    this.vatAmount = 0;
                } else if (this.vatIncluded) {
                    this.totalAmount = netBase;
                    this.subtotalAmount = netBase / (1 + (this.vatRate / 100));
                    this.vatAmount = this.totalAmount - this.subtotalAmount;
                } else {
                    this.subtotalAmount = netBase;
                    this.vatAmount = netBase * (this.vatRate / 100);
                    this.totalAmount = this.subtotalAmount + this.vatAmount;
                }`;

content = content.replace(updateTotalOld, updateTotalNew);

fs.writeFileSync(file, content);
