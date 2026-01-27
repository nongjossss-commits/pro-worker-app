import re
from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Read Blade Files
    with open('resources/views/workflow/partials/create_modal.blade.php', 'r') as f:
        create_modal = f.read()

    with open('resources/views/workflow/partials/add_employee_modal.blade.php', 'r') as f:
        add_employee_modal = f.read()

    # Simple Blade Cleaning
    # Create Modal Cleaning
    create_html = create_modal.replace('@csrf', '')
    create_html = re.sub(r"\{\{\s*route\('[^']+'\)\s*\}\}", '#', create_html)
    create_html = re.sub(r"\{\{\s*__\('([^']+)'\)\s*\}\}", r'\1', create_html)
    # Replace loops with dummy content
    create_html = re.sub(r'@foreach\(\$tabs as \$tab\)', '<option value="1">WorkType</option>', create_html)
    create_html = re.sub(r'@foreach\(.*?Employer.*?as \$emp\)', '<option value="1">Employer Name</option>', create_html)
    create_html = re.sub(r'@endforeach', '', create_html)
    # Remove remaining blade tags (variables)
    create_html = re.sub(r'\{\{.*?\}\}', '', create_html)

    # Add Employee Modal Cleaning
    add_emp_html = add_employee_modal.replace('@csrf', '')
    add_emp_html = re.sub(r"\{\{\s*route\('[^']+'\)\s*\}\}", '#', add_emp_html)
    add_emp_html = re.sub(r"\{\{\s*__\('([^']+)'\)\s*\}\}", r'\1', add_emp_html)
    add_emp_html = re.sub(r'\{\{.*?\}\}', '', add_emp_html)

    # Full HTML
    html_content = f"""
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {{ padding: 20px; background: #f0f0f0; }}
            /* Force Modals to be visible static blocks */
            .modal {{
                display: block !important;
                position: relative !important;
                z-index: 1 !important;
                opacity: 1 !important;
                background: white;
                margin-bottom: 20px;
            }}
            .modal-dialog {{ margin: 0; max-width: 100%; }}
            .modal-backdrop {{ display: none !important; }}
            .d-none {{ display: none !important; }}

            /* Highlight the search box for visibility */
            #global-search-input {{ border: 2px solid red; }}
        </style>
    </head>
    <body>
        <h2>Create Modal (No Project Name)</h2>
        <div style="border: 2px dashed blue; padding: 10px;">
            {create_html}
        </div>

        <h2>Add Employee Modal (Global Search)</h2>
        <div style="border: 2px dashed green; padding: 10px;">
            {add_emp_html}
        </div>

        <!-- No Bootstrap JS to avoid interference -->
        <script>
            // Manually force the Global Search section to show
            document.addEventListener('DOMContentLoaded', function() {{
                // Hide others
                const others = document.querySelectorAll('.section-mode');
                others.forEach(el => el.classList.add('d-none'));

                // Show Global
                const globalSearch = document.getElementById('section-global-search');
                if(globalSearch) {{
                    globalSearch.classList.remove('d-none');
                    globalSearch.style.display = 'block'; // Inline override just in case
                }} else {{
                    document.body.insertAdjacentHTML('beforeend', '<h3 style="color:red">Global Search Section NOT FOUND</h3>');
                }}
            }});
        </script>
    </body>
    </html>
    """

    page.set_content(html_content)
    page.wait_for_timeout(500)
    page.screenshot(path="verification/verification.png", full_page=True)

with sync_playwright() as playwright:
    run(playwright)
