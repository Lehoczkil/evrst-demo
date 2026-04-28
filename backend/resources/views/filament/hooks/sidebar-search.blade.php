{{-- Mobile-only home for the global search.
     Filament mounts a single `GlobalSearch` Livewire component in the
     topbar; mounting a second one here silently fails to render an
     input. Instead, on viewports below the lg breakpoint we relocate
     the existing component (its `[wire:id]` wrapper, so Livewire keeps
     scoping `wire:model="search"` to the right component) into this
     slot, and put it back when the viewport grows. --}}
@if (filament()->isGlobalSearchEnabled())
<div class="evrst-sidebar-search" id="evrst-sidebar-search-slot"></div>
<script>
(() => {
    if (window.__evrstSidebarSearchInit) return;
    window.__evrstSidebarSearchInit = true;

    const BREAKPOINT = 1024;
    let homeParent = null;
    let homeAnchor = null;

    // Move the whole `[wire:id]` wrapper so Livewire keeps scoping
    // `wire:model="search"` to the GlobalSearch component, not to whatever
    // Livewire component happens to enclose the slot.
    const findSearch = () => {
        const inner = document.querySelector('.fi-global-search-field')
            || document.querySelector('.fi-global-search');
        if (!inner) return null;
        return inner.closest('[wire\\:id]') || inner.closest('.fi-global-search') || inner;
    };

    const place = () => {
        const slot = document.getElementById('evrst-sidebar-search-slot');
        const search = findSearch();
        if (!slot || !search) return;

        const isMobile = window.innerWidth < BREAKPOINT;
        const inSlot = slot.contains(search);

        if (isMobile && !inSlot) {
            homeParent = search.parentElement;
            homeAnchor = search.nextElementSibling;
            slot.appendChild(search);
        } else if (!isMobile && inSlot && homeParent) {
            homeParent.insertBefore(search, homeAnchor);
        }
    };

    const init = () => {
        place();
        window.addEventListener('resize', place, { passive: true });
        document.addEventListener('livewire:navigated', () => {
            homeParent = null;
            homeAnchor = null;
            place();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endif
