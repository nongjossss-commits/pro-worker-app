{{--
    User Manual: Sales (Read and Sale) (English)
--}}

<h4><i class="bi bi-megaphone-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Read and Sale"</strong> menu is where you manage customers who are <strong>not yet</strong> full employers.
    Use it to log inquiries (Leads), create quotations, and once a sale closes,
    the system automatically creates the <strong>Employer + Employees + Production job</strong>.
</p>
<p>
    This is the starting point of the whole workflow — from sales picking up a new customer → closing the sale → into day-to-day management.
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — can access</li>
    <li><span class="manual-role">Caretaker</span> <span class="manual-role">Employer</span> — cannot access</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<p>This page is a <strong>Kanban Board</strong> — with columns based on customer status:</p>
<ol>
    <li><strong>New (Lead)</strong> — just made contact, not yet discussed</li>
    <li><strong>In Progress</strong> — negotiating terms</li>
    <li><strong>Quoted</strong> — quotation sent to the customer, awaiting reply</li>
    <li><strong>Won</strong> — deal agreed! about to enter the real system</li>
    <li><strong>Lost</strong> — customer declined</li>
</ol>
<p>Drag a card between columns to change its status</p>

<h4><i class="bi bi-list-check me-2"></i>Common tasks</h4>

<h5>1. Add a new customer (Lead)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click <strong>"+ Add Lead"</strong> at the top of the page</li>
        <li>Fill in the basic info — company/individual name, phone number, source (e.g. phone call, referral, Facebook)</li>
        <li>Select the <strong>job type</strong> (MOU, Visa, other)</li>
        <li>Click <strong>"Save"</strong> — the Lead appears in the "New" column</li>
    </ol>
</div>

<h5>2. Create a quotation</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the Lead's card</li>
        <li>Click <strong>"Create Quotation"</strong></li>
        <li>Enter line items/services + price + quantity</li>
        <li>Select the <strong>"Financial Profile"</strong> (biller) — the office profile that will appear on the document</li>
        <li>Click <strong>"Preview"</strong> to see a sample PDF</li>
        <li>Click <strong>"Send"</strong> — the Lead automatically moves to the "Quoted" column</li>
    </ol>
</div>

<h5>3. Close the sale → enter the real system</h5>
<div class="manual-step">
    Once the customer agrees:
    <ol class="mb-0 mt-2">
        <li>Open the card in "Quoted"</li>
        <li>Click <strong>"Close Sale / Convert to Real Customer"</strong></li>
        <li>The system will ask for full <strong>employer</strong> details (tax ID, registered address, etc.)</li>
        <li>Enter the <strong>list of employees</strong> to manage (one by one or via Excel import)</li>
        <li>Click <strong>"Confirm & Create"</strong> — the system creates all of these at once:
            <ul>
                <li>A new employer in the "Employers" menu</li>
                <li>All the employees in the "Employees" menu</li>
                <li>A new Production job in the "P Production" menu</li>
            </ul>
        </li>
    </ol>
</div>

<h5>4. Cancel a Lead</h5>
<div class="manual-step">
    Drag the card to the <strong>"Lost"</strong> column, or click "Cancel" on the card
    and specify a reason (e.g. "too expensive", "unreachable")
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>Tip:</strong>
        A cancelled Lead stays in the history — you can go back to review the reason and statistics
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Things to know / Tips</h4>

<div class="manual-tip">
    <strong>Why start with a Lead before entering the real system?</strong>
    Because 70-80% of Leads normally don't close — creating an employer straight away would clutter the system with junk data.
    Lead/Sales is kept separate so the real employer list stays clean and reliable
</div>

<div class="manual-tip">
    <strong>Quotation ≠ Tax Invoice:</strong>
    A Quotation has no tax implications — it's just used to communicate a price.
    A Tax Invoice is issued after the customer has paid, in the Finance menu
</div>

<div class="manual-tip">
    <strong>Drag cards to change status:</strong> no need to open the card — just drag it with the mouse across columns.
    The system saves automatically
</div>

<div class="manual-warn">
    <strong>Cannot edit after closing a sale:</strong>
    once you click "Close Sale" and the system has created the employer/employees,
    the original Lead gets "locked" and can't be edited anymore — changes must be made directly on the employer/employee records
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>

<dl>
    <dt>Q: Why can't I see this menu?</dt>
    <dd>A: This menu is only visible to the <strong>Super Admin / Admin / Staff</strong> roles — Caretaker and Employer don't have permission to see it</dd>

    <dt>Q: Can I edit a quotation that has already been sent?</dt>
    <dd>A: Yes, until the customer closes the sale — after that it's locked</dd>

    <dt>Q: An old customer is back, do I need to start a new Lead?</dt>
    <dd>A: No — if they're already an employer in the system, just add a new Production job in the Production menu</dd>

    <dt>Q: Who is the "Lead owner"?</dt>
    <dd>A: Whoever created the Lead becomes its <strong>owning salesperson</strong>, used for future commission calculations</dd>
</dl>
