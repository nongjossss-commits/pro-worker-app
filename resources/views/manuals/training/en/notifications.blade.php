{{-- Training Edition: Notifications (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-bell-fill"></i> {{ __('Notifications') }} — {{ __('The hub for every kind of alert') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Notifications"</strong> menu gathers <strong>every alert</strong> in the system —
        e.g. an employee nearing expiry, an approved quotation, a new customer ticket.
        Supports <strong>Web Push</strong> (browser popup notifications) + an <strong>in-app bell icon</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker</span>
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">View Notifications + Mark as Read</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/01-list',
        'alt' => 'Notification list + unread/all filter',
        'caption' => 'Notifications List — separated into unread/read',
        'callouts' => [
            '<strong>Bell icon:</strong> top-right of the navbar — shows an unread count badge',
            '<strong>Filter:</strong> Unread / All / By type',
            '<strong>Click a notification:</strong> opens the related item directly',
            '<strong>Mark all as read:</strong> clears the badge counter',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Notification types</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/02-types',
        'alt' => 'Examples of the various notification types',
        'caption' => 'Notification Types — different colors and icons',
        'callouts' => [
            '<strong>🔴 Expiry alerts:</strong> employees nearing expiry (passport/visa/WP)',
            '<strong>🔵 Ticket:</strong> a customer sent a new request',
            '<strong>🟢 Approved:</strong> a quotation / contract was approved',
            '<strong>🟡 Workflow:</strong> a job entered a new step',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Enable Web Push Notifications</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/03-web-push',
        'alt' => 'Browser popup requesting web push permission',
        'caption' => 'Web Push — receive alerts even with the browser closed',
        'callouts' => [
            '<strong>Permission popup:</strong> appears the first time you log in',
            '<strong>"Allow":</strong> receive notifications through the browser',
            '<strong>Background:</strong> keeps working even with the tab closed',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Log in → the browser will ask for permission</li>
            <li>Click <strong>"Allow"</strong></li>
            <li>When a notification arrives → it pops up in the browser immediately</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: I don't see the web push popup?</dt>
        <dd>A: Browser settings → site permissions → notifications → allow it manually</dd>

        <dt>Q: Who receives notifications?</dt>
        <dd>A: Depends on role — Admin sees everything, Caretaker/Employer only see their own</dd>
    </dl>
</section>
