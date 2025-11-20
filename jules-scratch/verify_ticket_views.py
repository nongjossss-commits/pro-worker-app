from playwright.sync_api import sync_playwright
import json

def verify_ticket_views():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # --- 1. Verify Ticket Create View ---

        # Use double curly braces {{ }} for literal braces in f-strings
        mock_basket_templates = """
            <!-- 1. Template for Existing Employees -->
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
            <!-- 2. Template for New Employees -->
            <template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-person-plus fs-4 text-success"></i>
                        <span>
                            ใหม่: <span x-text="item.employeeNameTh"></span>
                            <small class="text-muted d-block" x-text="'Passport: ' + (item.employeePassport || 'N/A')"></small>
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('new_employees', index, item.employeeNameTh)">ลบ</button>
                </div>
            </template>
            <!-- 3. Template for General Files -->
            <template x-for="(item, index) in basket.files" :key="'f-' + index">
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-text fs-4 text-secondary"></i>
                        <span>
                            <a :href="item.url" target="_blank" x-text="item.name" class="text-decoration-none"></a>
                            <small class="text-muted d-block" x-text="item.size"></small>
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('files', index, item.name)">ลบ</button>
                </div>
            </template>
        """

        html_create = f"""
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Create View</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        </head>
        <body class="bg-light p-4">
            <div class="container" x-data="mockHybridAttachmentManager()">
                <h2 class="mb-4">สร้างคำขอใหม่ (Smart Ticket)</h2>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="card mb-4">
                            <div class="card-header">รายละเอียดคำขอ</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">หัวเรื่อง <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="Test Subject">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ข้อความ/รายละเอียดเพิ่มเติม (ถ้ามี)</label>
                                    <textarea class="form-control" rows="8"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card mb-4">
                            <div class="card-header">สิ่งที่แนบมา (Attachment Basket)</div>
                            <div class="card-body">
                                <div class="d-grid gap-2 mb-3">
                                    <button type="button" class="btn btn-outline-primary" @click="mockAddExisting">
                                        <i class="bi bi-person-check me-2"></i> แนบลูกจ้างที่มีอยู่
                                    </button>
                                    <button type="button" class="btn btn-outline-success" @click="mockAddNew">
                                        <i class="bi bi-person-plus me-2"></i> แนบลูกจ้างใหม่/แจ้งเข้า
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" @click="mockAddFile">
                                        <i class="bi bi-file-earmark-arrow-up me-2"></i> แนบไฟล์/รูปภาพ
                                    </button>
                                </div>
                                <hr>
                                <h6 class="mb-2">รายการที่แนบ (<span x-text="totalItemsCount()"></span> รายการ)</h6>
                                <div class="list-group">
                                    {mock_basket_templates}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function mockHybridAttachmentManager() {{
                    return {{
                        basket: {{
                            existing_employees: [],
                            new_employees: [],
                            files: []
                        }},
                        totalItemsCount() {{
                            return this.basket.existing_employees.length +
                                   this.basket.new_employees.length +
                                   this.basket.files.length;
                        }},
                        mockAddExisting() {{
                            this.basket.existing_employees.push({{
                                id: Date.now(),
                                employeeNameTh: 'นายสมชาย ใจดี',
                                employeeNameEn: 'Somchai Jaidee',
                                photo_url: 'https://via.placeholder.com/35'
                            }});
                        }},
                        mockAddNew() {{
                            this.basket.new_employees.push({{
                                employeeNameTh: 'นายมาใหม่ ไฟแรง',
                                employeePassport: 'AA1234567'
                            }});
                        }},
                        mockAddFile() {{
                            this.basket.files.push({{
                                name: 'document.pdf',
                                url: '#',
                                size: '1.5 MB',
                                path: 'temp/document.pdf'
                            }});
                        }},
                        removeConfirm(type, index, name) {{
                            if(confirm('ลบ ' + name + '?')) {{
                                this.basket[type].splice(index, 1);
                            }}
                        }}
                    }}
                }}
            </script>
        </body>
        </html>
        """

        page.set_content(html_create)
        # Interact to show functionality
        page.get_by_role("button", name="แนบลูกจ้างที่มีอยู่").click()
        page.get_by_role("button", name="แนบลูกจ้างใหม่/แจ้งเข้า").click()
        page.get_by_role("button", name="แนบไฟล์/รูปภาพ").click()

        page.screenshot(path="jules-scratch/verify_create.png", full_page=True)


        # --- 2. Verify Ticket Show View ---
        # Mock content based on resources/views/tickets/show.blade.php
        # Focusing on the "Attachments Triage" section

        html_show = """
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Show View</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        </head>
        <body class="bg-light p-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>แจ้งเข้าพนักงานใหม่</h2>
                    <span class="badge bg-warning fs-5">Pending Staff</span>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4 border-info">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>สิ่งที่แนบมา (Attachments Triage)</h5>
                            </div>
                            <div class="card-body">
                                <!-- Existing Employees -->
                                <h6 class="text-primary mt-3">ลูกจ้างที่มีอยู่ (1 คน)</h6>
                                <div class="list-group mb-3">
                                    <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                        <img src="https://via.placeholder.com/40" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        <span class="flex-grow-1">
                                            <strong>นายสมชาย ใจดี</strong>
                                            <small class="text-muted">(P123456)</small>
                                        </span>
                                        <div class="ms-auto btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-info btn-preview"><i class="bi bi-search"></i></button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <!-- New Employees -->
                                <h6 class="text-success mt-3">ลูกจ้างใหม่/แจ้งเข้า (1 คน)</h6>
                                <div class="list-group mb-3">
                                    <div class="list-group-item py-2 d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <strong>นายมาใหม่ ไฟแรง</strong>
                                            <span class="text-muted">/ Mr. New Comer</span>
                                            <small class="d-block text-muted">Passport: N9876543</small>
                                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                                <a href="#" class="btn btn-sm btn-outline-info">รูปถ่าย</a>
                                                <a href="#" class="btn btn-sm btn-outline-info">Passport</a>
                                                <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-arrow-down"></i> ทะเบียนบ้าน</a>
                                            </div>
                                        </div>
                                        <div class="ms-auto btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-info"><i class="bi bi-search"></i></button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <!-- General Files -->
                                <h6 class="text-secondary mt-3">ไฟล์แนบทั่วไป (1 ไฟล์)</h6>
                                <div class="list-group mb-3">
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                                        <a href="#" class="text-decoration-none text-body flex-grow-1">
                                            <span><i class="bi bi-file-earmark-text me-2"></i> document.pdf</span>
                                        </a>
                                        <small class="text-muted me-3">1.5 MB</small>
                                        <button type="button" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">ข้อมูลตั๋วงาน</div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Ticket ID: #65</li>
                                <li class="list-group-item">Employer Test</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        """
        page.set_content(html_show)
        page.screenshot(path="jules-scratch/verify_show.png", full_page=True)

        browser.close()

verify_ticket_views()
