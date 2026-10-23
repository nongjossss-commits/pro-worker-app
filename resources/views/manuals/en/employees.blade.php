{{--
    User Manual: Employees (English)
--}}

<h4><i class="bi bi-people-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Employees"</strong> menu is the database of foreign workers (Myanmar/Laos/Cambodia) managed by the office.
    It stores basic information (full name, nationality, passport number, visa, work permit, MOU contract)
    and links to the <strong>employer</strong> the employee is working for.
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — full access, including terminate/restore/permanent delete</li>
    <li><span class="manual-role">Staff</span> — can view / add / edit, cannot permanently delete</li>
    <li><span class="manual-role">Caretaker</span> — can view / add / edit + terminate employees</li>
    <li><span class="manual-role">Employer</span> — can only see their own employees</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Filter bar</strong> at the top — filter by nationality, visa status, employer, search by name/passport number · the <strong>nationality box</strong> lists every nationality in the world (4 languages) — type part of a name to narrow the choices (Thai/English/Myanmar/Chinese all work, e.g. "camb", "japan") or press the ▾ arrow to scroll the full list · clicking the empty box shows "Recently selected" (the last 4 this browser picked), then the main nationalities (Myanmar, Lao, Cambodian, Vietnamese) · the <strong>expiry date box</strong> (end of the filter bar) finds employees whose work permit <em>or</em> visa expires on the chosen date</li>
    <li><strong>Search box</strong> — type part of an employee or employer name and matching suggestions appear instantly (no need to type the full name); click a suggestion to filter the list right away · <strong>clicking an employee suggestion shows only that employee</strong>, even if others (at other employers) share the name · pressing Enter without picking shows everyone matching the typed text · search results include notified-out / registration-cancelled employees too (the same people the suggestions offer) · searches name (Thai / English), passport no., work permit no., ID no., RA no., pink card no. and <strong>request no.</strong> — including the request no. on Workflow / Pre-Production work cards and on registration / renewal resolution cards · the suggestions shown while typing use the same rules as pressing Search</li>
    <li><strong>Two search boxes</strong> — box 1 takes an employee (name / passport / any number), an employer or a job owner; pick from the list: an employee = only that person, an employer = only that employer, a job owner = every employer under them (typing and pressing Enter searches the typed text) · box 2 opens once box 1 has a value and narrows box 1's result further, with the same kinds of text</li>
    <li><strong>Status tabs</strong> — Active / Terminated / Contract Ended / Trash</li>
    <li><strong>Filter by Address</strong> — choose province → district → sub-district from the employers' addresses (only addresses present in the current list are offered) · changing the province clears the old district/sub-district · searching or using other filters keeps the address filter · in English (or another non-Thai language) place names show in English when an English name was recorded · the same filter works in Employers, Incomplete Data, Notifications, Workflow, Pre-Production, Registration/Renewal Resolutions and Groups/Teams</li>
    <li><strong>Employee cards</strong> — shown as a grid of same-height tiles that <strong>fill each row edge to edge</strong> with no empty strip on the right · the tile colour shows the work in progress (purple = registration resolution, pink = renewal resolution, yellow = Workflow, cyan = Pre-Production, grey = none) as a top colour bar, a glow behind the photo and a matching border · an employee in several kinds of work at once (e.g. changing employer in Workflow AND in Renewal resolution) gets <strong>every colour, side by side</strong> — not blended: the top bar is split into sections, the photo ring is split, and the left / right glow use different colours · hovering lifts the tile with a coloured shadow and a light sweep (light and dark mode) (a bigger screen or zooming out adds a tile as soon as one more fits — each gets a little narrower; zooming in shows fewer, wider tiles; one on phones · a short list of 1–2 keeps normal-size tiles instead of stretching across the row); long text: the employer name is shortened with … but its magnifier stays, detail lines wrap to 2 lines (hover for the full text) · each tile has the photo (flag at the card's left edge, nationality right of the photo in the selected language), the name with the 🔍 preview button right after it, the employer, up to 6 detail lines (by default passport no., passport expiry, work permit type and 90-day report — Super Admin can change them under Super Admin Settings → "Employee Card Details": up to 6, in any order), a box with any work in progress (room for 3 status labels; long text is shortened with … — hover for the full text; the tile never grows), and the buttons at the bottom · the select tick box is in the top-right corner · choose 25 / 50 / 100 per page with the buttons on the right — the last choice is remembered in that browser (it stays after refreshing or reopening; each menu remembers its own; first time = 100) · the same layout is used in Employment History, the employer page's Employees tab and Groups &amp; Teams (Workflow / Pre-Production / Registration / Renewal resolution keep their cards but show this same set of details) · every detail has a <strong>copy button</strong> <i class="bi bi-copy"></i> — one click copies it, no text selecting</li>
    <li><strong>"+ Add New Employee"</strong> and <strong>"Import from Excel"</strong> buttons in the top-right corner</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>Common tasks</h4>

<h5>1. Add an employee one at a time</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click <strong>"+ Add New Employee"</strong> in the top-right corner</li>
        <li>Fill in the basic info — full name (Thai/English), gender, nationality (type to search or pick from the full list — same as the filter), date of birth</li>
        <li>Fill in the <strong>passport</strong> number + expiry date</li>
        <li>Select the <strong>employer</strong> this employee works for</li>
        <li>Upload a photo + documents (passport copy, visa, work permit) as available</li>
        <li>Click <strong>"Save"</strong></li>
    </ol>
</div>

<div class="manual-tip">
    💡 <strong>The add / edit / preview employee pages are split into tabs</strong> — no scrolling, just click the tab you need:
    <strong>Personal Information</strong> (photo, names, date of birth, nationality, phone + login information) · <strong>Passport, Visa &amp; Work Permit</strong> (passport + passport type for Myanmar / Cambodian workers, visa, visa expiry · work permit in this order: permit type → work permit no. → ID number → pink card no. → issue / expiry date, 90-day report, RA number, position, job description) ·
    <strong>Employment &amp; Insurance</strong> (start date / work age, request no., tax ID, worker code, department, bank · insurance) · <strong>Attached Files</strong> (the number = files attached)
    · <strong>each box is sized for its content</strong> (age / height small, dates short, names / job description long) and boxes flow in order across the full width (the next box moves up onto the row while it fits — no empty gaps) — a big screen or zooming out fits more per row, zooming in fewer, boxes never stretch · the photo sits top-right of the personal tab and every section moves up beside it (no empty band)
    <ul class="mb-0 mt-2">
        <li>At the top is a <strong>profile header</strong> — photo, name, employer, date of birth (age), nationality, passport number and expiry badges for passport / visa / work permit / 90-day report:
            <span class="text-danger">red = expired</span> · <span class="text-warning">yellow = expiring soon (30 days; 90-day report 15 days)</span> · <span class="text-success">green = valid</span> · grey = not filled in</li>
        <li>The <strong>Save</strong> button stays at the bottom of the screen on every tab — it saves all tabs at once</li>
        <li>If a field is missing or wrong, its tab opens automatically and shows a <strong>red dot</strong> · fields still missing (yellow icon) are counted on the tab</li>
        <li>On a phone, swipe the tab bar left/right</li>
        <li>The <strong>Attached Files</strong> tab is split into <strong>Main Documents</strong> (1–8) and <strong>Other Documents</strong> (9–18) with an "Attached x/y" counter — each slot is a small card: green edge + "Attached" = has a file (👁 view · PDF · ✏️ edit) · <strong>Choose file / Replace file</strong> and 📷 scan/take photo · after picking a new file the card turns blue and shows its name (uploaded when you press Save) · <strong>Other documents 9–18</strong>: the slot title is the document name (default = what Super Admin set under Settings → Attachment Files Settings); press ✏️ next to it to rename it for this employee only (Enter = OK, Esc = cancel) — advanced download / export / edit use the central names Super Admin set</li>
        <li><strong>Preview</strong> (🔍 button): 2 tabs — <strong>Employee Data</strong> shows everything on one page — the personal details (employer, English / Thai name, date of birth, age, gender, nationality, plus height, weight and parents when recorded) are in the <strong>profile header next to the photo</strong>, each with a copy button, and are not repeated below; then (contact &amp; login · passport · visa · work permit · employment · insurance) and <strong>Attached Files</strong> · the window is wide and almost full height, and fields flow to fill each row by window width / zoom (2 columns on phones) · frequently used fields (passport no., passport expiry, visa endorsement date / no., visa expiry, work permit no., ID number, pink card no., work permit expiry, 90-day report) are <strong>highlighted</strong> · every filled value has a <strong>copy button</strong> ⧉ after it — one click copies the value as shown, ready to paste (the button turns into ✓ and "Copied!" appears); empty fields have none · the Attached Files tab shows small boxes — with a file = green + "View"/"PDF", no file = faded "No Document" · the profile header and tab bar stay in place; long content scrolls inside the frame · the employer preview works the same way</li>
        <li>The preview has an <strong>"Edit"</strong> button at the top right (for users allowed to edit) — it opens the edit page; after saving or cancelling you return to the same page and the preview reopens with the latest data</li>
    </ul>
</div>

<h5>2. Import employees in bulk (Excel Bulk Import)</h5>
<div class="manual-step">
    Best for employers bringing in many employees at once
    <ol class="mb-0 mt-2">
        <li>Click <strong>"Import from Excel"</strong></li>
        <li>First download the <strong>"Template"</strong> file — the sample has the column headers the system requires</li>
        <li>Fill in employee data in Excel following the column headers (1 person per row)</li>
        <li>Upload the file back into the system</li>
        <li>Check the <strong>preview</strong> before confirming — the system shows the records to be added and warns about any errors</li>
        <li>Click <strong>"Confirm"</strong> — the system adds all of them at once</li>
    </ol>
</div>

<h5>3. Terminate / end contract</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the card of the employee to be terminated</li>
        <li>Click <strong>"Terminate / End Contract"</strong></li>
        <li>Select a reason + the termination date</li>
        <li>Click <strong>"Confirm"</strong> — the status changes to "Terminated" and no longer counts toward the active quota</li>
    </ol>
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>Tip:</strong>
        A terminated employee isn't gone — they stay under the "Terminated" tab and can be restored if re-hired
    </div>
</div>

<h5>4. Restore a terminated employee (Reinstate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click the <strong>"Terminated"</strong> status tab at the top</li>
        <li>Find the employee you want to restore</li>
        <li>Click <strong>"Reinstate"</strong></li>
    </ol>
</div>

<h5>5. Permanently delete an employee (Force Delete)</h5>
<div class="manual-warn">
    <strong>Admin/Super Admin only</strong> — used for employees entered by mistake or duplicated; once deleted it <strong>cannot be undone</strong>
    <br>
    Steps: go to the "Trash" tab → click the permanent delete icon → confirm twice
</div>

<h5>6. Duplicate employee check</h5>
<div class="manual-step">
    Every way of creating an employee (this menu, Workflow / Pre-Production, Registration / Renewal, Excel import, Sales transition) compares passport / work permit / pink card / ID number / RA number with existing employees and warns before saving (warning, not a block). Excel import and Sales transition ask you to tick "continue anyway".
</div>

<h5>7. Search finds every status</h5>
<div class="manual-step">
    The default list shows active employees only, but a search also finds notified-out / registration-cancelled employees, marked with a <strong>Terminated</strong> / <strong>Registration Cancelled</strong> badge — useful when checking a duplicate warning.
</div>

<h5>8. Move attachment files for many employees (Super Admin)</h5>
<div class="manual-step">
    Tick employees → <strong>Move Attachment Files</strong> → choose source/target slots and mode: swap / move & delete source / merge into B (combines both PDFs). Applies only to the selected employees; the Super Admin can delegate this to specific Admin/Staff users.
</div>

<h5>9. Work permit types</h5>
<div class="manual-step">
    The "work permit type" list is managed by the Super Admin in Settings (add / rename / reorder). Renaming updates existing employees automatically; a type still in use cannot be deleted; "Other" with free text is always available.
</div>

<h5>10. Editing the employee photo (crop window)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li><strong>Rotate / adjust angle</strong> — the empty corners after rotating are filled with the chosen background colour (white by default), not grey/black</li>
        <li><strong>Auto Face Center Crop</strong> — frames the photo ID-style with the face centred; works in every browser (the first use may take a few seconds)</li>
        <li><strong>White BG / Light Blue BG</strong> — the background is removed and filled automatically</li>
        <li>If the cut is wrong (e.g. an arm or shoulder is missing), press <strong>Refine / Edit Mask</strong>:
            <ul>
                <li><strong>Ghost</strong> — shows the original photo faintly behind, so you can see what was cut away</li>
                <li><strong>Restore</strong> — paint over the ghost to bring wrongly removed parts back · <strong>Erase</strong> — remove leftovers</li>
                <li><strong>Smart Edge</strong> (on by default) — put the centre of the brush on the area to erase/restore; the brush edge may overlap, it stops at the outline by itself</li>
                <li><strong>Magic Wand</strong> — one click erases a whole area of similar colour</li>
                <li>Mouse wheel = zoom · hold Space or right-drag = move · <kbd>[</kbd> <kbd>]</kbd> = brush size · <kbd>Ctrl</kbd>+<kbd>Z</kbd> = undo</li>
            </ul>
        </li>
        <li>Press <strong>Apply Changes</strong> — the photo gets the chosen background colour straight away (no need to press the background button again)</li>
    </ol>
</div>

<h5>11. Sending an employee / employer to a chat (LINE etc.)</h5>
<div class="manual-step">
    Employee and employer cards have a <i class="bi bi-grid-3x2-gap-fill"></i> button (bottom-right of the card) — in Employees, Employers, Notifications, Pre-Production, Registration/Renewal Resolution, Workflow, the appointment calendars, Incomplete Data and tickets.
    <ol class="mb-0 mt-2">
        <li><strong>Drag</strong> the button into a LINE chat (on a computer) or another app — it arrives as readable text: name, nationality, passport, expiry dates… (dropping it on the in-app chat still gives the usual card)</li>
        <li><strong>Click</strong> the button for a menu:
            <ul>
                <li><strong>Copy as text</strong> — then press <kbd>Ctrl</kbd>+<kbd>V</kbd> in the chat</li>
                <li><strong>Copy as card image</strong> — a brand-coloured card with the photo and data; press <kbd>Ctrl</kbd>+<kbd>V</kbd> in the chat</li>
                <li><strong>Save card image</strong> — a PNG file to forward</li>
                <li><strong>Share…</strong> (phone/tablet) — opens the device share sheet; pick LINE</li>
            </ul>
        </li>
        <li>From Notifications the text includes the reason, e.g. "⚠️ Visa expiry: 28/01/2026 (overdue 242 days)"</li>
        <li><strong>Admins</strong> choose which fields may be sent — click <i class="bi bi-grid-3x2-gap-fill"></i> → <strong>Share settings</strong> (the name is always included)</li>
        <li><strong>Per-menu data:</strong> request no., appointment, team and remarks come from the card being sent — a Workflow / Pre-Production job uses its own, a Registration / Renewal tab uses that tab's, the Employees menu uses the employee record · ticked fields with no value yet show "-"</li>
        <li>Every share is recorded in the <strong>Activity Log</strong> (type "share"): who sent whose data, and how</li>
    </ol>
</div>
<div class="manual-warn">
    Once data is in LINE or another app the program can no longer control it (personal data — PDPA). Send it only to people who need it.
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Things to know / Tips</h4>

<div class="manual-tip">
    <strong>Maximum employee quota:</strong> Super Admin can limit the number of active employees
    (set in Super Admin Settings → Quota Settings) — if the quota is full, you cannot add new employees
</div>

<div class="manual-tip">
    <strong>Employees vs. Delegates:</strong> "Employee Info" in the left menu for some roles
    actually refers to <strong>employer Delegates</strong>, not migrant workers — these are two separate menus
</div>

<div class="manual-tip">
    <strong>Visa/passport nearing expiry:</strong> the system automatically alerts you in <strong>Notifications</strong>
    when something is about to expire — check every morning
</div>
<div class="manual-tip">
    <strong>Capturing / importing documents — changing the page order ("Review documents" screen):</strong>
    <ul class="mb-0 mt-1">
        <li><strong>Just drag:</strong> drag a page with the mouse to where it should go (phone: press and hold, then drag) — it takes that position and the others shift, e.g. drag the first page onto the last one = moved to the end</li>
        <li><strong>Or press "Sort Pages":</strong> tap the pages in the new order (1, 2, 3 …) and press <strong>✓ Confirm</strong> at the top — while sorting, <strong>"Save" is locked</strong> until you confirm or cancel (so nothing is saved with an unconfirmed order)</li>
    </ul>
</div>
<div class="manual-tip">
    <strong>Advanced Export / Advanced Edit / Advanced Download — tick in order:</strong> the order you tick is the order used — a number badge shows on each item (1, 2, 3 …) and the "Order:" row above sums it up
    <ul class="mb-0 mt-1">
        <li><strong>Advanced Export:</strong> Excel columns run left → right in that order (Photo can be anywhere — no longer forced to the first column) · <strong>Advanced Edit:</strong> the edit table's columns follow it · <strong>Advanced Download:</strong> documents follow it, no. 1 first / on top — for "Merged PDF" and "Merged PDF (Individual)"</li>
        <li>Click an item that is already ticked → "Change order no. N?" → OK removes it from that number, <strong>the others keep their numbers</strong>, and the next item you tick becomes no. N (with several gaps, the lowest is filled first)</li>
        <li><strong>"Clear and pick again"</strong> unticks everything and starts again at 1 · every window opens empty (nothing pre-ticked) — just tick from no. 1; closing and reopening starts empty again</li>
    </ul>
</div>
<div class="manual-tip">
    <strong>Advanced download as one PDF / one PDF per person:</strong> PDFs from scanners or phone apps (newer PDF format) are converted automatically before merging · if a file still can't be merged, the Download Center says so, and the per-person option puts the original file in the ZIP — the ZIP option always contains every original file
</div>
<div class="manual-tip">
    <strong>Downloading many employees:</strong> after you press start, the file is built in the background — the Download Center shows progress (e.g. "Processing... 120/500") and starts the download by itself when ready (keep the Download Center open) · if you close it, come back and press Download in the list within 24 hours · a "merge into one PDF" too large for the server's memory is split into several PDFs in a ZIP (same order) instead of failing
</div>

<div class="manual-warn">
    <strong>Before permanently deleting an employee:</strong> make sure no Production / Workflow job or tax invoice
    still references this employee
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>

<dl>
    <dt>Q: I can't add an employee, it shows "quota exceeded"?</dt>
    <dd>A: The system quota is full — contact Super Admin to increase it, or terminate old employees no longer in use</dd>

    <dt>Q: Why does the Excel import show an error?</dt>
    <dd>A: Check that the column headers in your Excel file match the downloaded template exactly — don't rename the columns</dd>

    <dt>Q: An employee moved to a new employer?</dt>
    <dd>A: Edit the employee → change "Employer" to the new one → Save</dd>

    <dt>Q: I only see employees from one company?</dt>
    <dd>A: If you're logged in with the "Employer" role, you'll only see your own employees — you need an Admin/Staff role or higher to see everyone</dd>
</dl>
