{{-- User Manual: Notifications (English) --}}

<h4><i class="bi bi-bell-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Notifications"</strong> menu holds all the alerts the system generates automatically —
    e.g. passports nearing expiry, visas nearing expiry, jobs approaching their deadline, new messages from employers
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access it?</h4>
<p>Anyone with the <code>view-notifications</code> permission</p>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. View notifications</h5>
<div class="manual-step">
    Click the bell icon on the navbar, or open the Notifications menu — items are shown newest first
</div>

<h5>2. Mark as read</h5>
<div class="manual-step">
    Click a notification → the system marks it as read automatically
</div>

<h5>3. Dismiss/delete a notification</h5>
<div class="manual-step">
    Click the X icon on a notification — only available to those with the <code>cancel-notifications</code> permission
</div>

<h5>4. Snooze a notification</h5>
<div class="manual-step">
    Some notifications can be snoozed (e.g. pushing back a visa-expiry reminder) — click the "Snooze" button
</div>

<h5>5. Edit the employee</h5>
<div class="manual-step">
    Click <i class="bi bi-pencil-fill"></i> (Edit Employee) on an employee card or row — the edit form opens in a window on the same page; after saving, the notification list refreshes with the latest data. (Shown while the "Employees" menu is enabled; if that menu has a password, you are asked for it first.)
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Web Push:</strong> enable notification permissions in your browser to receive real-time alerts
</div>

<div class="manual-tip">
    <strong>Expiry Scanner:</strong> the system scans expiry dates every morning (the CheckExpiries cron job) — new notifications are created automatically
</div>
<div class="manual-tip">
    <strong>Send to a LINE chat:</strong> drag the <i class="bi bi-grid-3x2-gap-fill"></i> button on a card into the chat, or click it to copy text / a card image / share — see the Employees manual, section 11
</div>
