from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Create a mock HTML file that simulates the blade template logic
        # We need to simulate the Alpine.js behavior.

        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Import Verification</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        </head>
        <body>
            <div class="container-fluid py-4" x-data="importGrid()">
                <div class="card">
                     <div class="card-body">
                        <table class="table table-bordered">
                            <thead><tr><th>Photo</th><th>Name</th><th>Action</th></tr></thead>
                            <tbody>
                                <template x-for="(row, index) in rows" :key="row.id">
                                    <tr>
                                        <td @click="$refs['file_' + row.id].click()">
                                            <div style="height: 50px; background: #eee;">
                                                <img x-show="row.photoPreview" :src="row.photoPreview" style="height: 50px;">
                                                <span x-show="!row.photoPreview">Upload</span>
                                            </div>
                                            <input type="file" :x-ref="'file_' + row.id" class="d-none" @change="handleFileChange($event, row)">
                                        </td>
                                        <td><input type="text" x-model="row.name_th"></td>
                                        <td><button @click="removeRow(index)">Remove</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <button id="add-btn" @click="addRow()">Add Row</button>
                     </div>
                </div>
            </div>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('importGrid', () => ({
                        rows: [{ id: 1, name_th: '', photoPreview: null }],
                        nextId: 2,
                        addRow() {
                            this.rows.push({ id: this.nextId++, name_th: '', photoPreview: null });
                        },
                        removeRow(index) {
                            this.rows.splice(index, 1);
                        },
                        handleFileChange(event, row) {
                            const file = event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => { row.photoPreview = e.target.result; };
                                reader.readAsDataURL(file);
                            }
                        }
                    }));
                });
            </script>
        </body>
        </html>
        """

        with open("verify_import_ui.html", "w") as f:
            f.write(html_content)

        page = browser.new_page()
        page.goto("file://" + os.path.abspath("verify_import_ui.html"))

        # Verify initial state
        rows = page.locator("tbody tr")
        print(f"Initial rows: {rows.count()}")
        assert rows.count() == 1

        # Click Add Row
        page.click("#add-btn")
        print(f"Rows after add: {rows.count()}")
        assert rows.count() == 2

        # Take screenshot
        page.screenshot(path="verification_import_ui.png")

        browser.close()
        print("Verification UI script ran successfully.")

if __name__ == "__main__":
    run()
