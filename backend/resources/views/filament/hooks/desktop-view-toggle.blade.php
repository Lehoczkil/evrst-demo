{{-- Topbar button that toggles a "desktop" viewport on small screens.

     The mechanism: when toggled on we replace the <meta name="viewport">
     content with a fixed pixel width, which makes mobile browsers render
     the admin at desktop size and lets the user pinch-zoom around it.
     The choice is persisted in localStorage so navigation keeps the mode. --}}

<button
    type="button"
    id="evrst-desktop-toggle"
    title="Toggle desktop view"
    aria-label="Toggle desktop view"
    class="fi-icon-btn relative flex shrink-0 items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 hover:bg-gray-100 dark:hover:bg-white/5 fi-color-gray fi-size-md fi-icon-btn-size-md h-9 w-9"
    onclick="window.evrstToggleDesktopView && window.evrstToggleDesktopView()"
>
    <svg id="evrst-desktop-toggle-icon-on" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 hidden">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
    </svg>
    <svg id="evrst-desktop-toggle-icon-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
    </svg>
</button>
