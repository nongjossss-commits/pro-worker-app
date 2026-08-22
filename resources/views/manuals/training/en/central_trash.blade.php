{{-- Training Edition: Central Trash (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-trash-fill"></i> {{ __('Central Trash') }} — {{ __('Restore deleted data') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Central Trash"</strong> menu gathers <strong>deleted data</strong> from across the system
        (employees / employers / Production / etc.) — <strong>restorable</strong> within a set period,
        or permanently deleted if not needed
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">View the list of deleted items</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/01-list',
        'alt' => 'Trash items list split by type',
        'caption' => 'Central Trash — split into tabs by entity type',
        'callouts' => [
            '<strong>Tabs:</strong> Employees / Employers / Production / etc.',
            '<strong>Deleted at:</strong> the date it was deleted',
            '<strong>Deleted by:</strong> who deleted it',
            '<strong>Days remaining:</strong> a countdown until auto-purge',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Central Trash</strong></li>
            <li>Select the tab for the type of data that was deleted</li>
            <li>Find the item you want to restore</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Restore, or Delete Forever</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/02-restore-delete',
        'alt' => 'Restore + Delete Forever buttons with confirmation',
        'caption' => 'Restore / Delete Forever — both require confirmation',
        'callouts' => [
            '<strong>♻️ Restore:</strong> brings the data back into normal use',
            '<strong>🗑️ Delete Forever:</strong> permanently deleted — cannot be undone!',
            '<strong>Confirmation dialog:</strong> both actions require confirmation',
            '<strong>Bulk action:</strong> select multiple items at once',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Find the item you want to restore → click <strong>♻️ Restore</strong></li>
            <li>Or click <strong>🗑️ Delete Forever</strong> to permanently delete it</li>
            <li>Confirm in the dialog</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Careful:</strong> Delete Forever = permanent deletion, cannot be undone — make sure before clicking
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: How long does the trash keep items?</dt>
        <dd>A: 30 days by default → then auto-purged (Super Admin can adjust this)</dd>

        <dt>Q: If I restore a deleted employee, is their employer link the same as before?</dt>
        <dd>A: Yes — every relationship + document + activity log stays intact</dd>

        <dt>Q: Can Staff delete data?</dt>
        <dd>A: Depends on permissions — some actions require Admin or higher</dd>
    </dl>
</section>
