@php
    $tasks = $this->getTasks();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin.widgets.my_tasks') }}</x-slot>

        @if ($tasks->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.widgets.my_tasks_empty') }}</p>
        @else
            <div class="flex flex-col gap-2">
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
                        class="flex gap-2.5 px-2.5 py-2 rounded-[10px] no-underline transition bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10"
                    >
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $statusColor }}; margin-top: .45rem; flex-shrink: 0;"></span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $task->title }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
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
