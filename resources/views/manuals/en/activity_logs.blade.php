{{-- User Manual: Activity Logs (English) --}}

<h4><i class="bi bi-journal-text me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Activity Logs"</strong> menu records <strong>every change</strong>
    that happens in the system — who did what, and when. Used for <strong>audit + verification</strong>,
    e.g. who deleted this employer, who changed a price, who changed an employee's status
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access it?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — can access</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Filters</strong> — by date, user, activity type (create / update / delete)</li>
    <li><strong>History table</strong> — date/time, user, action, item changed</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>Find out who changed what</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Select a date range (from-to)</li>
        <li>Select a user (if needed)</li>
        <li>Select an activity type</li>
        <li>Click "Search" — the table shows only matching entries</li>
    </ol>
</div>

<h5>Search by name or any number</h5>
<div class="manual-step">
    The search box on the first page and on the day page finds <strong>names</strong> and <strong>every ID number</strong> of employees/employers:
    RA number (outsource), passport, work permit, pink card, request number, ID number, reference number, social security number, tax ID
    — including the entries from the day that number was <strong>typed in or changed</strong>.
</div>

<h5>Reading the changes</h5>
<div class="manual-step">
    <ul class="mb-0">
        <li>"Description" is a plain sentence, e.g. <em>Edited employee Somchai (ID 128) — 2 changes</em></li>
        <li>"Changes" shows the first 3 right away, e.g. <em>Passport No.: from A123 to B456</em>, <em>File attached to slot 1. Passport</em>, <em>File moved from slot 2. Visa to Other document 1</em> — press "View all" for the full list</li>
        <li>File names open the file (if it still exists) · passwords are never shown, only "password changed"</li>
        <li>Logged for employees, employers, agents, importers, delegates and user accounts</li>
    </ul>
</div>

<h5>Log-in / log-out tracking</h5>
<div class="manual-step">
    <ul class="mb-0">
        <li><strong>Logged in</strong> and <strong>failed log-in</strong> (wrong password — shows the email used; the password is never stored)</li>
        <li><strong>Logged out</strong> (pressed Logout) or <strong>signed out automatically</strong> (browser/app closed or the program not open for more than 5 minutes)</li>
        <li>"IP / Device" shows the browser and device, e.g. <em>Chrome · Windows</em>, <em>Safari · iPhone</em></li>
    </ul>
</div>

<h5>Data or a file is missing — changed by a person, or a system fault?</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Type the employee's or employer's name in the search box and open <strong>that record's history</strong> — in time order, every change shows <strong>the field, old value → new value, date-time and who changed it</strong></li>
        <li><em>"Cleared …"</em>, <em>"Removed the file from …"</em> or <em>"Changed the file in …"</em> = a person changed it (the name is shown) · <strong>system</strong> entries (user = System), e.g. "automatic update from the resolution settings" or "attachment move tool", are recorded too</li>
        <li>If a file was attached (a "File attached in …" entry) with no remove/replace after it, but the preview shows the red <strong>"File missing from the server"</strong> badge = a system / server problem, not a file never attached — tell the system administrator</li>
        <li>Since 30/10/2026 replaced or removed files are <strong>kept for 30 days</strong>, then deleted for good — Super Admin (and admins a Super Admin ticked "View replaced files" for) can open them from "View old file" in the history or the "Replaced files" button in the preview</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Audit is your evidence:</strong> the Activity Log can be used as legal evidence — the system never auto-deletes it
</div>
<div class="manual-tip">
    <strong>Shares:</strong> whenever someone drags an employee/employer card into another app (e.g. LINE), copies it as text, copies/saves the card image or shares it, an entry of type "share" records who sent whose data, and how
</div>
