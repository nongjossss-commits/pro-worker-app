{{-- Training Edition: Importers (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-box-seam-fill"></i> {{ __('Importers') }} — {{ __('Companies that import MOU labor from abroad') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Importers"</strong> menu stores data on <strong>labor-importing companies</strong> (MOU Importers)
        that handle importing labor from abroad — with <strong>signature + stamp</strong> used on MOU documents
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the menu + view the Importer list</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/01-list',
        'alt' => 'Importers list',
        'caption' => 'Importers List',
        'callouts' => [
            '<strong>Company name (TH/EN):</strong> as per commercial registration',
            '<strong>Registration number:</strong> Importer Registration Number',
            '<strong>Address:</strong> the registered address',
            '<strong>Signature 1/2 + Stamp:</strong> used in automated PDFs',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Add + edit Importer data</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/02-form',
        'alt' => 'Importer form',
        'caption' => 'Importer Form — data + 2 signature slots',
        'callouts' => [
            '<strong>Basic info:</strong> name + tax ID + address',
            '<strong>Signature 1:</strong> the primary authorized signer',
            '<strong>Signature 2:</strong> a secondary authorized signer (optional)',
            '<strong>Stamp:</strong> the company stamp',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click "+ Add Importer"</li>
            <li>Fill in the data + upload Signature 1 (primary)</li>
            <li>Upload the stamp + Signature 2 (optional)</li>
            <li>Click Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Where is Importer used?</dt>
        <dd>A: In PDF Templates that have Importer fields (MOU import documents) — when generating, the system pulls in the data + signature automatically</dd>

        <dt>Q: What's the difference between Importer and Agent?</dt>
        <dd>A: Importer = a labor-importing company (plays a role in MOU/documents), Agent = a broker who brings in customers (broker fee)</dd>

        <dt>Q: Why are there 2 signature slots?</dt>
        <dd>A: Some documents require 2 directors to sign — the "Signature 2" field is for that case (optional either way)</dd>
    </dl>
</section>
