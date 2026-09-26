{{--
    "This page is still open" ping for App\Http\Middleware\EnsureBrowserSessionAlive.
    Once a minute, and whenever the tab becomes visible again. If the server
    has already ended the session (browser/app was closed or left in the
    background too long) the page goes straight to the login screen.
    Included by layouts/app.blade.php and labor/layout.blade.php.
--}}
@auth
<script>
(function () {
    if (window.__sessionHeartbeat) return;
    window.__sessionHeartbeat = true;

    const url = @json(route('session.heartbeat'));
    const loginUrl = @json(route('login'));
    let busy = false;

    function beat() {
        if (busy) return;
        busy = true;
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (res) {
            if (res.status === 401 || res.status === 419) {
                window.location.href = loginUrl;
            }
        }).catch(function () { /* offline — try again next tick */ })
          .finally(function () { busy = false; });
    }

    setInterval(beat, 60 * 1000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') beat();
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) beat();
    });
})();
</script>
@endauth
