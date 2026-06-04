// Ledger Form — Alpine x-data scope for real-time tax calculation
// NOTE: no 'defer' on the script tag per project pattern (see CLAUDE.md / feedback memory)

document.addEventListener('alpine:init', () => {
    Alpine.data('ledgerForm', ({ type, accounts, categories }) => ({
        type,
        accounts: accounts || [],
        categories: categories || [],
        bankAccountId: '',
        categoryId: '',
        gross: 0,
        vatTreatment: 'none',
        vatRate: 7,
        vatInclusive: false,
        whtType: 'none',
        whtRate: 0,
        preview: { subtotal: 0, vat_amount: 0, wht_amount: 0, net_amount: 0 },

        currentAccount() {
            return this.accounts.find(a => String(a.id) === String(this.bankAccountId));
        },

        isPersonal() {
            const a = this.currentAccount();
            return a && a.account_type === 'personal';
        },

        onAccountChange() {
            if (this.isPersonal()) {
                this.vatTreatment = 'none';
                this.vatRate = 0;
                this.vatInclusive = false;
                this.whtType = 'none';
                this.whtRate = 0;
            }
            this.recalculate();
        },

        onCategoryChange() {
            if (this.isPersonal()) return;
            const cat = this.categories.find(c => String(c.id) === String(this.categoryId));
            if (!cat) return;

            const vat = cat.default_vat_treatment || 'none';
            this.vatTreatment = vat;
            if (vat === 'taxable' && (!this.vatRate || this.vatRate === 0)) this.vatRate = 7;
            if (vat === 'none' || vat === 'exempt' || vat === 'zero_rate') this.vatRate = 0;

            this.whtType = cat.default_wht_type || 'none';
            this.whtRate = parseFloat(cat.default_wht_rate || 0);

            this.recalculate();
        },

        recalculate() {
            const gross = parseFloat(this.gross) || 0;

            if (this.isPersonal()) {
                this.preview = { subtotal: gross, vat_amount: 0, wht_amount: 0, net_amount: gross };
                return;
            }

            let subtotal = gross;
            let vatAmount = 0;

            if (this.vatTreatment === 'taxable' && this.vatRate > 0) {
                if (this.vatInclusive) {
                    subtotal = gross / (1 + (this.vatRate / 100));
                    vatAmount = gross - subtotal;
                } else {
                    subtotal = gross;
                    vatAmount = gross * (this.vatRate / 100);
                }
            }

            let whtAmount = 0;
            if (this.whtType !== 'none' && this.whtRate > 0) {
                whtAmount = subtotal * (this.whtRate / 100);
            }

            const netAmount = subtotal + vatAmount - whtAmount;

            this.preview = {
                subtotal: this.round(subtotal),
                vat_amount: this.round(vatAmount),
                wht_amount: this.round(whtAmount),
                net_amount: this.round(netAmount),
            };
        },

        round(n) {
            return Math.round((n + Number.EPSILON) * 100) / 100;
        },

        fmt(n) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
        },
    }));
});
