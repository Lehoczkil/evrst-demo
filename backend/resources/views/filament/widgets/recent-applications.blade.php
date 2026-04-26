@php
    $apps = $this->getApplications();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Latest applications</x-slot>

        @if ($apps->isEmpty())
            <p style="color: rgb(100 116 139); font-size: .85rem;">No applications yet.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: .5rem;">
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
                        style="display: flex; gap: .65rem; padding: .55rem .65rem; border-radius: 10px; background: rgba(15,23,42,.04); text-decoration: none; transition: background .15s ease;"
                        onmouseover="this.style.background='rgba(15,23,42,.08)'"
                        onmouseout="this.style.background='rgba(15,23,42,.04)'"
                    >
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $statusColor }}; margin-top: .45rem;"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; color: rgb(15 23 42); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $app->name }}</div>
                            <div style="font-size: .7rem; color: rgb(100 116 139); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $app->department ?: $app->email }} · {{ $app->created_at?->diffForHumans() }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
