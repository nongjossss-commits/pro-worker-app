{{-- Training Edition: Agents (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Agents') }} — {{ __('Brokers who bring customers or employees to the office') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Agents"</strong> menu stores data on <strong>brokers/agents</strong>
        who bring customers or employees to the office — for tracking broker fees + commission
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the Agents menu + add an agent</h2>

    @include('manuals.training._screenshot', [
        'src' => 'agents/01-list-add',
        'alt' => 'Agent list + add button',
        'caption' => 'Agents List + Add Modal',
        'callouts' => [
            '<strong>Agent name:</strong> the individual\'s or brokerage's name',
            '<strong>Contact:</strong> phone / Email / Line',
            '<strong>Commission:</strong> broker fee percentage',
            '<strong>Notes:</strong> add any specific details',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Agents</strong></li>
            <li>Click "+ Add Agent"</li>
            <li>Fill in the details → click Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: What's the difference between Agent and Importer?</dt>
        <dd>A: Agent = a broker who brings in customers (Thai broker), Importer = a labor-importing company (with signature/stamp used in documents)</dd>

        <dt>Q: Can I link an Agent to an Employer?</dt>
        <dd>A: Yes — Employer has a "Referring Agent" field to select an Agent</dd>
    </dl>
</section>
