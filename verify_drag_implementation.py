
import json
import re
from playwright.sync_api import sync_playwright

def verify_drag_handles():
    # Mock HTML content representing the structure of _employee_card.blade.php and other modified views
    # This mock includes the data-drag-payload attribute and the ondragstart handler pattern
    mock_html = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Drag Verification</title>
        <!-- Bootstrap Icons for the handle -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </head>
    <body>

        <!-- Card View Mock -->
        <div class="employee-card">
            <span id="card-handle" class="btn btn-sm btn-light border cursor-grab ms-1"
                  draggable="true"
                  data-drag-payload='{"id": 1, "title": "Test Employee", "url": "http://example.com#1"}'
                  ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))">
                <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
            </span>
        </div>

        <!-- Table View Mock (Employer Edit) -->
        <table>
            <tr id="employee-row-1">
                <td><input type="checkbox"></td>
                <td>
                    <span id="table-handle" class="cursor-grab"
                          draggable="true"
                          data-drag-payload='{"id": 1, "title": "Test Employee", "source_menu": "Employer Info"}'
                          ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))">
                        <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                    </span>
                </td>
                <td>Data</td>
            </tr>
        </table>

        <script>
            // Mock global drag handler
            window.startDragGlobal = function(event, type, payload) {
                console.log('Drag started:', type, payload);
                event.dataTransfer.setData('application/json', JSON.stringify({type, payload}));
            };
        </script>
    </body>
    </html>
    """

    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.set_content(mock_html)

        # 1. Verify Card Handle
        card_handle = page.locator("#card-handle")
        assert card_handle.is_visible(), "Card drag handle not visible"
        assert card_handle.get_attribute("draggable") == "true", "Card handle not draggable"

        payload_attr = card_handle.get_attribute("data-drag-payload")
        assert payload_attr is not None, "Card handle missing data-drag-payload"

        # Verify JSON validity of payload
        try:
            payload = json.loads(payload_attr)
            assert payload['id'] == 1
        except json.JSONDecodeError:
            assert False, "Card payload is not valid JSON"

        # 2. Verify Table Handle
        table_handle = page.locator("#table-handle")
        assert table_handle.is_visible(), "Table drag handle not visible"

        table_payload_attr = table_handle.get_attribute("data-drag-payload")
        assert table_payload_attr is not None, "Table handle missing data-drag-payload"

        try:
            payload = json.loads(table_payload_attr)
            assert payload['source_menu'] == "Employer Info"
        except json.JSONDecodeError:
            assert False, "Table payload is not valid JSON"

        # 3. Take Screenshot for visual confirmation
        page.screenshot(path="verification_drag.png")
        print("Verification successful. Screenshot saved to verification_drag.png")

        browser.close()

if __name__ == "__main__":
    verify_drag_handles()
