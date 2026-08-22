{{-- Training Edition: Ticket Inbox (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-inbox-fill"></i> {{ __('Ticket Inbox') }} — {{ __('Receive and manage requests from employers') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Ticket Inbox"</strong> menu is where <strong>tickets</strong> submitted by employers arrive —
        e.g. "request a visa renewal for this employee", "request a passport change" — Admin/Staff receive + assign + track them
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-tickets)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Receive a new ticket + Assign it</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/01-list-assign',
        'alt' => 'Ticket list + assignment dropdown',
        'caption' => 'Ticket Inbox — list by status',
        'callouts' => [
            '<strong>Status:</strong> Open / In Progress / Resolved / Closed',
            '<strong>Assigned to:</strong> the staff member responsible for this ticket',
            '<strong>Priority:</strong> Normal / High / Urgent',
            '<strong>Type:</strong> visa / wp / passport / others',
            '<strong>Last updated:</strong> time of the last reply',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Ticket Inbox</strong></li>
            <li>Click a ticket to open it</li>
            <li>Click "Assign to..." → select the responsible staff member</li>
            <li>Update the status as work progresses</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Reply + attach documents</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/02-chat',
        'alt' => 'Ticket reply page + chat thread',
        'caption' => 'Ticket Detail — Chat thread + attachments',
        'callouts' => [
            '<strong>Message thread:</strong> messages between office ↔ employer',
            '<strong>Attach documents:</strong> upload PDF/images',
            '<strong>Attach an employee:</strong> link a specific employee to the ticket',
            '<strong>Mark Resolved:</strong> close the ticket once done',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Is the customer notified when I reply?</dt>
        <dd>A: The system sends a notification + email automatically whenever there's a reply</dd>

        <dt>Q: Can I reassign a ticket?</dt>
        <dd>A: Yes — Admin can change the responsible staff member at any time</dd>
    </dl>
</section>
