{{-- Global help modal + sidebar injector. Renders once at the bottom
     of the body and listens for window 'help-open' events; both the
     page-heading "?" button and the per-nav-item "?" buttons dispatch
     into it, so we only ever paint one modal in the DOM. --}}

@php
    $pages = (array) trans('admin.help.pages');
    // Resolve the help keys to the actual /admin/* URL so the JS injector
    // can match each sidebar link to its help entry.
    $urlMap = [];
    foreach ($pages as $key => $entry) {
        if (! is_array($entry) || ! isset($entry['title'])) continue;
        $routeName = 'filament.admin.' . $key;
        if (! \Illuminate\Support\Facades\Route::has($routeName)) continue;
        try {
            $urlMap[$key] = route($routeName);
        } catch (\Throwable) {
            // Routes that need parameters (edit/{record}) aren't injectable.
        }
    }
@endphp

<script>
    window.__evrstHelp = @json($pages);
    // { helpKey: '/admin/cms/events' }
    window.__evrstHelpUrls = @json($urlMap);
    window.__evrstHelpTooltip = @json(__('admin.help.tooltip'));
</script>

<div
    x-data="{
        open: false,
        title: '',
        body: '',
    }"
    @help-open.window="title = $event.detail.title; body = $event.detail.body; open = true;"
    @keydown.escape.window="open = false"
    x-cloak
>
    {{-- Full-viewport backdrop. Fixed to the viewport so any ancestor's
         transform / filter doesn't displace it; the modal itself is
         positioned with translate(-50%, -50%) so it stays dead-centre
         regardless of flex quirks. --}}
    <div
        x-show="open"
        x-transition.opacity
        @click.self="open = false"
        style="
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh;
            z-index: 9999;
            background: rgba(15,23,42,.55);
            backdrop-filter: blur(4px);
        "
    >
        <div
            @click.stop
            style="
                position: fixed;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                background: white; color: rgb(15 23 42);
                border-radius: 14px;
                width: calc(100vw - 2rem); max-width: 560px;
                max-height: calc(100vh - 4rem);
                box-shadow: 0 16px 60px rgba(15,23,42,.35);
                display: flex; flex-direction: column;
                overflow: hidden;
                z-index: 10000;
            "
            class="dark:!bg-gray-900 dark:!text-gray-100 dark:!border dark:!border-white/10"
        >
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(15,23,42,.08); display:flex; align-items:center; justify-content:space-between; flex: 0 0 auto;">
                <div style="font-size: 1rem; font-weight: 700;" x-text="title"></div>
                <button type="button" @click="open = false" style="background:transparent; border:0; cursor:pointer; font-size: 1.1rem; color: rgb(100 116 139); padding: 0;">✕</button>
            </div>
            <div style="padding: 1.25rem; font-size: .9rem; line-height: 1.55; overflow-y: auto; flex: 1 1 auto;" x-html="body"></div>
            <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(15,23,42,.08); display:flex; justify-content:flex-end; background: rgba(15,23,42,.02); flex: 0 0 auto;">
                <button type="button" @click="open = false" style="background: rgb(245 158 11); color: rgb(120 53 15); border: 0; border-radius: .5rem; padding: .45rem .85rem; font-size: .85rem; font-weight: 600; cursor: pointer;">{{ __('admin.help.close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Open the modal for a given help key. Resolves title/body from the
    // server-injected map and dispatches a window event so the Alpine
    // root above renders.
    window.evrstOpenHelp = function (key) {
        const entry = window.__evrstHelp?.[key];
        if (!entry) return;
        window.dispatchEvent(new CustomEvent('help-open', {
            detail: { title: entry.title, body: entry.body },
        }));
    };

    function injectSidebarIcons() {
        const urls = window.__evrstHelpUrls || {};
        if (!Object.keys(urls).length) return;

        // Build a path -> key index for fast matches.
        const pathIndex = {};
        for (const [key, fullUrl] of Object.entries(urls)) {
            try {
                const u = new URL(fullUrl, window.location.origin);
                pathIndex[u.pathname] = key;
            } catch (e) {}
        }

        // Filament 4's actual class on the link element is fi-sidebar-item-btn.
        document.querySelectorAll('a.fi-sidebar-item-btn').forEach((a) => {
            if (a.dataset.evrstHelpInjected) return;
            const href = a.getAttribute('href');
            if (!href) return;
            let path;
            try { path = new URL(href, window.location.origin).pathname; } catch (e) { return; }
            const key = pathIndex[path];
            if (!key) return;

            a.dataset.evrstHelpInjected = '1';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', window.__evrstHelpTooltip);
            btn.title = window.__evrstHelpTooltip;
            btn.textContent = '?';
            btn.style.cssText = [
                'margin-left:auto', 'flex-shrink:0',
                'width:14px', 'height:14px',
                'border-radius:9999px',
                'border:1px solid rgba(255,255,255,.4)',
                'background:transparent',
                'color:#ffffff',
                'font-size:.55rem', 'font-weight:700', 'line-height:1',
                'cursor:help', 'padding:0',
                'display:inline-flex', 'align-items:center', 'justify-content:center',
                'opacity:.55', 'transition:opacity .12s ease, background .12s ease, border-color .12s ease',
            ].join(';');
            btn.addEventListener('mouseenter', () => {
                btn.style.opacity = '1';
                btn.style.background = 'rgba(255,255,255,.18)';
                btn.style.borderColor = 'rgba(255,255,255,.7)';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.opacity = '.55';
                btn.style.background = 'transparent';
                btn.style.borderColor = 'rgba(255,255,255,.4)';
            });
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.evrstOpenHelp(key);
            });
            a.appendChild(btn);
        });
    }

    document.addEventListener('DOMContentLoaded', injectSidebarIcons);
    document.addEventListener('livewire:navigated', injectSidebarIcons);
    // Sidebar can paint after Livewire's first render — re-scan a couple of times.
    setTimeout(injectSidebarIcons, 250);
    setTimeout(injectSidebarIcons, 800);
})();
</script>
