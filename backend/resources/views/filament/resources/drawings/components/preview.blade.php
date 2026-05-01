<div style="
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border-radius: 6px;
    padding: 1rem;
    width: 100%;
    height: calc(100dvh - 11rem);
    min-height: 480px;
">
    @if ($drawing->url)
        <img
            src="{{ $drawing->url }}"
            alt="{{ $drawing->title }}"
            style="
                display: block;
                max-width: 100%;
                max-height: 100%;
                width: auto;
                height: auto;
                object-fit: contain;
                border-radius: 4px;
            "
        />
    @else
        <p style="color: #475569; font-size: .9rem; margin: 0;">
            {{ __('admin.drawing.empty_heading') }}
        </p>
    @endif
</div>
