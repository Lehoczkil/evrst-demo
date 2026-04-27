@php
    $tasks = $this->getTasks();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin.widgets.my_tasks') }}</x-slot>

        @if ($tasks->isEmpty())
            <p style="color: rgb(100 116 139); font-size: .85rem;">{{ __('admin.widgets.my_tasks_empty') }}</p>
        @else
            <div style="display: flex; flex-direction: column; gap: .5rem;">
                @foreach ($tasks as $task)
                    @php
                        $statusColor = match ($task->status) {
                            \App\Models\Task::STATUS_TODO => '#94a3b8',
                            \App\Models\Task::STATUS_IN_PROGRESS => '#f59e0b',
                            \App\Models\Task::STATUS_TESTING => '#0ea5e9',
                            default => '#94a3b8',
                        };
                    @endphp
                    <a
                        href="{{ $this->urlFor($task) }}"
                        style="display: flex; gap: .65rem; padding: .55rem .65rem; border-radius: 10px; background: rgba(15,23,42,.04); text-decoration: none; transition: background .15s ease;"
                        onmouseover="this.style.background='rgba(15,23,42,.08)'"
                        onmouseout="this.style.background='rgba(15,23,42,.04)'"
                    >
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $statusColor }}; margin-top: .45rem;"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; color: rgb(15 23 42); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $task->title }}</div>
                            <div style="font-size: .7rem; color: rgb(100 116 139);">
                                {{ __('admin.tasks.statuses.' . $task->status) }}
                                @if ($task->due_date)
                                    · {{ __('admin.tasks.due_date') }}: {{ $task->due_date->format('d M') }}
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
