{{-- Training Edition: Employment History (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-badge"></i> {{ __('Employment History') }} — {{ __('Every employee ever, including terminated/contract-ended') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Employment History"</strong> menu shows <strong>every employee</strong> that has ever been in the system,
        whether active, terminated, contract-ended, or already transferred to another employer.
        Used to review past history, find old employees, and <strong>transfer</strong> former employees to a new employer
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (only their own)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Search past employees</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/01-search-filter',
        'alt' => 'Employment History page + filter bar',
        'caption' => 'Employment History — shows every employee, including inactive ones',
        'callouts' => [
            '<strong>Search:</strong> type a name / passport number',
            '<strong>Filter by nationality:</strong> Myanmar / Laos / Cambodia / Vietnam',
            '<strong>Filter by MOU type:</strong> select any group',
            '<strong>Filter by passport:</strong> CI / PJ / TD / International',
            '<strong>Filter by pink card:</strong> has one / doesn\'t',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Employment History</strong></li>
            <li>Type a search or use the filters at the top</li>
            <li>Click "Filter" — results include both active + inactive employees</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Bulk-transfer old employees to a new employer</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/02-bulk-transfer',
        'alt' => 'Bulk Action bar + employer-transfer modal',
        'caption' => 'Bulk Transfer — move multiple employees to a new employer',
        'callouts' => [
            '<strong>Tick checkbox:</strong> select multiple employees',
            '<strong>Bulk bar:</strong> floats at the bottom',
            '<strong>Transfer Employer:</strong> select the destination employer',
            '<strong>Effect:</strong> these employees\' notify_out entries are auto-cancelled',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tick the checkbox for the employees you want to move</li>
            <li>Bulk bar → "Actions" → <strong>"Transfer Employer"</strong></li>
            <li>Select the destination employer → confirm</li>
            <li>The system transfers them + auto-cancels their notify_out entries</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Export + Batch PDF</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/03-export-pdf',
        'alt' => 'Export CSV + Bulk PDF buttons',
        'caption' => 'Export + PDF — using Bulk Actions',
        'callouts' => [
            '<strong>Export CSV:</strong> downloads immediately (respecting the filter)',
            '<strong>Advanced Export:</strong> choose your own columns',
            '<strong>Automated PDF:</strong> generate PDFs from a template for multiple people at once',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Filter to the data you need</li>
            <li>Click "Export CSV" (top-right) — downloads immediately</li>
            <li>Or use Bulk Action → "Advanced Export" / "Automated PDF"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: How is this different from the "Employees" menu?</dt>
        <dd>A: Employees = active only, Employment History = everyone, including terminated/contract-ended/notified-out</dd>

        <dt>Q: Do employees in the trash show up here?</dt>
        <dd>A: No — go to "Central Trash" instead — they can be restored from there</dd>
    </dl>
</section>
