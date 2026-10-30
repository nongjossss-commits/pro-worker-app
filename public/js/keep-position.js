/*
 * Keep the user's place (2026-10-30): after an action that reloads the page
 * — save / delete / cancel / edit-and-come-back, in any menu — the page comes
 * back where the user was, instead of at the top.
 *
 *  - When the page is left, its position is remembered per address
 *    (path + query) in sessionStorage: the card / row at the top of the
 *    screen (by id) and how far down it was, the scroll position, and the
 *    open accordions (Workflow / Pre-Production / resolution job cards).
 *  - It is put back when the page is RELOADED (or reached with Back /
 *    Forward), or when the user left it
 *    through an action on the page (a form sent, a link inside a card such as
 *    "Edit") and comes back to the same address within 30 minutes.
 *    Arriving from the menu / a normal link starts at the top as before.
 *  - Accordions are reopened first (their cards load on open), then the page
 *    waits up to 5 s for the remembered card to appear.
 *  - An address with #anchor (e.g. "#employee-card-12" after editing) is
 *    left to the existing highlight-and-scroll handler.
 *
 * Cheap: one small sessionStorage write when leaving; nothing while working.
 */
(function () {
    'use strict';

    var PREFIX = 'keepPos:';
    var MAX_AGE = 30 * 60 * 1000;
    // Elements that mark "where the user is" — the first one with an id wins.
    // Employee cards / rows first; a job card's header only when no card is on
    // screen (headers stick to the top while scrolling, so they don't say where you are).
    var ANCHORS = '[id^="employee-card-"],[id^="item-card-"],[id^="history-row-"],[id^="notification-item-"],'
        + 'tr[id],[id^="employer-card-"]';
    var FALLBACK_ANCHORS = '[id^="heading-"],[id^="heading"],.production-order-card-container[id]';
    // Clicks / submits inside these count as "working on the page" (come back to the same place).
    var WORK_AREAS = '.employee-card,[id^="item-card-"],[id^="history-row-"],.notification-item,'
        + '.production-order-card,.accordion-item,table tbody,.modal,.swal2-container,.bulk-action-bar';

    function key(url) {
        var u = url ? new URL(url, location.href) : location;
        return PREFIX + u.pathname + u.search;
    }

    function read(k) {
        try { return JSON.parse(sessionStorage.getItem(k) || 'null'); } catch (e) { return null; }
    }

    function write(k, v) {
        try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {}
    }

    function anchorAtTop() {
        var x = Math.min(window.innerWidth / 2, 600);
        var lists = [ANCHORS, FALLBACK_ANCHORS];
        for (var i = 0; i < lists.length; i++) {
            for (var y = 90; y < Math.min(window.innerHeight - 20, 700); y += 30) {
                var el = document.elementFromPoint(x, y);
                var a = el && el.closest ? el.closest(lists[i]) : null;
                if (a && a.id) {
                    var frame = scrollableParent(a);
                    return { id: a.id, top: Math.round(a.getBoundingClientRect().top), frameTop: frame ? Math.round(frame.scrollTop) : null };
                }
            }
        }
        return null;
    }

    var actionLeave = false;

    function remember() {
        var open = [];
        document.querySelectorAll('.accordion-collapse.collapse.show[id]').forEach(function (el) {
            if (open.length < 6) open.push(el.id);
        });
        write(key(), {
            y: Math.round(window.scrollY),
            anchor: anchorAtTop(),
            open: open,
            t: Date.now(),
            action: actionLeave,
        });
    }

    // Leaving through an action on the page (form sent / link inside a card / modal).
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.closest && e.target.closest('form')) {
            var m = (e.target.getAttribute('method') || 'get').toLowerCase();
            if (m !== 'get' || e.target.closest(WORK_AREAS)) actionLeave = true;
        }
    }, true);
    document.addEventListener('click', function (e) {
        var a = e.target && e.target.closest ? e.target.closest('a[href],button') : null;
        if (a && a.closest(WORK_AREAS) && !a.closest('#sidebar, .sidebar, nav, .pagination')) actionLeave = true;
    }, true);

    window.addEventListener('pagehide', remember);

    function scrollableParent(el) {
        for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
            var oy = getComputedStyle(p).overflowY;
            if ((oy === 'auto' || oy === 'scroll') && p.scrollHeight > p.clientHeight + 2) return p;
        }
        return null;
    }

    function navType() {
        try {
            var n = performance.getEntriesByType('navigation')[0];
            return n ? n.type : '';
        } catch (e) { return ''; }
    }

    function restore() {
        if (location.hash) return; // the #anchor handler takes care of it
        var saved = read(key());
        if (!saved || Date.now() - (saved.t || 0) > MAX_AGE) return;
        // Reload, or Back / Forward (the browser's own restore is switched off below), or an action.
        var type = navType();
        if (type !== 'reload' && type !== 'back_forward' && !saved.action) return;

        // used once — opening the page again later starts at the top
        saved.action = false;
        write(key(), saved);

        // Reopen the job cards that were open (their employees load on open).
        if (saved.open && saved.open.length && window.bootstrap && bootstrap.Collapse) {
            saved.open.forEach(function (id) {
                var el = document.getElementById(id);
                if (el && !el.classList.contains('show')) {
                    el.removeAttribute('data-bs-parent');
                    try { bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show(); } catch (e) {}
                }
            });
        }

        var done = false;
        // Put the remembered card back where it was on screen. A card inside its
        // own scroll frame (x-card-scroll-frame) first gets the frame's old
        // position back, then the page scroll fine-tunes the rest (the browser
        // also restores inner frames by itself, so it is checked again a moment later).
        function align(a) {
            var frame = scrollableParent(a);
            // 'instant': the frames scroll smoothly by CSS, an animation would land late
            if (frame && saved.anchor.frameTop != null) {
                frame.scrollTo({ top: saved.anchor.frameTop, behavior: 'instant' });
            }
            window.scrollTo({ top: Math.max(0, window.scrollY + a.getBoundingClientRect().top - saved.anchor.top), behavior: 'instant' });
        }
        function place() {
            if (done) return true;
            var a = saved.anchor && document.getElementById(saved.anchor.id);
            if (a) {
                align(a);
                done = true;
                // Checked again a few times while the page settles (the browser
                // restores inner frames late, images / lazy cards change heights) —
                // stops as soon as the user scrolls or types themselves.
                var userMoved = false;
                var stop = function () { userMoved = true; };
                ['wheel', 'touchstart', 'keydown', 'mousedown'].forEach(function (ev) { window.addEventListener(ev, stop, { once: true, passive: true, capture: true }); });
                [350, 900, 1800].forEach(function (ms) {
                    setTimeout(function () {
                        if (userMoved) return;
                        var again = document.getElementById(saved.anchor.id);
                        if (again && Math.abs(again.getBoundingClientRect().top - saved.anchor.top) > 4) align(again);
                    }, ms);
                });
                return true;
            }
            return false;
        }

        if (!place()) {
            // Not there (yet): scroll to the old position now, and to the card when it loads.
            window.scrollTo({ top: saved.y || 0, behavior: 'instant' });
            if (saved.anchor) {
                var obs = new MutationObserver(function () { if (place()) obs.disconnect(); });
                obs.observe(document.body, { childList: true, subtree: true });
                setTimeout(function () { obs.disconnect(); }, 5000);
            }
        }
    }

    if ('scrollRestoration' in history) {
        // The browser's own restore runs before lazily loaded cards exist; this script places it instead.
        try { history.scrollRestoration = 'manual'; } catch (e) {}
    }
    if (document.readyState === 'complete') {
        setTimeout(restore, 0);
    } else {
        window.addEventListener('load', function () { setTimeout(restore, 0); });
    }

    window.KeepPosition = { remember: remember };
})();
