{{--
    Workflow "ส่งข้อมูลไปแท็บอื่น" (WorkflowHandoffController).
    - Send: Actions → Send to another tab → pick a tab → ticked employees wait there.
    - Receive: the target tab shows a banner → open → tick → add to the employer's card
      (multi-card tabs ask which card / a new one) or dismiss.
    Included by workflow/index.blade.php only for users who can manage Workflow.
--}}
@php
    $handoffTabs = $tabs->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values();
@endphp

{{-- Send to another tab --}}
<div class="modal fade" id="handoffSendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-send me-2"></i>{{ __('Send to another tab') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary mb-2">
                    {{ __('Send the data of :count selected employees to the tab below. They will wait in that tab until someone adds them to an employer card. Nothing changes here.', ['count' => '__N__']) }}
                </p>
                <div class="list-group" id="handoffTabList"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary fw-bold" id="handoffSendBtn" disabled>
                    <i class="bi bi-send me-1"></i>{{ __('Send') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Received in this tab --}}
<div class="modal fade" id="handoffInboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-inbox me-2"></i>{{ __('Employees sent to this tab') }} — <span id="handoffInboxTab"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- step 1: pick people --}}
                <div id="handoffStepPick">
                    <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-body-tertiary">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="handoffPickAll">
                            <label class="form-check-label fw-bold" for="handoffPickAll">{{ __('Select All') }} (<span id="handoffPickedCount">0</span>)</label>
                        </div>
                    </div>
                    <div id="handoffList" class="list-group list-group-flush"></div>
                </div>
                {{-- step 2: which card (per employer) --}}
                <div id="handoffStepCards" class="p-3" style="display:none">
                    <p class="small text-body-secondary mb-3" id="handoffCardsHint"></p>
                    <div id="handoffCardGroups" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
            <div class="modal-footer py-2 justify-content-between">
                <button type="button" class="btn btn-outline-danger" id="handoffDismissBtn" disabled>
                    <i class="bi bi-x-circle me-1"></i>{{ __('Not needed (remove from list)') }}
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" id="handoffBackBtn" style="display:none">{{ __('Back') }}</button>
                    <button type="button" class="btn btn-primary fw-bold" id="handoffNextBtn" disabled>
                        <i class="bi bi-person-plus me-1"></i>{{ __('Add to card') }}
                    </button>
                    <button type="button" class="btn btn-success fw-bold" id="handoffConfirmBtn" style="display:none">
                        <i class="bi bi-check-lg me-1"></i>{{ __('Confirm and add') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const T = {
        sendCountText: @json(__('Send the data of :count selected employees to the tab below. They will wait in that tab until someone adds them to an employer card. Nothing changes here.')),
        thisTab: @json(__('This tab')),
        selectFirst: @json(__('Please select employees first.')),
        sentTitle: @json(__('Sent to :tab')),
        sentN: @json(__(':count employees sent')),
        notSent: @json(__('Not sent')),
        failed: @json(__('Could not save. Please try again.')),
        empty: @json(__('No employees waiting.')),
        from: @json(__('From')),
        by: @json(__('Sent by')),
        employer: @json(__('Employer')),
        newCard: @json(__('Create a new card')),
        people: @json(__('people')),
        created: @json(__('created')),
        oneCardHint: @json(__('This tab has one card per employer — they will be added to that card (it is created if the employer has none yet).')),
        multiHint: @json(__('This tab can have several cards per employer — choose the card for each employer.')),
        willAddTo: @json(__('Will be added to')),
        chooseCard: @json(__('Choose a card for :name')),
        addedN: @json(__(':count employees added to the card')),
        notAdded: @json(__('Not added')),
        dismissConfirm: @json(__('Remove :count employees from this list? They will not be added to any card.')),
        dismissed: @json(__('Removed from the list')),
    };
    const URLS = {
        send: @json(route('workflow.handoffs.send')),
        list: @json(route('workflow.handoffs.index')),
        receive: @json(route('workflow.handoffs.receive')),
        dismiss: @json(route('workflow.handoffs.dismiss')),
    };
    const TABS = @json($handoffTabs);
    const CURRENT_TAB = @json(isset($activeTab) ? $activeTab->id : null);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const modal = id => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));

    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || T.failed);
        return data;
    }
    const skippedHtml = list => list && list.length
        ? '<ul class="text-start small mb-0 mt-2">' + list.map(s => '<li><b>' + esc(s.name) + '</b> — ' + esc(s.reason) + '</li>').join('') + '</ul>'
        : '';

    // ---------- send ----------
    let sendTarget = null;
    window.openHandoffSendModal = function () {
        const picked = window.getGlobalSelectedData ? window.getGlobalSelectedData() : [];
        if (!picked.length) { appToast(T.selectFirst, 'warning'); return; }
        sendTarget = null;
        document.querySelector('#handoffSendModal .modal-body p').textContent = T.sendCountText.replace(':count', picked.length);
        const list = document.getElementById('handoffTabList');
        list.innerHTML = TABS.map(t => {
            const here = t.id === CURRENT_TAB;
            return '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-tab="' + t.id + '"' + (here ? ' disabled' : '') + '>'
                + '<span><i class="bi bi-folder2-open me-2"></i>' + esc(t.name) + '</span>'
                + (here ? '<span class="badge text-bg-secondary">' + esc(T.thisTab) + '</span>' : '<i class="bi bi-circle"></i>')
                + '</button>';
        }).join('');
        document.getElementById('handoffSendBtn').disabled = true;
        modal('handoffSendModal').show();
    };
    document.getElementById('handoffTabList').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-tab]');
        if (!btn || btn.disabled) return;
        sendTarget = Number(btn.dataset.tab);
        this.querySelectorAll('[data-tab]').forEach(b => {
            const on = b === btn;
            b.classList.toggle('active', on);
            const icon = b.querySelector('i.bi-circle, i.bi-check-circle-fill');
            if (icon) icon.className = on ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
        document.getElementById('handoffSendBtn').disabled = false;
    });
    document.getElementById('handoffSendBtn').addEventListener('click', async function () {
        if (!sendTarget) return;
        const picked = window.getGlobalSelectedData();
        this.disabled = true;
        try {
            const data = await post(URLS.send, {
                target_work_type_id: sendTarget,
                source_work_type_id: CURRENT_TAB,
                employees: picked.map(p => ({ id: Number(p.id), employer_id: p.employer_id ? Number(p.employer_id) : null })),
            });
            modal('handoffSendModal').hide();
            const html = '<div>' + esc(T.sentN.replace(':count', data.sent)) + '</div>'
                + (data.skipped.length ? '<div class="mt-2 fw-bold">' + esc(T.notSent) + ' (' + data.skipped.length + ')</div>' + skippedHtml(data.skipped) : '');
            Swal.fire({ icon: data.sent ? (data.skipped.length ? 'warning' : 'success') : 'info', title: T.sentTitle.replace(':tab', data.tab), html: html });
            if (data.sent && window.clearGlobalSelection) window.clearGlobalSelection();
        } catch (err) {
            appAlert(err.message, { icon: 'error' });
        } finally {
            this.disabled = false;
        }
    });

    // ---------- receive ----------
    let inbox = null;
    const picked = () => [...document.querySelectorAll('#handoffList .handoff-pick:checked')].map(c => Number(c.value));
    function refreshPickState() {
        const ids = picked();
        document.getElementById('handoffPickedCount').textContent = ids.length;
        document.getElementById('handoffNextBtn').disabled = !ids.length;
        document.getElementById('handoffDismissBtn').disabled = !ids.length;
        const all = document.querySelectorAll('#handoffList .handoff-pick:not(:disabled)');
        document.getElementById('handoffPickAll').checked = all.length > 0 && ids.length === all.length;
    }
    function showStep(step) {
        const pick = step === 'pick';
        document.getElementById('handoffStepPick').style.display = pick ? '' : 'none';
        document.getElementById('handoffStepCards').style.display = pick ? 'none' : '';
        document.getElementById('handoffNextBtn').style.display = pick ? '' : 'none';
        document.getElementById('handoffDismissBtn').style.display = pick ? '' : 'none';
        document.getElementById('handoffBackBtn').style.display = pick ? 'none' : '';
        document.getElementById('handoffConfirmBtn').style.display = pick ? 'none' : '';
    }
    async function loadInbox(workTypeId) {
        const res = await fetch(URLS.list + '?work_type_id=' + encodeURIComponent(workTypeId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || T.failed);
        inbox = data;
        document.getElementById('handoffInboxTab').textContent = data.tab.name;
        const list = document.getElementById('handoffList');
        list.innerHTML = data.handoffs.length ? data.handoffs.map(h => {
            const photo = h.photo || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(h.name) + '&background=random');
            return '<label class="list-group-item d-flex align-items-center gap-3 py-2' + (h.blocked ? ' opacity-75' : '') + '">'
                + '<input class="form-check-input handoff-pick flex-shrink-0 mt-0" type="checkbox" value="' + h.id + '">'
                + '<img src="' + esc(photo) + '" class="rounded-circle flex-shrink-0" style="width:40px;height:40px;object-fit:cover" alt="">'
                + '<div class="flex-grow-1" style="min-width:0">'
                +   '<div class="fw-bold text-truncate">' + esc((h.title ? h.title + ' ' : '') + h.name) + '</div>'
                +   '<div class="small text-body-secondary">' + esc([h.nationality, h.passport].filter(Boolean).join(' · ')) + '</div>'
                +   (h.blocked ? '<div class="small text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + esc(h.blocked) + '</div>' : '')
                + '</div>'
                + '<div class="small text-end flex-shrink-0" style="max-width:45%">'
                +   '<div><span class="text-body-secondary">' + esc(T.employer) + ':</span> <b>' + esc(h.employer_name) + '</b></div>'
                +   '<div class="text-body-secondary">' + (h.from_tab ? esc(T.from) + ': ' + esc(h.from_tab) + ' · ' : '') + esc(T.by) + ': ' + esc(h.sent_by || '-') + ' · ' + esc(h.sent_at) + '</div>'
                + '</div>'
                + '</label>';
        }).join('') : '<div class="text-center text-body-secondary py-5">' + esc(T.empty) + '</div>';
        refreshPickState();
        showStep('pick');
    }
    window.openHandoffInbox = async function (workTypeId) {
        try {
            await loadInbox(workTypeId);
            modal('handoffInboxModal').show();
        } catch (err) { appAlert(err.message, { icon: 'error' }); }
    };
    document.getElementById('handoffList').addEventListener('change', e => { if (e.target.classList.contains('handoff-pick')) refreshPickState(); });
    document.getElementById('handoffPickAll').addEventListener('change', function () {
        document.querySelectorAll('#handoffList .handoff-pick:not(:disabled)').forEach(c => { c.checked = this.checked; });
        refreshPickState();
    });

    document.getElementById('handoffNextBtn').addEventListener('click', function () {
        const ids = picked();
        const chosen = inbox.handoffs.filter(h => ids.includes(h.id));
        const employerIds = [...new Set(chosen.map(h => h.employer_id))];
        document.getElementById('handoffCardsHint').textContent = inbox.tab.multiple ? T.multiHint : T.oneCardHint;
        document.getElementById('handoffCardGroups').innerHTML = employerIds.map(eid => {
            const emp = inbox.employers.find(e => e.id === eid) || { id: eid, name: '#' + eid, cards: [] };
            const names = chosen.filter(h => h.employer_id === eid).map(h => esc(h.name)).join(', ');
            let body;
            if (!inbox.tab.multiple) {
                const card = emp.cards[0];
                body = '<div class="small"><i class="bi bi-arrow-right-circle me-1 text-primary"></i>' + esc(T.willAddTo) + ': <b>'
                    + (card ? esc(card.name) + '</b> (' + card.people + ' ' + esc(T.people) + ')' : esc(T.newCard) + '</b>') + '</div>';
            } else {
                body = emp.cards.map((c, i) => '<div class="form-check"><input class="form-check-input" type="radio" name="handoffCard-' + eid + '" id="hc-' + eid + '-' + c.id + '" value="' + c.id + '"' + (emp.cards.length === 1 ? ' checked' : '') + '>'
                        + '<label class="form-check-label" for="hc-' + eid + '-' + c.id + '"><b>' + esc(c.name) + '</b> <span class="text-body-secondary small">· ' + c.people + ' ' + esc(T.people) + ' · ' + esc(T.created) + ' ' + esc(c.created) + '</span></label></div>').join('')
                    + '<div class="form-check"><input class="form-check-input" type="radio" name="handoffCard-' + eid + '" id="hc-' + eid + '-new" value="new"' + (emp.cards.length === 0 ? ' checked' : '') + '>'
                    + '<label class="form-check-label text-primary fw-bold" for="hc-' + eid + '-new"><i class="bi bi-plus-circle me-1"></i>' + esc(T.newCard) + '</label></div>';
            }
            return '<div class="border rounded p-3" data-employer="' + eid + '">'
                + '<div class="fw-bold mb-1"><i class="bi bi-building me-1"></i>' + esc(emp.name) + '</div>'
                + '<div class="small text-body-secondary mb-2">' + names + '</div>' + body + '</div>';
        }).join('');
        showStep('cards');
    });
    document.getElementById('handoffBackBtn').addEventListener('click', () => showStep('pick'));

    document.getElementById('handoffConfirmBtn').addEventListener('click', async function () {
        const ids = picked();
        const cards = {};
        if (inbox.tab.multiple) {
            for (const box of document.querySelectorAll('#handoffCardGroups [data-employer]')) {
                const eid = box.dataset.employer;
                const sel = box.querySelector('input[type=radio]:checked');
                if (!sel) {
                    const name = (inbox.employers.find(e => String(e.id) === eid) || {}).name || ('#' + eid);
                    appAlert(T.chooseCard.replace(':name', name), { icon: 'warning' });
                    return;
                }
                cards[eid] = String(sel.value);
            }
        }
        this.disabled = true;
        try {
            const data = await post(URLS.receive, { work_type_id: inbox.tab.id, handoff_ids: ids, cards: cards });
            modal('handoffInboxModal').hide();
            const html = '<div>' + esc(T.addedN.replace(':count', data.added)) + '</div>'
                + (data.skipped.length ? '<div class="mt-2 fw-bold">' + esc(T.notAdded) + ' (' + data.skipped.length + ')</div>' + skippedHtml(data.skipped) : '');
            await Swal.fire({ icon: data.skipped.length ? 'warning' : 'success', html: html });
            window.location.reload();
        } catch (err) {
            appAlert(err.message, { icon: 'error' });
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('handoffDismissBtn').addEventListener('click', async function () {
        const ids = picked();
        if (!ids.length) return;
        if (!(await appConfirm(T.dismissConfirm.replace(':count', ids.length), { danger: true }))) return;
        try {
            await post(URLS.dismiss, { handoff_ids: ids });
            appToast(T.dismissed);
            await loadInbox(inbox.tab.id);
            if (!inbox.handoffs.length) window.location.reload();
        } catch (err) { appAlert(err.message, { icon: 'error' }); }
    });
})();
</script>
