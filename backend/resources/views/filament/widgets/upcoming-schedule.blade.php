@php
    $items = $this->getUpcoming();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin.widgets.upcoming') }}</x-slot>

        @if ($items->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.widgets.no_upcoming') }}</p>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($items as $item)
                    @php
                        $when = \Carbon\Carbon::parse($item['when']);
                    @endphp
                    <a
                        href="{{ $item['url'] }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-[10px] no-underline transition bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10"
                        style="border-left: 3px solid {{ $item['color'] }};"
                    >
                        <div class="text-center" style="min-width: 56px;">
                            <div class="text-[.65rem] uppercase font-bold text-gray-500 dark:text-gray-400">{{ $when->format('M') }}</div>
                            <div class="text-xl font-bold leading-none text-gray-900 dark:text-white">{{ $when->format('d') }}</div>
                            <div class="text-[.65rem] text-gray-500 dark:text-gray-400">{{ $when->format('H:i') }}</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[.65rem] uppercase tracking-wide font-bold" style="color: {{ $item['color'] }};">{{ $item['type'] }}</div>
                            <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $item['title'] }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
