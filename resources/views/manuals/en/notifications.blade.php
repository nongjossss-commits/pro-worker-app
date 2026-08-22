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

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Web Push:</strong> enable notification permissions in your browser to receive real-time alerts
</div>

<div class="manual-tip">
    <strong>Expiry Scanner:</strong> the system scans expiry dates every morning (the CheckExpiries cron job) — new notifications are created automatically
</div>
