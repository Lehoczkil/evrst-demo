{{-- Topbar shortcut: any signed-in user with bugs.report can file a
     bug report from anywhere in the admin. Mirrors the locale-switcher
     / desktop-view-toggle pattern (USER_MENU_BEFORE renderHook). --}}

@php
    $canReport = auth()->user()?->can(\App\Auth\Perm::BUGS_REPORT) ?? false;
@endphp

@if ($canReport)
<a
    href="{{ route('filament.admin.resources.bug-reports.create') }}"
    title="{{ __('admin.bugs.report_button') }}"
    aria-label="{{ __('admin.bugs.report_button') }}"
    style="
        display:inline-flex; align-items:center; justify-content:center;
        width: 32px; height: 32px;
        border-radius: 9999px;
        background: transparent;
        color: var(--evrst-text-muted, rgb(100 116 139));
        border: 1px solid transparent;
        cursor: pointer;
        transition: color .15s ease, background-color .15s ease, border-color .15s ease;
    "
    onmouseover="this.style.color='var(--evrst-text, rgb(15, 23, 42))'; this.style.background='color-mix(in srgb, currentColor 10%, transparent)';"
    onmouseout="this.style.color='var(--evrst-text-muted, rgb(100, 116, 139))'; this.style.background='transparent';"
>
    {{-- Heroicon outline `bug-ant`, sized 20px inside the 32px hit area. --}}
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke-width="1.5" stroke="currentColor"
         style="width: 20px; height: 20px;" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0 1 12 12.75Zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 0 1-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75V3.75M3.79 14.19c-.39.18-.781.371-1.171.572m18.764 0c-.39-.2-.781-.392-1.171-.572m-15.422 0a23.91 23.91 0 0 0-1.152 6.06M3.79 14.19A24.143 24.143 0 0 1 12 12.75c2.883 0 5.646.508 8.207 1.44M16.5 6.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
    </svg>
</a>
@endif
