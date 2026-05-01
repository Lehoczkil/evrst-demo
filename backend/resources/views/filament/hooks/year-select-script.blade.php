{{-- Replace the `<input type="number">` year picker inside Filament's
     date-time picker panel with a real `<select>` dropdown. Filament
     re-creates the panel every time the trigger opens, so we observe
     the DOM and re-attach. The original input keeps its Alpine x-model
     bindings — we drive it via the select and dispatch the events
     Alpine listens for. --}}
<script>
(function () {
    // Fixed range: don't grow on scroll. Wide enough that no realistic
    // due date / event date falls outside it.
    const RANGE_MIN = 1970;
    const RANGE_MAX = new Date().getFullYear() + 30;

    function buildOptions() {
        const out = document.createDocumentFragment();
        for (let y = RANGE_MAX; y >= RANGE_MIN; y--) {
            const opt = document.createElement('option');
            opt.value = String(y);
            opt.textContent = String(y);
            out.appendChild(opt);
        }
        return out;
    }

    function clamp(year) {
        if (year < RANGE_MIN) return RANGE_MIN;
        if (year > RANGE_MAX) return RANGE_MAX;
        return year;
    }

    function ensureSelectFor(input) {
        if (input.dataset.evrstYearSwapped === '1') return;
        input.dataset.evrstYearSwapped = '1';

        const select = document.createElement('select');
        select.className = input.className + ' evrst-year-select';
        // Keep the input mounted (Alpine still talks to it) but hide it.
        input.style.display = 'none';
        input.setAttribute('aria-hidden', 'true');

        select.appendChild(buildOptions());
        const initial = clamp(parseInt(input.value || String(new Date().getFullYear()), 10) || new Date().getFullYear());
        select.value = String(initial);

        select.addEventListener('change', () => {
            input.value = select.value;
            // Alpine's x-model.debounce listens on `input`; fire both
            // for safety so the calendar grid refreshes immediately.
            input.dispatchEvent(new Event('input',  { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Block wheel-cycling on the closed <select> — that's what was
        // walking the year up/down past our list and triggering Alpine
        // to re-render with a fresh out-of-range value every tick.
        select.addEventListener('wheel', (e) => {
            if (document.activeElement === select) e.preventDefault();
        }, { passive: false });

        // Mirror programmatic changes back into the select (Alpine
        // reassigns the year when the user clicks a date in another
        // year). Clamp into our fixed range — never extend the list.
        const sync = () => {
            const raw = parseInt(input.value, 10);
            if (! Number.isFinite(raw)) return;
            const clamped = clamp(raw);
            const want = String(clamped);
            if (select.value !== want) select.value = want;
            if (raw !== clamped) {
                // Snap the input back into range so Alpine + the
                // calendar grid follow our clamp.
                input.value = want;
                input.dispatchEvent(new Event('input',  { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };
        new MutationObserver(sync).observe(input, { attributes: true, attributeFilter: ['value'] });
        input.addEventListener('input',  sync);
        input.addEventListener('change', sync);

        input.parentNode.insertBefore(select, input.nextSibling);
    }

    function scan(root) {
        const nodes = (root || document).querySelectorAll('input.fi-fo-date-time-picker-year-input');
        nodes.forEach(ensureSelectFor);
    }

    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const n of m.addedNodes) {
                if (n.nodeType !== 1) continue;
                if (n.matches && n.matches('input.fi-fo-date-time-picker-year-input')) {
                    ensureSelectFor(n);
                } else if (n.querySelectorAll) {
                    scan(n);
                }
            }
        }
    });

    function start() {
        scan();
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
    document.addEventListener('livewire:navigated', () => scan());
})();
</script>

<style>
.evrst-year-select {
    appearance: auto;
    -webkit-appearance: auto;
    background: var(--evrst-surface-solid, #11141a) !important;
    color: var(--evrst-text, #e8ebf2) !important;
    border: 1px solid var(--evrst-border, rgba(255,255,255,.16)) !important;
    border-radius: var(--evrst-r-sm, 4px) !important;
    padding: 2px 6px !important;
    font-family: var(--evrst-mono);
    font-size: 0.78rem;
    height: auto !important;
    min-width: 5ch;
}
.evrst-year-select:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 2px var(--evrst-accent-soft, rgba(251,191,36,.25));
}
</style>
