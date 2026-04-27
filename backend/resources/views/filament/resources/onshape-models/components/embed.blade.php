{{-- Onshape iframe embed. The iframe loads cad.onshape.com directly
     from the user's browser — our server is never a proxy and pays
     no bandwidth or CPU cost for the 3D viewer. --}}

@php
    $url = $model?->embed_url;
@endphp

@if ($url)
    <div style="width:100%; border-radius:.75rem; overflow:hidden; border:1px solid rgba(15,23,42,.12);">
        <iframe
            src="{{ $url }}"
            width="100%"
            height="600"
            style="display:block; border:0;"
            allow="fullscreen; clipboard-read; clipboard-write"
            referrerpolicy="strict-origin-when-cross-origin"
            loading="lazy"
            title="{{ $model->title }}"
        ></iframe>
    </div>
    <p style="font-size:.72rem; color:rgb(100 116 139); margin-top:.5rem;">
        {{ __('admin.onshape.embed_help') }}
    </p>
@else
    <p style="color:rgb(100 116 139); font-size:.85rem;">
        {{ __('admin.onshape.no_link') }}
    </p>
@endif
