{{-- Training Edition: Employer Ticket (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-send-fill"></i> {{ __('Employer Ticket') }} — {{ __('For employers: send a request to the office') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Employer Ticket"</strong> menu is for the <strong>Employer role</strong>
        to send requests directly to the office — e.g. "request a visa renewal", "request an employee resignation" — instead of email/Line.
        The office receives these in the <strong>Ticket Inbox</strong> menu
    </p>
    <div class="training-role-row">
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Create a new ticket</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/01-new-ticket',
        'alt' => 'New ticket creation form',
        'caption' => 'New Ticket Form — select type + details + attach documents',
        'callouts' => [
            '<strong>Type:</strong> Visa / Work Permit / Passport / Other',
            '<strong>Related employee:</strong> select from your own employees',
            '<strong>Details:</strong> describe what you need',
            '<strong>Attach a file:</strong> PDF / image (optional)',
            '<strong>Priority:</strong> Normal / High / Urgent',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar (Employer) → <strong>Employer Ticket</strong> or "+ New Ticket"</li>
            <li>Select the type + the employee</li>
            <li>Fill in the details + attach documents</li>
            <li>Click Submit → the office receives a notification immediately</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Track status + reply</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/02-status-chat',
        'alt' => 'Ticket list + chat thread',
        'caption' => 'My Tickets — track status + chat with the office',
        'callouts' => [
            '<strong>Status:</strong> Open / In Progress / Resolved',
            '<strong>Chat thread:</strong> talk with the office',
            '<strong>Notification:</strong> pops up when the office replies',
            '<strong>Mark Resolved:</strong> close the ticket once satisfied',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: How many tickets can I submit?</dt>
        <dd>A: Unlimited — each ticket is a separate matter</dd>

        <dt>Q: Can I see other companies' tickets?</dt>
        <dd>A: No — you only see your own company's</dd>

        <dt>Q: The office closed my ticket, but it isn't finished yet?</dt>
        <dd>A: Open a new ticket + reference the old ticket's number</dd>
    </dl>
</section>
