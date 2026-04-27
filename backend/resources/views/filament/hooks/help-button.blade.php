{{-- Per-page help. Renders a small "?" button that gets moved into
     the .fi-header-heading element by x-init, and dispatches into the
     shared modal mounted at body end (see help-modal.blade.php). --}}

@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    $key = preg_replace('/^filament\.admin\./', '', $routeName);
    $pages = (array) trans('admin.help.pages');
    $entry = $pages[$key] ?? null;
    $hasHelp = is_array($entry) && isset($entry['title'], $entry['body']);
@endphp

@if ($hasHelp)
<span
    x-data
    x-init="
        const h1 = $el.parentElement?.querySelector('.fi-header-heading');
        if (h1 && !h1.contains($el)) h1.appendChild($el);
    "
    style="display:inline-flex; align-items:center; vertical-align: middle; margin-left: .55rem;"
>
    {{-- The button inherits the heading's text colour via currentColor so
         it lands as white in dark mode and dark slate in light mode
         without a per-theme override. --}}
    <button
        type="button"
        onclick="window.evrstOpenHelp(@js($key))"
        title="{{ __('admin.help.tooltip') }}"
        aria-label="{{ __('admin.help.tooltip') }}"
        style="
            display:inline-flex; align-items:center; justify-content:center;
            width: 16px; height: 16px; border-radius: 9999px;
            background: transparent; color: currentColor;
            border: 1px solid currentColor;
            font-size: .6rem; font-weight: 700; line-height: 1;
            opacity: .55; cursor: help; transition: opacity .12s ease, background .12s ease;
            vertical-align: middle;
        "
        onmouseover="this.style.opacity='1'; this.style.background='color-mix(in srgb, currentColor 18%, transparent)';"
        onmouseout="this.style.opacity='.55'; this.style.background='transparent';"
    >?</button>
</span>
@endif
