from playwright.sync_api import sync_playwright, expect
import json

def verify_attachment_basket():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        # Mock data for pre-selected employees (API response)
        mock_employees = [
            {
                "id": 101,
                "employer_name": "Employer A",
                "employeeNameTh": "นาย ทดสอบ หนึ่ง",
                "employeeNameEn": "Mr. Test One",
                "employeePassport": "PP123456",
                "companyWorkerId": "WORK001",
                "photo_url": "https://placehold.co/40x40",
                "nationality": "เมียนมา",
                "flag_url": "https://placehold.co/20x15"
            },
            {
                "id": 102,
                "employer_name": "Employer A",
                "employeeNameTh": "นางสาว ทดสอบ สอง",
                "employeeNameEn": "Miss Test Two",
                "employeePassport": "PP654321",
                "companyWorkerId": "WORK002",
                "photo_url": "https://placehold.co/40x40",
                "nationality": "ลาว",
                "flag_url": "https://placehold.co/20x15"
            }
        ]

        # Construct the HTML content directly with the component logic
        # We need to inject the Alpine component code and the necessary HTML structure.
        # Since we can't serve the actual Laravel app, we build a static representation.

        html_content = """
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="csrf-token" content="test-token">
            <title>Verify Attachment Basket</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body class="p-4">
            <div class="container">
                <h3>Ticket Attachment Verification</h3>

                <!-- Component Initialization -->
                <div class="content-section" x-data="hybridAttachmentManager({
                    is_admin_create_view: true,
                    employerId: null,
                    preselectedEmployeeIds: [101, 102]
                })">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">Employer Selection</div>
                                <div class="card-body">
                                    <select class="form-select" x-model="contextEmployerId" @change="handleEmployerChange($event.target.value)">
                                        <option value="">-- Select Employer --</option>
                                        <option value="999">Employer B (Recipient)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Attachment Basket</div>
                                <div class="card-body">
                                    <h6 class="mb-2">Items (<span x-text="totalItemsCount()"></span>)</h6>
                                    <div class="list-group" id="basket-list">
                                        <template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
                                            <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
                                                    <span>
                                                        <i class="bi bi-person-check me-1 text-primary"></i>
                                                        <span x-text="item.employeeNameTh"></span>
                                                        <span class="text-muted" x-text="item.employeeNameEn ? '(' + item.employeeNameEn + ')' : ''"></span>
                                                    </span>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('existing_employees', index, item.employeeNameTh)">ลบ</button>
                                            </div>
                                        </template>
                                        <template x-if="basket.existing_employees.length === 0">
                                            <div class="text-muted text-center">No items</div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Debug Info -->
                    <div class="mt-3 alert alert-info">
                        <strong>Debug State:</strong>
                        <pre x-text="JSON.stringify(basket.existing_employees, null, 2)"></pre>
                    </div>

                </div>
            </div>

            <!-- Inject the Script Logic -->
            <script>
            function hybridAttachmentManager(config = {}) {
                return {
                    basket: { existing_employees: [], new_employees: [], files: [] },
                    contextEmployerId: null,
                    isContextAdminCreate: false,
                    selectedEmployeeIds: [],
                    isLoading: false,
                    availableEmployees: [], // Mock available list for employer B

                    init() {
                        this.contextEmployerId = config.employerId || null;
                        this.isContextAdminCreate = config.is_admin_create_view || false;

                        console.log('Init called with config:', config);

                        if (config.preselectedEmployeeIds && config.preselectedEmployeeIds.length > 0) {
                            this.selectedEmployeeIds = config.preselectedEmployeeIds;
                            this.fetchPreselectedEmployees();
                        }
                    },

                    totalItemsCount() {
                        return this.basket.existing_employees.length;
                    },

                    async fetchPreselectedEmployees() {
                        this.isLoading = true;
                        try {
                            console.log('Fetching preselected IDs:', this.selectedEmployeeIds);

                            // SIMULATE API CALL
                            // In real app: fetch(`route...?ids[]=...`)
                            // Here we just resolve with mock data matching IDs

                            const mockData = %s;

                            // Filter mock data to match selected IDs
                            const employees = mockData.filter(e => this.selectedEmployeeIds.includes(e.id));

                            console.log('API returned:', employees);

                            employees.forEach(emp => {
                                if (!this.basket.existing_employees.some(e => e.id == emp.id)) {
                                    this.basket.existing_employees.push(emp);
                                }
                            });

                            this.selectedEmployeeIds = [];

                        } catch (error) {
                            console.error('Failed to fetch:', error);
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    handleEmployerChange(val) {
                        this.contextEmployerId = val;
                        // Verify that we DO NOT clear basket
                        console.log('Employer changed to:', val);
                        this.availableEmployees = []; // Clear dropdown list
                        // We intentionally do NOT clear basket.existing_employees
                    },

                    removeConfirm(type, index, name) {
                        this.basket[type].splice(index, 1);
                    }
                }
            }
            </script>
        </body>
        </html>
        """ % json.dumps(mock_employees)

        # Route interception to bypass any actual network calls if the script tries them
        # But here we embedded the mock logic in the JS itself for simplicity in static context.

        page.set_content(html_content)

        # Wait for Alpine to initialize and "fetch" data
        page.wait_for_timeout(1000)

        # Assertions
        # 1. Check that basket contains the 2 pre-selected employees
        expect(page.locator("#basket-list")).to_contain_text("นาย ทดสอบ หนึ่ง")
        expect(page.locator("#basket-list")).to_contain_text("นางสาว ทดสอบ สอง")

        # 2. Verify state persistence after employer selection
        # Select Employer B
        page.select_option("select", "999")
        page.wait_for_timeout(500)

        # Check that employees are STILL in the basket
        expect(page.locator("#basket-list")).to_contain_text("นาย ทดสอบ หนึ่ง")

        # Take screenshot
        page.screenshot(path="verification/basket_verification.png")

        browser.close()

if __name__ == "__main__":
    verify_attachment_basket()
