from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # The application is not served, so I will construct a static HTML page
    # that mimics the structure of the settings page.
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Notification Settings</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </head>
    <body>
        <div class="container p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">รายการแจ้งเตือน</h2>
                <a href="#" class="btn btn-primary">
                    <i class="bi bi-gear-fill"></i> ตั้งค่าการแจ้งเตือน
                </a>
            </div>
            <h1>ตั้งค่าการแจ้งเตือน</h1>
            <p>กำหนดจำนวนวันล่วงหน้าก่อนถึงวันหมดอายุสำหรับเอกสารประเภทต่างๆ</p>

            <form>
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ประเภทการแจ้งเตือน</th>
                                    <th>แจ้งเตือนล่วงหน้า (วัน)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>รายงานตัว 90 วัน</td>
                                    <td><input type="number" class="form-control" value="90"></td>
                                </tr>
                                <tr>
                                    <td>Passport</td>
                                    <td><input type="number" class="form-control" value="60"></td>
                                </tr>
                                <tr>
                                    <td>ใบอนุญาตทำงาน (MOU)</td>
                                    <td><input type="number" class="form-control" value="60"></td>
                                </tr>
                                <tr>
                                    <td>วีซ่า</td>
                                    <td><input type="number" class="form-control" value="60"></td>
                                </tr>
                                <tr>
                                    <td>ต่ออายุ CI</td>
                                    <td><input type="number" class="form-control" value="60"></td>
                                </tr>
                                <tr>
                                    <td>ต่ออายุมติ</td>
                                    <td><input type="number" class="form-control" value="60"></td>
                                </tr>
                                <tr>
                                    <td>มติขึ้นทะเบียนใหม่</td>
                                    <td><input type="number" class="form-control" value="60"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
                    </div>
                </div>
            </form>
        </div>
    </body>
    </html>
    """

    page.set_content(html_content)
    page.screenshot(path="jules-scratch/notification_settings.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
