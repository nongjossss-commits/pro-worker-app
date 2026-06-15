// Quick Capture — Alpine x-data for receipt/text → AI extract → ledger form.
// NOTE: no `defer` on the <script> tag — register before alpine:init fires.

document.addEventListener('alpine:init', () => {
    Alpine.data('quickCapture', ({ extractUrl, csrf, accounts, incomeCategories, expenseCategories }) => ({
        extractUrl,
        csrf,
        accounts: accounts || [],
        incomeCategories: incomeCategories || [],
        expenseCategories: expenseCategories || [],

        mode: 'image',
        text: '',
        capturedFile: null,
        imagePreview: null,
        extracting: false,
        extractInfo: '',
        extractSucceeded: false,

        aiSource: 'manual',
        aiConfidence: null,
        aiExtractedJson: '',

        form: {
            entry_date: new Date().toISOString().slice(0, 10),
            type: 'expense',
            bank_account_id: '',
            category_id: '',
            counterparty_name: '',
            counterparty_tax_id: '',
            gross_amount: '',
            vat_treatment: 'none',
            vat_rate: 7,
            wht_type: 'none',
            wht_rate: 0,
            description: '',
            notes: '',
        },

        get activeCategories() {
            return this.form.type === 'income' ? this.incomeCategories : this.expenseCategories;
        },

        currentAccount() {
            return this.accounts.find(a => String(a.id) === String(this.form.bank_account_id));
        },

        isPersonal() {
            const a = this.currentAccount();
            return a && a.account_type === 'personal';
        },

        onAccountChange() {
            if (this.isPersonal()) {
                this.form.vat_treatment = 'none';
                this.form.vat_rate = 0;
                this.form.wht_type = 'none';
                this.form.wht_rate = 0;
            }
        },

        onTypeChange() {
            this.form.category_id = '';
        },

        onCategoryChange() {
            if (this.isPersonal()) return;
            const cat = this.activeCategories.find(c => String(c.id) === String(this.form.category_id));
            if (!cat) return;
            if (cat.default_vat_treatment) {
                this.form.vat_treatment = cat.default_vat_treatment;
                if (cat.default_vat_treatment === 'taxable' && (!this.form.vat_rate || this.form.vat_rate === 0)) {
                    this.form.vat_rate = 7;
                }
                if (['none', 'exempt', 'zero_rate'].includes(cat.default_vat_treatment)) {
                    this.form.vat_rate = 0;
                }
            }
            if (cat.default_wht_type) {
                this.form.wht_type = cat.default_wht_type;
            }
            if (cat.default_wht_rate !== undefined && cat.default_wht_rate !== null) {
                this.form.wht_rate = parseFloat(cat.default_wht_rate) || 0;
            }
        },

        onImagePick(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            this.capturedFile = file;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => { this.imagePreview = ev.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.imagePreview = null;
            }
        },

        resetCapture() {
            this.text = '';
            this.capturedFile = null;
            this.imagePreview = null;
            this.extractInfo = '';
            this.extractSucceeded = false;
            this.aiExtractedJson = '';
            this.aiSource = 'manual';
            this.aiConfidence = null;
        },

        async runExtract() {
            this.extracting = true;
            this.extractInfo = '';
            this.extractSucceeded = false;

            const formData = new FormData();
            formData.append('_token', this.csrf);
            formData.append('mode', this.mode);
            if (this.mode === 'image' && this.capturedFile) {
                formData.append('image', this.capturedFile);
            }
            if (this.mode === 'text') {
                formData.append('text', this.text);
            }

            try {
                const resp = await fetch(this.extractUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                });
                const json = await resp.json();
                this.aiSource = (json.raw && json.raw.source) || (this.mode === 'image' ? 'ocr' : 'text');
                this.aiConfidence = (json.raw && json.raw.confidence) || null;
                this.aiExtractedJson = JSON.stringify(json.raw || {});
                this.extractSucceeded = !!json.succeeded;
                this.extractInfo = json.message || (json.succeeded ? 'Extracted — please review and save.' : 'Engine returned no fields — please fill manually.');

                if (json.prefill) {
                    Object.entries(json.prefill).forEach(([key, value]) => {
                        if (value !== undefined && value !== null && this.form[key] !== undefined) {
                            this.form[key] = value;
                        }
                    });
                }
            } catch (err) {
                this.extractInfo = 'Extract failed: ' + (err && err.message ? err.message : 'network error');
            } finally {
                this.extracting = false;
            }
        },

        reuseCapturedReceipt() {
            if (!this.capturedFile || !this.$refs.receiptInput) return;
            const dt = new DataTransfer();
            dt.items.add(this.capturedFile);
            this.$refs.receiptInput.files = dt.files;
        },
    }));
});
