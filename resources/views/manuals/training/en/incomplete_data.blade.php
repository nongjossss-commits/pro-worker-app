{{-- Training Edition: Incomplete Data (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ __('Incomplete Data') }} — {{ __('A tool for finding employees with missing data') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Incomplete Data"</strong> menu is a tool for finding <strong>employees with missing data</strong> —
        e.g. no passport, no work permit expiry, no photo, no address —
        so the team can fix it before the data is used for real
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the menu + view employees with missing data</h2>

    @include('manuals.training._screenshot', [
        'src' => 'incomplete_data/01-list',
        'alt' => 'List of employees with incomplete data',
        'caption' => 'Incomplete Data List — shows which fields are missing',
        'callouts' => [
            '<strong>Employee:</strong> name + employer',
            '<strong>Missing fields:</strong> a red badge shows which field is missing',
            '<strong>Edit ✏️ button:</strong> click to go straight to that employee\'s edit page',
            '<strong>Filter:</strong> select by which type of field is missing',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Incomplete Data</strong></li>
            <li>See which employees have missing data + which fields</li>
            <li>Click Edit → go to the employee's page → fill in the data</li>
            <li>Come back and check again — the entry disappears once the data is complete</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Which fields count as "incomplete data"?</dt>
        <dd>A: The critical fields the system needs for generating documents — passport, nationality, employer_id, date of birth, etc.</dd>

        <dt>Q: Why does the employee still appear even though I already filled it in?</dt>
        <dd>A: There's a 60-second cache — wait for it to refresh, or refresh manually</dd>
    </dl>
</section>
