{{-- Training Edition: Delegates (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard"></i> {{ __('Delegates') }} — {{ __('People authorized to sign on an employer\'s behalf') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Delegates"</strong> menu stores data on people who are <strong>authorized</strong> to sign on an employer's behalf
        on various documents (e.g. power of attorney, request forms) — with signature + stamp + address, just like an employer
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the Delegates menu</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/01-list',
        'alt' => 'Delegate list + filter',
        'caption' => 'Delegates List',
        'callouts' => [
            '<strong>Name TH/EN + Position:</strong> the authorized signer',
            '<strong>Linked Employer:</strong> which employer(s) they\'re linked to (optional)',
            '<strong>+ Add Delegate:</strong> create a new one',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Add + edit delegate data</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/02-form',
        'alt' => 'Delegate create/edit form',
        'caption' => 'Delegate Form — data plus signature',
        'callouts' => [
            '<strong>Personal info:</strong> name + position + tax ID + address',
            '<strong>Signature:</strong> upload a PNG (transparent background)',
            '<strong>Power of attorney:</strong> attach a reference PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click "+ Add Delegate"</li>
            <li>Fill in the data + upload the signature</li>
            <li>Click Save</li>
            <li>Use it when generating a PDF: specify the Delegate field — the system pulls in the name/signature automatically</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: How is this different from "Employee Info" in the employer role's sidebar?</dt>
        <dd>A: In the employer role's sidebar, "Employee Info" = Delegates (the company's authorized signers), while the "Employees" menu = the actual migrant workers</dd>

        <dt>Q: How many employers can one Delegate sign for?</dt>
        <dd>A: Multiple — when generating a PDF, you can select whichever Delegate you need</dd>
    </dl>
</section>
