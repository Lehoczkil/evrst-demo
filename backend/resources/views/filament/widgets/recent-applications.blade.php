@php
    $apps = $this->getApplications();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin.widgets.recent_apps') }}</x-slot>

        @if ($apps->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.widgets.recent_apps_empty') }}</p>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($apps as $app)
                    @php
                        $statusColor = match ($app->status) {
                            'PENDING'  => '#f59e0b',
                            'ACCEPTED' => '#10b981',
                            'REJECTED' => '#ef4444',
                            default    => '#94a3b8',
                        };
                    @endphp
                    <a
                        href="{{ $this->urlFor($app) }}"
                        class="flex gap-2.5 px-2.5 py-2 rounded-[10px] no-underline transition bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10"
                    >
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $statusColor }}; margin-top: .45rem; flex-shrink: 0;"></span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $app->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $app->department ?: $app->email }} · {{ $app->created_at?->diffForHumans() }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
