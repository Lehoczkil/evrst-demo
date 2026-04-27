@php
    $items = $this->getUpcoming();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin.widgets.upcoming') }}</x-slot>

        @if ($items->isEmpty())
            <p style="color: rgb(100 116 139); font-size: .85rem;">{{ __('admin.widgets.no_upcoming') }}</p>
        @else
            <div style="display: flex; flex-direction: column; gap: .55rem;">
                @foreach ($items as $item)
                    @php
                        $when = \Carbon\Carbon::parse($item['when']);
                    @endphp
                    <a
                        href="{{ $item['url'] }}"
                        style="display: flex; align-items: center; gap: .8rem; padding: .55rem .75rem; border-radius: 10px; text-decoration: none; background: rgba(15,23,42,.04); transition: background .15s ease; border-left: 3px solid {{ $item['color'] }};"
                        onmouseover="this.style.background='rgba(15,23,42,.08)'"
                        onmouseout="this.style.background='rgba(15,23,42,.04)'"
                    >
                        <div style="min-width: 56px; text-align: center;">
                            <div style="font-size: .65rem; text-transform: uppercase; color: rgb(100 116 139); font-weight: 700;">{{ $when->format('M') }}</div>
                            <div style="font-size: 1.2rem; font-weight: 700; line-height: 1;">{{ $when->format('d') }}</div>
                            <div style="font-size: .65rem; color: rgb(100 116 139);">{{ $when->format('H:i') }}</div>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: .65rem; text-transform: uppercase; letter-spacing: .04em; color: {{ $item['color'] }}; font-weight: 700;">{{ $item['type'] }}</div>
                            <div style="font-weight: 600; color: rgb(15 23 42); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['title'] }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
