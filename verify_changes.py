
import re

def verify_file_content(filepath, patterns):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        all_passed = True
        for pattern in patterns:
            if not re.search(pattern, content, re.DOTALL):
                print(f"❌ Pattern not found in {filepath}: {pattern}")
                all_passed = False
            else:
                print(f"✅ Pattern found in {filepath}: {pattern}")
        return all_passed
    except FileNotFoundError:
        print(f"❌ File not found: {filepath}")
        return False

def run_verification():
    checks = [
        (
            "resources/views/layouts/app.blade.php",
            [
                r"route\('production\.index'\).+P Production",
                r"route\('production\.index'\).+P Workflow"
            ]
        ),
        (
            "app/Http/Controllers/ProductionController.php",
            [
                r"public function create\(Request \$request\)",
                r"\$employerId = \$request->query\('employer_id'\);",
                r"compact\('employerId', 'ticketId'\)"
            ]
        ),
        (
            "resources/views/production/create.blade.php",
            [
                r"select\('id', 'employerNameTh', 'employerNameEn', 'employerId'\)",
                r"isset\(\$employerId\) && \$employerId == \$emp->id"
            ]
        ),
        (
            "resources/views/admin/tickets/show.blade.php",
            [
                r"href=\"{{ route\('production\.create', \['ticket_id' => \$ticket->id, 'employer_id' => optional\(\$ticket->employerUser->employer\)->id\]\) }}\""
            ]
        ),
        (
            "resources/views/employers/edit.blade.php",
            [
                r"id=\"employer-bulk-send-production-btn\".+Send to P Production",
                r"window\.location\.href = '{{ route\(\"production\.create\"\) }}\?employer_id=' \+ employerId;"
            ]
        )
    ]

    success = True
    for filepath, patterns in checks:
        if not verify_file_content(filepath, patterns):
            success = False

    if success:
        print("\n🎉 All verification checks passed!")
    else:
        print("\n⚠️ Some verification checks failed.")

if __name__ == "__main__":
    run_verification()
