{{--
    Interactive reading aids for the manual bundles — floating table of
    contents (drawer), click-to-zoom screenshots, reading progress bar and a
    back-to-top button. Pure inline CSS/JS with inline SVG icons (no CDN), so
    it also works inside the downloaded standalone .html file. Everything here
    is hidden when printing — the printed booklet stays exactly as before.

    Include once, just before </body>. Reads the sections from the page:
    every <section class="manual-section" id="…"> with its <h2>.
--}}
<style>
    html { scroll-behavior: smooth; }
    .manual-section { scroll-margin-top: 72px; }
    .mv-progress { position: fixed; top: 0; left: 0; height: 3px; width: 0; background: var(--brand, #2563eb); z-index: 1000; transition: width .1s linear; }
    .mv-fab { position: fixed; right: 20px; z-index: 900; width: 48px; height: 48px; border-radius: 50%; border: 0; cursor: pointer;
        display: grid; place-items: center; color: #fff; background: var(--brand, #2563eb); box-shadow: 0 6px 18px rgba(0,0,0,.22); transition: transform .15s, opacity .2s; }
    .mv-fab:hover { transform: translateY(-2px); }
    .mv-fab svg { width: 22px; height: 22px; }
    .mv-fab-toc { bottom: 84px; }
    .mv-fab-top { bottom: 24px; opacity: 0; pointer-events: none; background: #334155; }
    .mv-fab-top.show { opacity: 1; pointer-events: auto; }

    .mv-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 950; opacity: 0; pointer-events: none; transition: opacity .2s; }
    .mv-backdrop.open { opacity: 1; pointer-events: auto; }
    .mv-drawer { position: fixed; top: 0; right: 0; bottom: 0; width: min(340px, 88vw); background: #fff; z-index: 960; transform: translateX(100%);
        transition: transform .25s ease; box-shadow: -8px 0 30px rgba(0,0,0,.18); display: flex; flex-direction: column; font-family: inherit; }
    .mv-drawer.open { transform: none; }
    .mv-drawer-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 3px solid var(--brand, #2563eb); font-weight: 700; font-size: 16px; color: var(--brand, #2563eb); }
    .mv-close { background: none; border: 0; cursor: pointer; color: #64748b; padding: 4px; line-height: 0; }
    .mv-close svg { width: 22px; height: 22px; }
    .mv-drawer ol { list-style: none; margin: 0; padding: 8px 0; overflow-y: auto; flex: 1; counter-reset: mv; }
    .mv-drawer li { margin: 0; counter-increment: mv; }
    .mv-drawer a { display: flex; gap: 10px; padding: 9px 18px; color: #1f2937; text-decoration: none; font-size: 14px; line-height: 1.4; border-left: 4px solid transparent; }
    .mv-drawer a::before { content: counter(mv); min-width: 22px; color: #94a3b8; font-weight: 700; }
    .mv-drawer a:hover { background: #f1f5f9; }
    .mv-drawer a.active { border-left-color: var(--brand, #2563eb); background: #f8fafc; color: var(--brand, #2563eb); font-weight: 700; }

    .manual-section img:not(.mv-noz) { cursor: zoom-in; transition: box-shadow .15s, transform .15s; }
    .manual-section img:not(.mv-noz):hover { box-shadow: 0 8px 24px rgba(0,0,0,.18); transform: translateY(-1px); }
    .mv-lightbox { position: fixed; inset: 0; z-index: 1100; background: rgba(15,23,42,.92); display: none; align-items: center; justify-content: center; flex-direction: column; padding: 24px; cursor: zoom-out; }
    .mv-lightbox.open { display: flex; animation: mvFade .18s ease; }
    .mv-lightbox img { max-width: 96vw; max-height: 84vh; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,.5); background: #fff; animation: mvPop .2s ease; }
    .mv-lightbox .mv-cap { color: #e2e8f0; margin-top: 14px; font-size: 14px; text-align: center; max-width: 900px; }
    .mv-lightbox .mv-close { position: absolute; top: 14px; right: 16px; color: #fff; }
    .mv-lightbox .mv-close svg { width: 30px; height: 30px; }
    .mv-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,.14); border: 0; color: #fff; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display: grid; place-items: center; }
    .mv-nav:hover { background: rgba(255,255,255,.28); }
    .mv-nav svg { width: 24px; height: 24px; }
    .mv-prev { left: 16px; } .mv-next { right: 16px; }
    @keyframes mvFade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes mvPop { from { transform: scale(.94); opacity: .4; } to { transform: none; opacity: 1; } }

    @media print { .mv-progress, .mv-fab, .mv-backdrop, .mv-drawer, .mv-lightbox { display: none !important; } }
</style>

<div class="mv-progress" id="mvProgress"></div>
<button type="button" class="mv-fab mv-fab-toc" id="mvTocBtn" title="{{ __('Table of Contents') }}" aria-label="{{ __('Table of Contents') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
</button>
<button type="button" class="mv-fab mv-fab-top" id="mvTopBtn" title="{{ __('Back to top') }}" aria-label="{{ __('Back to top') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>
<div class="mv-backdrop" id="mvBackdrop"></div>
<nav class="mv-drawer" id="mvDrawer" aria-label="{{ __('Table of Contents') }}">
    <div class="mv-drawer-head">
        <span>{{ __('Table of Contents') }}</span>
        <button type="button" class="mv-close" id="mvDrawerClose" aria-label="{{ __('Close') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <ol id="mvTocList"></ol>
</nav>
<div class="mv-lightbox" id="mvLightbox" role="dialog" aria-modal="true">
    <button type="button" class="mv-close" aria-label="{{ __('Close') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <button type="button" class="mv-nav mv-prev" aria-label="&lsaquo;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg></button>
    <img alt="">
    <div class="mv-cap"></div>
    <button type="button" class="mv-nav mv-next" aria-label="&rsaquo;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg></button>
</div>

<script>
(function () {
    const $ = id => document.getElementById(id);
    const sections = Array.from(document.querySelectorAll('section.manual-section[id]'));

    // ---- Table of contents drawer ----
    const list = $('mvTocList'), drawer = $('mvDrawer'), backdrop = $('mvBackdrop');
    const links = sections.map(sec => {
        const h = sec.querySelector('h2');
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = '#' + sec.id;
        a.textContent = h ? h.textContent.trim() : sec.id;
        li.appendChild(a); list.appendChild(li);
        return a;
    });
    const openDrawer = () => { drawer.classList.add('open'); backdrop.classList.add('open'); };
    const closeDrawer = () => { drawer.classList.remove('open'); backdrop.classList.remove('open'); };
    $('mvTocBtn').addEventListener('click', openDrawer);
    $('mvDrawerClose').addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    list.addEventListener('click', e => { if (e.target.closest('a')) closeDrawer(); });
    if (!sections.length) $('mvTocBtn').style.display = 'none';

    // ---- Progress bar, back-to-top, current section ----
    const topBtn = $('mvTopBtn'), bar = $('mvProgress');
    topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    let ticking = false;
    function onScroll() {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = (max > 0 ? (window.scrollY / max) * 100 : 0) + '%';
        topBtn.classList.toggle('show', window.scrollY > 600);
        let current = -1;
        sections.forEach((s, i) => { if (s.getBoundingClientRect().top < 120) current = i; });
        links.forEach((a, i) => a.classList.toggle('active', i === current));
        ticking = false;
    }
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    onScroll();

    // ---- Click-to-zoom screenshots ----
    const box = $('mvLightbox'), big = box.querySelector('img'), cap = box.querySelector('.mv-cap');
    const imgs = Array.from(document.querySelectorAll('.manual-section img')).filter(img => !img.classList.contains('mv-noz'));
    let idx = -1;
    function show(i) {
        idx = (i + imgs.length) % imgs.length;
        const img = imgs[idx];
        const fig = img.closest('figure');
        const fc = fig && fig.querySelector('figcaption');
        big.src = img.currentSrc || img.src;
        big.alt = img.alt || '';
        cap.textContent = (fc ? fc.textContent : img.alt || '').trim();
        box.querySelectorAll('.mv-nav').forEach(b => b.style.display = imgs.length > 1 ? '' : 'none');
        box.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function hide() { box.classList.remove('open'); document.body.style.overflow = ''; idx = -1; }
    imgs.forEach((img, i) => img.addEventListener('click', () => show(i)));
    box.addEventListener('click', e => {
        if (e.target.closest('.mv-prev')) { e.stopPropagation(); show(idx - 1); return; }
        if (e.target.closest('.mv-next')) { e.stopPropagation(); show(idx + 1); return; }
        hide();
    });
    document.addEventListener('keydown', e => {
        if (idx >= 0) {
            if (e.key === 'Escape') hide();
            else if (e.key === 'ArrowLeft') show(idx - 1);
            else if (e.key === 'ArrowRight') show(idx + 1);
        } else if (e.key === 'Escape') {
            closeDrawer();
        }
    });
})();
</script>
