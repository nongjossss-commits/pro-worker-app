{{--
    <x-card-scroll-frame>
        …card list markup…
    </x-card-scroll-frame>

    Wraps a long list of cards in a viewport-height scroll container so
    the page's top toolbar / filters stay visible while the operator
    scrolls through cards. Mouse wheel over the frame scrolls the
    frame; wheel outside the frame scrolls the page normally.

    Props:
      • topOffset  — pixels reserved for header + filters above the frame.
                     Default 240px works when the filter card takes ~200-220px
                     under the sidebar toggle. Pages with fatter headers can
                     override to 300, 340, etc.
      • paddingX   — inner padding on left/right in rem.
      • minHeight  — floor height so the frame doesn't collapse on empty
                     result sets.
      • mobileEnabled — usually false; on portrait phones a plain page scroll
                        feels better than a small inner window.

    All styles / script are inlined here so the component is self-contained
    and can be dropped into any page without editing the shared layout.
--}}
@props([
    'topOffset' => 240,
    'maxHeight' => null,     // e.g., "500px" or "60vh" — when set, wins over topOffset
    'paddingX' => 0.5,
    'minHeight' => 200,
    'mobileEnabled' => false,
    'id' => null,
    'variant' => 'outer',    // "outer" (page-level) or "inner" (nested inside a card)
])

@php
    $frameId = $id ?: 'csf-' . uniqid();
    // maxHeight literal overrides the calc(100vh - topOffset) expression.
    $maxHeightExpr = $maxHeight
        ? $maxHeight
        : 'calc(100vh - ' . (int) $topOffset . 'px)';
@endphp

<div class="card-scroll-frame-outer position-relative csf-variant-{{ $variant }}">
    <div id="{{ $frameId }}"
         class="card-scroll-frame"
         data-mobile-enabled="{{ $mobileEnabled ? '1' : '0' }}"
         style="--csf-max-height: {{ $maxHeightExpr }};
                --csf-padding-x: {{ (float) $paddingX }}rem;
                --csf-min-height: {{ (int) $minHeight }}px;">
        {{ $slot }}
    </div>

    <button type="button"
            class="btn btn-primary rounded-circle shadow card-scroll-back-top-btn"
            data-target="{{ $frameId }}"
            title="{{ __('กลับไปการ์ดใบแรก') }}"
            aria-label="{{ __('กลับไปการ์ดใบแรก') }}">
        <i class="bi bi-arrow-up-short" style="font-size: 1.3rem; line-height: 1;"></i>
    </button>
</div>

@once
    <style>
        /* Scoped card scroll frame — desktop + tablet only by default. */
        .card-scroll-frame-outer {
            /* keep the button positioning contained here */
            position: relative;
        }
        .card-scroll-frame {
            max-height: var(--csf-max-height, calc(100vh - 240px));
            min-height: var(--csf-min-height, 200px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-left: var(--csf-padding-x, 0.5rem);
            padding-right: var(--csf-padding-x, 0.5rem);
            border: 1px dashed #e5e7eb;
            border-radius: 10px;
            background: #fdfdfd;
            scroll-behavior: smooth;
            box-shadow: inset 0 -4px 8px -6px rgba(0,0,0,0.08),
                        inset 0 4px 8px -6px rgba(0,0,0,0.08);
            /* soft anchor so keyboard PgDn doesn't overshoot */
            scroll-padding-top: 6px;
            scroll-padding-bottom: 6px;
        }

        /* Custom scrollbar (WebKit/Blink) */
        .card-scroll-frame::-webkit-scrollbar { width: 10px; }
        .card-scroll-frame::-webkit-scrollbar-track { background: transparent; }
        .card-scroll-frame::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 5px;
            border: 2px solid #fdfdfd;
        }
        .card-scroll-frame::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Firefox scrollbar */
        .card-scroll-frame {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        /* Inner variant — a bit tighter, blends into the card body */
        .csf-variant-inner .card-scroll-frame {
            border: 1px dashed #d1d5db;
            background: #ffffff;
        }

        /* Back-to-first-card floating button */
        .card-scroll-back-top-btn {
            position: absolute;
            right: 18px;
            bottom: 18px;
            width: 44px;
            height: 44px;
            padding: 0;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10;
            opacity: 0.88;
            transition: opacity 0.2s;
        }
        .card-scroll-back-top-btn:hover { opacity: 1; }
        .card-scroll-back-top-btn.is-visible { display: flex; }

        /* Mobile fallback: on portrait phones the frame collapses so the
           page scrolls naturally — a shrunken inner window feels cramped. */
        @media (max-width: 767px) {
            .card-scroll-frame[data-mobile-enabled="0"] {
                max-height: none;
                min-height: 0;
                overflow: visible;
                border: none;
                background: transparent;
                box-shadow: none;
                padding-left: 0;
                padding-right: 0;
            }
            .card-scroll-frame[data-mobile-enabled="0"] ~ .card-scroll-back-top-btn {
                display: none !important;
            }
        }
    </style>

    <script>
        // Wire every card-scroll-frame on the page. We keep a Set of already-
        // wired frames so we can also be called from AJAX reload handlers
        // (e.g. after an accordion's employee list fetches) without double-
        // binding the scroll listener.
        window.wireCardScrollFrames = window.wireCardScrollFrames || function (root) {
            const scope = root || document;
            const frames = scope.querySelectorAll('.card-scroll-frame-outer');
            frames.forEach(function (outer) {
                const frame = outer.querySelector('.card-scroll-frame');
                const btn = outer.querySelector('.card-scroll-back-top-btn');
                if (!frame || !btn) return;
                if (frame.dataset.csfWired === '1') return; // already handled

                const isMobile = window.matchMedia('(max-width: 767px)').matches;
                const mobileEnabled = frame.dataset.mobileEnabled === '1';
                if (isMobile && !mobileEnabled) {
                    frame.dataset.csfWired = '1';
                    return;
                }

                let ticking = false;
                function onScroll() {
                    if (ticking) return;
                    ticking = true;
                    window.requestAnimationFrame(function () {
                        btn.classList.toggle('is-visible', frame.scrollTop > 200);
                        ticking = false;
                    });
                }
                frame.addEventListener('scroll', onScroll, { passive: true });

                btn.addEventListener('click', function () {
                    frame.scrollTo({ top: 0, behavior: 'smooth' });
                });

                frame.dataset.csfWired = '1';
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.wireCardScrollFrames();
        });

        // Auto-wire frames that appear later. MutationObserver on document
        // body catches accordion-loaded employee lists / any dynamic insert.
        if (typeof MutationObserver !== 'undefined') {
            const _csfObserver = new MutationObserver(function (mutations) {
                for (const m of mutations) {
                    for (const node of m.addedNodes) {
                        if (node.nodeType !== 1) continue;
                        if (node.matches && node.matches('.card-scroll-frame-outer')) {
                            window.wireCardScrollFrames(node.parentNode || document);
                        } else if (node.querySelector && node.querySelector('.card-scroll-frame-outer')) {
                            window.wireCardScrollFrames(node);
                        }
                    }
                }
            });
            document.addEventListener('DOMContentLoaded', function () {
                _csfObserver.observe(document.body, { childList: true, subtree: true });
            });
        }
    </script>
@endonce
