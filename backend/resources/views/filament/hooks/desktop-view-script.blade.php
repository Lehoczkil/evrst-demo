{{-- Persists & applies the "force desktop" viewport. Loaded in HEAD_START
     so the override happens before the layout paints — avoids a visible
     reflow when the user has already opted in. --}}

<script>
(function () {
    const KEY = 'evrst_force_desktop';
    const DESKTOP_VIEWPORT = 'width=1280, initial-scale=0.35, minimum-scale=0.2, maximum-scale=3.0';
    const MOBILE_VIEWPORT  = 'width=device-width, initial-scale=1';

    function ensureMeta() {
        let meta = document.querySelector('meta[name="viewport"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'viewport');
            document.head.appendChild(meta);
        }
        return meta;
    }

    function apply(force) {
        ensureMeta().setAttribute('content', force ? DESKTOP_VIEWPORT : MOBILE_VIEWPORT);
        document.documentElement.classList.toggle('evrst-force-desktop', !!force);

        // Reflect state in the topbar toggle, if rendered.
        const on  = document.getElementById('evrst-desktop-toggle-icon-on');
        const off = document.getElementById('evrst-desktop-toggle-icon-off');
        if (on)  on.classList.toggle('hidden', !force);
        if (off) off.classList.toggle('hidden', !!force);
    }

    function isOn() {
        try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
    }

    window.evrstToggleDesktopView = function () {
        const next = !isOn();
        try { localStorage.setItem(KEY, next ? '1' : '0'); } catch (e) {}
        apply(next);
    };

    // Apply current state ASAP and again once the topbar button exists so its icon syncs.
    apply(isOn());
    document.addEventListener('DOMContentLoaded', function () { apply(isOn()); });
    document.addEventListener('livewire:navigated', function () { apply(isOn()); });
})();
</script>
