{{--
    Display mode (light / dark / follow the device) — runs in <head> BEFORE
    any stylesheet so the page never flashes white before turning dark.

    The choice is remembered per device in localStorage "pw_theme"
    ("light" | "dark"; missing = follow the device). It sets
    <html data-bs-theme="light|dark"> (Bootstrap 5.3's own dark mode switch,
    styled further by partials/_dark_theme_styles) and
    <html data-theme-mode="light|dark|system"> (what the user picked — the
    switcher button's icon reads this, so it is right before any JS runs).

    window.appTheme.set('light'|'dark'|'system') switches instantly, no
    reload; every change fires document event "app:themechange" (charts
    listen to it). Other open tabs follow via the "storage" event, and in
    "system" mode the page follows the device when it changes (e.g. a phone
    going dark in the evening). Printing always uses the light colours.

    Included by layouts/app, labor/layout, layouts/guest and errors/minimal.
--}}
<script>
    (function () {
        var KEY = 'pw_theme';
        var root = document.documentElement;
        var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

        function stored() {
            try {
                var v = localStorage.getItem(KEY);
                return v === 'light' || v === 'dark' ? v : 'system';
            } catch (e) {
                return 'system';
            }
        }
        function resolve(mode) {
            return mode === 'system' ? (mq && mq.matches ? 'dark' : 'light') : mode;
        }
        function apply(mode) {
            root.setAttribute('data-bs-theme', resolve(mode));
            root.setAttribute('data-theme-mode', mode);
        }
        function announce() {
            try {
                document.dispatchEvent(new CustomEvent('app:themechange', { detail: { mode: current, theme: resolve(current) } }));
            } catch (e) {}
        }
        // Switch without every transition on the page animating at once.
        function switchTo(mode) {
            root.classList.add('theme-switching');
            apply(mode);
            announce();
            setTimeout(function () { root.classList.remove('theme-switching'); }, 60);
        }

        var current = stored();
        apply(current);

        window.appTheme = {
            mode: function () { return current; },
            theme: function () { return resolve(current); },
            set: function (mode) {
                if (mode !== 'light' && mode !== 'dark') mode = 'system';
                current = mode;
                try {
                    if (mode === 'system') localStorage.removeItem(KEY); else localStorage.setItem(KEY, mode);
                } catch (e) {}
                switchTo(mode);
            }
        };

        if (mq) {
            var onDeviceChange = function () { if (current === 'system') switchTo('system'); };
            if (mq.addEventListener) mq.addEventListener('change', onDeviceChange); else if (mq.addListener) mq.addListener(onDeviceChange);
        }
        window.addEventListener('storage', function (e) {
            if (e.key === KEY) { current = stored(); switchTo(current); }
        });

        // Chart.js draws on a canvas, so CSS can't recolour it: re-tint the
        // axis / grid / legend text of every chart on the page. A chart's
        // own light-mode colours are remembered and put back in light mode.
        function syncCharts() {
            var C = window.Chart;
            if (!C || !C.instances) return;
            var dark = resolve(current) === 'dark';
            var text = '#cbd5e1', grid = 'rgba(148, 163, 184, .18)';
            var list = Array.isArray(C.instances) ? C.instances : Object.keys(C.instances).map(function (k) { return C.instances[k]; });
            list.forEach(function (chart) {
                if (!chart || !chart.options) return;
                var o = chart.options, orig = chart.$pwThemeOrig || (chart.$pwThemeOrig = {});
                function set(obj, key, id, value) {
                    if (!obj) return;
                    if (!(id in orig)) orig[id] = obj[key];
                    obj[key] = dark ? value : orig[id];
                }
                Object.keys(o.scales || {}).forEach(function (k) {
                    var s = o.scales[k];
                    set(s.ticks, 'color', k + '.ticks', text);
                    set(s.grid, 'color', k + '.grid', grid);
                    set(s.title, 'color', k + '.title', text);
                    set(s.pointLabels, 'color', k + '.pointLabels', text);
                    set(s.angleLines, 'color', k + '.angleLines', grid);
                });
                var p = o.plugins || {};
                set(p.legend && p.legend.labels, 'color', 'legend', text);
                set(p.title, 'color', 'title', text);
                set(p.subtitle, 'color', 'subtitle', text);
                try { chart.update('none'); } catch (e) {}
            });
        }
        document.addEventListener('app:themechange', syncCharts);
        window.addEventListener('load', function () { if (resolve(current) === 'dark') setTimeout(syncCharts, 50); });

        // Paper is white: print in light colours, then switch back.
        var beforePrint = null;
        window.addEventListener('beforeprint', function () {
            beforePrint = root.getAttribute('data-bs-theme');
            root.setAttribute('data-bs-theme', 'light');
        });
        window.addEventListener('afterprint', function () {
            if (beforePrint) root.setAttribute('data-bs-theme', beforePrint);
            beforePrint = null;
        });
    })();
</script>
<style>
    .theme-switching, .theme-switching *, .theme-switching *::before, .theme-switching *::after { transition: none !important; }
</style>
