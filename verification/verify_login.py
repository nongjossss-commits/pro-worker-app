import re
import os
from playwright.sync_api import sync_playwright

def mock_blade(content):
    # Mock {{ __('key') }} -> key
    content = re.sub(r"\{\{\s*__\(['\"](.*?)['\"]\)\s*\}\}", r"\1", content)
    # Mock {{ __('key', [...]) }} -> key (ignoring params for now)
    content = re.sub(r"\{\{\s*__\(['\"](.*?)['\"],.*?\)\s*\}\}", r"\1", content)

    # Mock route()
    content = re.sub(r"\{\{\s*route\(.*?\)\s*\}\}", "#", content)

    # Mock old()
    content = re.sub(r"\{\{\s*old\(.*?\)\s*\}\}", "", content)

    # Mock csrf
    content = re.sub(r"@csrf", '<input type="hidden" name="_token" value="mock">', content)

    # Mock locale
    content = re.sub(r"\{\{\s*str_replace\('_', '-', app\(\)->getLocale\(\)\)\s*\}\}", "en", content)

    # Remove x-components (simplified)
    content = re.sub(r"<x-auth-session-status.*?/>", "", content)
    content = re.sub(r"<x-input-error.*?/>", "", content)

    # Remove blade ifs (very basic)
    content = re.sub(r"@if\s*\(.*?\)", "", content)
    content = re.sub(r"@endif", "", content)
    content = re.sub(r"@section\(.*?\)", "", content)
    content = re.sub(r"@extends\(.*?\)", "", content)
    content = re.sub(r"@push\(.*?\).*?@endpush", "", content, flags=re.DOTALL)

    # Fix relative paths for css/js to absolute or CDN if possible, or just leave as is if using CDN
    # The login page uses CDN for tailwind, so it should be fine.

    return content

def verify_login_page():
    # Read the blade file
    with open('resources/views/auth/login.blade.php', 'r') as f:
        blade_content = f.read()

    html_content = mock_blade(blade_content)

    # Save as temporary HTML
    output_path = os.path.abspath('verification/login_mock.html')
    with open(output_path, 'w') as f:
        f.write(html_content)

    print(f"Created mock HTML at {output_path}")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local file
        page.goto(f"file://{output_path}")

        # Take screenshot
        screenshot_path = os.path.abspath("verification/login_page.png")
        page.screenshot(path=screenshot_path, full_page=True)
        print(f"Screenshot saved to {screenshot_path}")

        browser.close()

if __name__ == "__main__":
    verify_login_page()
