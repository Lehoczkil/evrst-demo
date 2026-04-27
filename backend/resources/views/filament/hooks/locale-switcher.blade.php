{{-- Two pill buttons (EN / HU) with a small gap between them. The active
     locale gets the brand amber/orange fill; the other one is a flat
     ghost button. Posting either form persists the choice. --}}

@php
    $current = strtolower((string) app()->getLocale());
    $locales = [
        'en' => 'EN',
        'hu' => 'HU',
    ];
@endphp

<div
    style="display:inline-flex; align-items:center; gap:.4rem;"
    role="group"
    aria-label="{{ __('admin.locale.tooltip') }}"
>
    @foreach ($locales as $code => $label)
        @php $active = ($code === $current); @endphp
        <form method="POST" action="{{ route('admin.locale.set') }}" style="margin:0; line-height:0;">
            @csrf
            <input type="hidden" name="locale" value="{{ $code }}">
            <input type="hidden" name="redirect" value="{{ url()->current() }}">
            <button
                type="submit"
                title="{{ __('admin.locale.tooltip') }}"
                aria-pressed="{{ $active ? 'true' : 'false' }}"
                style="
                    display:inline-flex; align-items:center; justify-content:center;
                    height: 28px; min-width: 38px;
                    padding: 0 .65rem;
                    border-radius: 9999px;
                    font-size: .68rem; font-weight: 700;
                    letter-spacing: .08em;
                    cursor: pointer;
                    transition: background-color .15s ease, color .15s ease, border-color .15s ease;
                    {{ $active
                        ? 'background: rgb(245 158 11); color: rgb(120 53 15); border: 1px solid rgb(217 119 6); box-shadow: 0 1px 2px rgba(217, 119, 6, .35);'
                        : 'background: transparent; color: rgb(100 116 139); border: 1px solid rgba(15, 23, 42, .12);' }}
                "
                onmouseover="if (this.getAttribute('aria-pressed') === 'false') { this.style.color = 'rgb(245 158 11)'; this.style.borderColor = 'rgb(245 158 11)'; }"
                onmouseout="if (this.getAttribute('aria-pressed') === 'false') { this.style.color = 'rgb(100 116 139)'; this.style.borderColor = 'rgba(15, 23, 42, .12)'; }"
            >{{ $label }}</button>
        </form>
    @endforeach
</div>
