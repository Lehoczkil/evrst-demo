{{-- Per-page help. Renders a small "?" button next to the page heading;
     clicking it opens a modal whose copy is keyed by the current route
     name. If no translation exists for the current route the button is
     hidden, so this hook is safe to register globally. --}}

@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    // Strip the panel prefix once: "filament.admin.resources.tasks.index"
    // becomes "resources.tasks.index" so the lang file stays compact.
    $key = preg_replace('/^filament\.admin\./', '', $routeName);
    // The lang keys are literal dotted strings (e.g. "resources.cms.events.index")
    // — Laravel's __() would otherwise traverse them as nested paths.
    $pages = (array) trans('admin.help.pages');
    $entry = $pages[$key] ?? null;
    $hasHelp = is_array($entry) && isset($entry['title'], $entry['body']);
    $title = $hasHelp ? $entry['title'] : null;
    $body  = $hasHelp ? $entry['body']  : null;
@endphp

@if ($hasHelp)
<span
    x-data="{ open: false }"
    x-init="
        const h1 = $el.parentElement?.querySelector('.fi-header-heading');
        if (h1 && !h1.contains($el)) h1.appendChild($el);
    "
    @keydown.escape.window="open = false"
    style="display:inline-flex; align-items:center; vertical-align: middle; margin-left: .55rem;"
>
    <button
        type="button"
        @click="open = true"
        title="{{ __('admin.help.tooltip') }}"
        aria-label="{{ __('admin.help.tooltip') }}"
        style="
            display:inline-flex; align-items:center; justify-content:center;
            width: 26px; height: 26px; border-radius: 9999px;
            background: rgba(245, 158, 11, .18); color: rgb(146 64 14);
            border: 1px solid rgba(245, 158, 11, .55);
            font-size: .85rem; font-weight: 700; line-height: 1;
            cursor: help; transition: all .15s ease;
            vertical-align: middle;
        "
        onmouseover="this.style.background='rgb(245 158 11)'; this.style.color='white';"
        onmouseout="this.style.background='rgba(245, 158, 11, .14)'; this.style.color='rgb(180 83 9)';"
    >?</button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity
            @click.self="open = false"
            style="
                position: fixed; inset: 0; z-index: 70;
                background: rgba(15,23,42,.55);
                display: flex; align-items: center; justify-content: center;
                padding: 1rem; backdrop-filter: blur(4px);
            "
        >
            <div
                style="
                    background: white; color: rgb(15 23 42);
                    border-radius: 14px;
                    width: 100%; max-width: 540px;
                    box-shadow: 0 16px 60px rgba(15,23,42,.35);
                    overflow: hidden;
                "
                class="dark:!bg-gray-900 dark:!text-gray-100"
            >
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(15,23,42,.08); display:flex; align-items:center; justify-content:space-between;">
                    <div style="font-size: 1rem; font-weight: 700;">{{ $title }}</div>
                    <button type="button" @click="open = false" style="background:transparent; border:0; cursor:pointer; font-size: 1.1rem; color: rgb(100 116 139);">✕</button>
                </div>
                <div style="padding: 1.25rem; font-size: .9rem; line-height: 1.55;">
                    {!! $body !!}
                </div>
                <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(15,23,42,.08); display:flex; justify-content:flex-end; background: rgba(15,23,42,.02);">
                    <button
                        type="button"
                        @click="open = false"
                        style="background: rgb(245 158 11); color: rgb(120 53 15); border: 0; border-radius: .5rem; padding: .45rem .85rem; font-size: .85rem; font-weight: 600; cursor: pointer;"
                    >{{ __('admin.help.close') }}</button>
                </div>
            </div>
        </div>
    </template>
</span>
@endif
