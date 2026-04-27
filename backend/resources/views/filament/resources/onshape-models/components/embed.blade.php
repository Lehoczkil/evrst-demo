{{-- Onshape blocks third-party iframe embedding via their CSP
     frame-ancestors directive — only *.onshape.com and a couple of
     partner domains may frame cad.onshape.com. We surface the document
     metadata + a prominent "Open in Onshape" button instead, so the
     edit flow lives on cad.onshape.com in a new tab. --}}

@php
    $url = $model?->embed_url;
@endphp

@if ($url)
    <div
        class="bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border dark:border-white/10"
        style="
            display: flex; flex-direction: column; align-items: center;
            gap: 1rem;
            padding: 2.5rem 1.5rem;
            border-radius: .75rem;
            border: 1px solid rgba(15,23,42,.08);
            text-align: center;
        "
    >
        <div style="
            width: 72px; height: 72px;
            border-radius: 9999px;
            background: rgba(245, 158, 11, .15);
            display: inline-flex; align-items: center; justify-content: center;
            color: rgb(245 158 11);
        ">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
        </div>

        <div style="font-size: 1rem; font-weight: 600;">{{ $model->title }}</div>

        @if ($model->description)
            <p style="font-size: .85rem; max-width: 32rem;" class="text-gray-600 dark:text-gray-300">
                {{ $model->description }}
            </p>
        @endif

        <a
            href="{{ $url }}"
            target="_blank"
            rel="noopener noreferrer"
            style="
                display: inline-flex; align-items: center; gap: .5rem;
                background: rgb(245 158 11); color: rgb(120 53 15);
                border: 0; border-radius: .5rem;
                padding: .65rem 1.1rem;
                font-size: .9rem; font-weight: 600;
                text-decoration: none;
                transition: background-color .12s ease;
            "
            onmouseover="this.style.background='rgb(252 211 77)';"
            onmouseout="this.style.background='rgb(245 158 11)';"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                <polyline points="15 3 21 3 21 9"/>
                <line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
            {{ __('admin.onshape.view_in_onshape') }}
        </a>

        <p style="font-size: .72rem; max-width: 28rem;" class="text-gray-500 dark:text-gray-400">
            {{ __('admin.onshape.embed_unavailable') }}
        </p>

        <dl style="
            display: grid; grid-template-columns: auto 1fr; gap: .25rem 1rem;
            font-size: .72rem; margin-top: .5rem;
            font-family: ui-monospace, SFMono-Regular, monospace;
        " class="text-gray-500 dark:text-gray-400">
            <dt>document</dt><dd>{{ $model->document_id }}</dd>
            <dt>workspace</dt><dd>{{ $model->workspace_id }}</dd>
            @if ($model->element_id)
                <dt>element</dt><dd>{{ $model->element_id }}</dd>
            @endif
        </dl>
    </div>
@else
    <p class="text-gray-500 dark:text-gray-400" style="font-size: .85rem;">
        {{ __('admin.onshape.no_link') }}
    </p>
@endif
