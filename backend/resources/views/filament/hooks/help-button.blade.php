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
    <button
        type="button"
        onclick="window.evrstOpenHelp(@js($key))"
        title="{{ __('admin.help.tooltip') }}"
        aria-label="{{ __('admin.help.tooltip') }}"
        style="
            display:inline-flex; align-items:center; justify-content:center;
            width: 24px; height: 24px; border-radius: 9999px;
            background: rgba(245, 158, 11, .18); color: rgb(146 64 14);
            border: 1px solid rgba(245, 158, 11, .55);
            font-size: .8rem; font-weight: 700; line-height: 1;
            cursor: help; transition: all .15s ease;
            vertical-align: middle;
        "
        onmouseover="this.style.background='rgb(245 158 11)'; this.style.color='white';"
        onmouseout="this.style.background='rgba(245, 158, 11, .18)'; this.style.color='rgb(146 64 14)';"
    >?</button>
</span>
@endif
