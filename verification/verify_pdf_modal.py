from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Define HTML content simulating the structure we need to test
        # Note: We are simulating the "Generate Modal" HTML structure
        # with Alpine.js and Bootstrap included via CDN for interaction.
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PDF Generation Test</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
            <style>
                .cursor-pointer { cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="container py-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Generate Automated PDF</h5>
                    </div>
                    <div class="card-body p-4" x-data="pdfGenerator()">
                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Select Employer (Owner of Template)</label>
                            <div x-data="employerSelector()" @click.outside="open = false">
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" placeholder="Type to search..." x-model="search" @focus="open = true">
                                        <button class="btn btn-outline-secondary dropdown-toggle" @click="open = !open"></button>
                                    </div>
                                    <div class="card position-absolute w-100 shadow mt-1 border-0" x-show="open" style="z-index: 1050; display:none;">
                                        <ul class="list-group list-group-flush">
                                            <template x-for="opt in filteredOptions" :key="opt.id">
                                                <li class="list-group-item list-group-item-action cursor-pointer" @click="selectOption(opt)">
                                                    <span x-text="opt.name_th"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Select Template</label>
                            <select class="form-select" x-model="selectedTemplateId" :disabled="isLoadingTemplates">
                                <option value="">-- Choose Template --</option>
                                <template x-for="t in templates" :key="t.id">
                                    <option :value="t.id" x-text="t.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">3. Output Destination</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_type" id="outputDownload" value="download" x-model="outputType">
                                    <label class="form-check-label" for="outputDownload">Download</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_type" id="outputSlot" value="save_to_slot" x-model="outputType">
                                    <label class="form-check-label" for="outputSlot">Save to Slot</label>
                                </div>
                            </div>
                        </div>

                         <div class="mb-4 p-3 bg-light rounded border" x-show="outputType === 'save_to_slot'" style="display: none;">
                            <label class="form-label fw-bold">Select Attachment Slot</label>
                            <select name="slot_name" class="form-select">
                                <option value="">-- Select Slot --</option>
                                <optgroup label="Employee Documents (เอกสารลูกจ้าง)">
                                    <option value="employee_doc_9">Employee Other Document 1 (เอกสารอื่นๆ 1)</option>
                                    <option value="employee_doc_18">Employee Other Document 10 (เอกสารอื่นๆ 10)</option>
                                </optgroup>
                                <optgroup label="Employer Documents (เอกสารนายจ้าง)">
                                    <option value="employer_doc_other_1">Employer Other Document 1 (เอกสารอื่นๆ 1)</option>
                                    <option value="employer_doc_other_3">Employer Other Document 3 (เอกสารอื่นๆ 3)</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('pdfGenerator', () => ({
                        outputType: 'download',
                        templates: [], // Start empty
                        selectedTemplateId: '',
                        isLoadingTemplates: false,
                        selectedEmployerId: 'global',

                        init() {
                             window.addEventListener('employer-selected', (e) => {
                                this.selectedEmployerId = e.detail.id;
                                this.fetchTemplates();
                            });
                            // Mock initial fetch for global
                            this.fetchTemplates();
                        },

                        fetchTemplates() {
                            this.isLoadingTemplates = true;
                            console.log('Fetching templates for:', this.selectedEmployerId);

                            // MOCK API CALL
                            setTimeout(() => {
                                if(this.selectedEmployerId === 'global') {
                                    this.templates = [{id: 1, name: 'Global Template A'}, {id: 2, name: 'Global Template B'}];
                                } else {
                                    this.templates = [{id: 3, name: 'Company Specific Template 1'}, {id: 1, name: 'Global Template A'}];
                                }
                                this.isLoadingTemplates = false;
                            }, 500);
                        }
                    }));

                    Alpine.data('employerSelector', () => ({
                        search: '',
                        open: false,
                        options: [
                            {id: 'global', name_th: 'Global Templates Only (ส่วนกลาง)', search_str: 'global'},
                            {id: 101, name_th: 'Company A Co., Ltd.', search_str: 'company a'},
                            {id: 102, name_th: 'Company B Ltd.', search_str: 'company b'}
                        ],
                        get filteredOptions() {
                            if (this.search === '') return this.options;
                            return this.options.filter(o => o.search_str.includes(this.search.toLowerCase()));
                        },
                        selectOption(opt) {
                            console.log('Selected:', opt);
                            this.open = false;
                            this.search = opt.name_th;
                            window.dispatchEvent(new CustomEvent('employer-selected', { detail: { id: opt.id } }));
                        }
                    }));
                });
            </script>
        </body>
        </html>
        """

        page.set_content(html_content)

        # 1. Take initial screenshot (Default Global)
        page.screenshot(path="verification/1_initial_state.png")

        # 2. Open Employer Dropdown
        page.click("input[placeholder='Type to search...']")
        page.screenshot(path="verification/2_dropdown_open.png")

        # 3. Select Company A
        page.click("text=Company A Co., Ltd.")
        page.wait_for_timeout(600) # Wait for "Mock API"
        page.screenshot(path="verification/3_company_selected.png")

        # 4. Select "Save to Slot" to see the mapped options
        page.click("label[for='outputSlot']")
        page.wait_for_timeout(300) # Transition
        page.screenshot(path="verification/4_save_slot_options.png")

        browser.close()

if __name__ == "__main__":
    run()
