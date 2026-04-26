@php
    /** @var array<int, array{key: string, label: string, accent: string, tasks: array}> $columns */
    $columns = $this->getColumns();
    $canEdit = $this->canEditTasks();

    $initials = function (string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    // Soft, deterministic avatar colour per name — no DB roundtrip.
    $avatarBg = function (string $name): string {
        $palette = ['#fca5a5','#fdba74','#fcd34d','#86efac','#67e8f9','#93c5fd','#c4b5fd','#f9a8d4'];
        $hash = 0;
        foreach (str_split($name) as $ch) { $hash = ($hash * 31 + ord($ch)) % 1000003; }
        return $palette[$hash % count($palette)];
    };
@endphp

<x-filament-panels::page>
    @php
        $userOptions = $this->getUserOptions();
        $statuses = \App\Models\Task::statusLabels();
    @endphp
    <div style="display: flex; flex-wrap: wrap; gap: .5rem .75rem; align-items: center; padding-bottom: .75rem; margin-bottom: .75rem; border-bottom: 1px solid rgba(15,23,42,.08);">
        <input
            type="search"
            wire:model.live.debounce.300ms="filterSearch"
            placeholder="Search title / description"
            style="flex: 1 1 220px; min-width: 200px; padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(15,23,42,.12); font-size: .8rem;"
        >
        <select wire:model.live="filterStatus" style="padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(15,23,42,.12); font-size: .8rem;">
            <option value="">All statuses</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterSupervisorId" style="padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(15,23,42,.12); font-size: .8rem;">
            <option value="">Any supervisor</option>
            @foreach ($userOptions as $u)
                <option value="{{ $u['id'] }}">👤 {{ $u['name'] }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterAssigneeId" style="padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(15,23,42,.12); font-size: .8rem;">
            <option value="">Any assignee</option>
            @foreach ($userOptions as $u)
                <option value="{{ $u['id'] }}">🛠 {{ $u['name'] }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="clearFilters" style="padding: 6px 12px; border-radius: 999px; border: 0; background: rgba(15,23,42,.06); cursor: pointer; font-size: .75rem;">Clear</button>
    </div>

    <style>
        .kanban-board {
            display: grid;
            grid-template-columns: repeat({{ count($columns) }}, minmax(280px, 1fr));
            gap: 1rem;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .kanban-board { grid-template-columns: 1fr; }
        }
        .kanban-col {
            background: rgba(241, 245, 249, .65);
            border-radius: 14px;
            padding: 12px;
            min-height: 480px;
            display: flex;
            flex-direction: column;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,.04);
            backdrop-filter: blur(4px);
        }
        .dark .kanban-col {
            background: rgba(255,255,255,.04);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.04);
        }
        .kanban-col__header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 6px 8px 10px;
            border-bottom: 1px solid rgba(0,0,0,.06);
            margin-bottom: 10px;
        }
        .dark .kanban-col__header { border-bottom-color: rgba(255,255,255,.06); }
        .kanban-col__title {
            display: flex; align-items: center; gap: 8px;
            font-size: .8rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em;
            color: rgb(31 41 55);
        }
        .dark .kanban-col__title { color: rgb(229 231 235); }
        .kanban-col__dot {
            width: 10px; height: 10px; border-radius: 999px; display: inline-block;
        }
        .kanban-col__count {
            background: rgba(15,23,42,.08);
            color: rgb(15 23 42);
            font-size: .7rem; font-weight: 600;
            padding: 2px 8px; border-radius: 999px;
        }
        .dark .kanban-col__count {
            background: rgba(255,255,255,.1);
            color: rgb(229 231 235);
        }

        .kanban-col__list {
            display: flex; flex-direction: column; gap: 10px;
            min-height: 30px;
            flex: 1;
        }

        .kanban-card {
            position: relative;
            background: white;
            border-radius: 12px;
            padding: 14px 14px 12px;
            box-shadow: 0 1px 2px rgba(15,23,42,.06), 0 1px 1px rgba(15,23,42,.04);
            border: 1px solid rgba(15,23,42,.05);
            cursor: grab;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }
        .dark .kanban-card {
            background: rgb(30 41 59);
            border-color: rgba(255,255,255,.06);
            box-shadow: 0 1px 2px rgba(0,0,0,.4);
        }
        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px -6px rgba(15,23,42,.18);
        }
        .kanban-card:active { cursor: grabbing; }
        .kanban-card.sortable-ghost {
            opacity: .35;
            transform: rotate(1.5deg);
        }
        .kanban-card.sortable-chosen {
            box-shadow: 0 14px 28px -8px rgba(15,23,42,.3);
            cursor: grabbing;
        }
        .kanban-card.sortable-drag {
            transform: rotate(2deg);
            box-shadow: 0 18px 32px -10px rgba(15,23,42,.4);
        }
        .kanban-card__accent {
            position: absolute; left: 0; top: 0; bottom: 0;
            width: 4px;
            border-top-left-radius: 12px; border-bottom-left-radius: 12px;
        }
        .kanban-card__title {
            font-size: .9rem;
            font-weight: 600;
            color: rgb(15 23 42);
            line-height: 1.35;
            text-decoration: none;
            display: block;
            padding-right: 56px;
        }
        .dark .kanban-card__title { color: rgb(241 245 249); }
        .kanban-card__title:hover { text-decoration: underline; }
        .kanban-card__meta {
            display: flex; flex-wrap: wrap; gap: 6px 10px;
            margin-top: 8px;
            font-size: .72rem;
            color: rgb(71 85 105);
        }
        .dark .kanban-card__meta { color: rgb(148 163 184); }
        .kanban-card__chip {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(15,23,42,.05);
            padding: 2px 8px; border-radius: 999px;
            font-weight: 500;
        }
        .dark .kanban-card__chip {
            background: rgba(255,255,255,.06);
        }
        .kanban-card__chip--due {
            background: rgba(245,158,11,.15);
            color: rgb(146 64 14);
        }
        .dark .kanban-card__chip--due {
            background: rgba(245,158,11,.18);
            color: rgb(252 211 77);
        }
        .kanban-card__avatars {
            display: inline-flex;
        }
        .kanban-card__avatar {
            width: 22px; height: 22px;
            border-radius: 999px;
            font-size: .65rem;
            font-weight: 700;
            color: rgb(15 23 42);
            display: inline-flex; align-items: center; justify-content: center;
            border: 2px solid white;
            margin-left: -6px;
        }
        .dark .kanban-card__avatar { border-color: rgb(30 41 59); }
        .kanban-card__avatar:first-child { margin-left: 0; }
        .kanban-card__avatar--supervisor {
            box-shadow: 0 0 0 2px rgb(245 158 11);
        }
        .kanban-card__open {
            position: absolute; top: 8px; right: 8px;
            opacity: 0; transition: opacity 160ms ease;
            font-size: .68rem;
            background: rgb(15 23 42); color: white;
            padding: 2px 8px; border-radius: 999px;
            text-decoration: none;
        }
        .dark .kanban-card__open { background: rgb(248 250 252); color: rgb(15 23 42); }
        .kanban-card:hover .kanban-card__open { opacity: 1; }

        .kanban-empty {
            border: 2px dashed rgba(15,23,42,.12);
            border-radius: 10px;
            text-align: center;
            font-size: .75rem;
            color: rgb(100 116 139);
            padding: 18px 10px;
        }
        .dark .kanban-empty {
            border-color: rgba(255,255,255,.1);
            color: rgb(148 163 184);
        }
    </style>

    @if ($this->isFiltered())
        <div style="margin: 0 0 .75rem; padding: .5rem .75rem; border-radius: 8px; background: rgba(245,158,11,.12); color: rgb(146 64 14); font-size: .8rem;">
            Drag-and-drop is disabled while filters are active — clear filters to reorder cards.
        </div>
    @endif

    <div
        x-data="kanbanBoard({ canEdit: @js($canEdit && ! $this->isFiltered()) })"
        x-init="init()"
        class="kanban-board"
        wire:ignore
    >
        @foreach ($columns as $col)
            <div class="kanban-col">
                <div class="kanban-col__header">
                    <span class="kanban-col__title">
                        <span class="kanban-col__dot" style="background: {{ $col['accent'] }}"></span>
                        {{ $col['label'] }}
                    </span>
                    <span class="kanban-col__count" data-count-for="{{ $col['key'] }}">{{ count($col['tasks']) }}</span>
                </div>

                <div class="kanban-col__list" data-status="{{ $col['key'] }}">
                    @forelse ($col['tasks'] as $task)
                        <div class="kanban-card" data-task-id="{{ $task->id }}">
                            <span class="kanban-card__accent" style="background: {{ $col['accent'] }}"></span>
                            <a
                                href="{{ \App\Filament\Resources\Tasks\TaskResource::getUrl('edit', ['record' => $task->id]) }}"
                                class="kanban-card__open"
                            >Open ↗</a>
                            <a
                                href="{{ \App\Filament\Resources\Tasks\TaskResource::getUrl('edit', ['record' => $task->id]) }}"
                                class="kanban-card__title"
                            >
                                {{ $task->title }}
                            </a>

                            <div class="kanban-card__meta">
                                @if ($task->due_date)
                                    <span class="kanban-card__chip kanban-card__chip--due">
                                        🗓 {{ $task->due_date->format('d M Y') }}
                                    </span>
                                @endif

                                @php
                                    $supervisor = $task->supervisor;
                                    $assignees = $task->assignees;
                                @endphp

                                @if ($supervisor || $assignees->isNotEmpty())
                                    <span class="kanban-card__avatars">
                                        @if ($supervisor)
                                            <span
                                                class="kanban-card__avatar kanban-card__avatar--supervisor"
                                                style="background: {{ $avatarBg($supervisor->name) }}"
                                                title="Supervisor: {{ $supervisor->name }}"
                                            >{{ $initials($supervisor->name) }}</span>
                                        @endif
                                        @foreach ($assignees as $a)
                                            <span
                                                class="kanban-card__avatar"
                                                style="background: {{ $avatarBg($a->name) }}"
                                                title="{{ $a->name }}"
                                            >{{ $initials($a->name) }}</span>
                                        @endforeach
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="kanban-empty">Drop here</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('kanbanBoard', ({ canEdit }) => ({
                init() {
                    if (typeof Sortable === 'undefined') {
                        const s = document.createElement('script');
                        s.src = '{{ asset('vendor/sortable.min.js') }}';
                        s.onload = () => this.bind(canEdit);
                        document.head.appendChild(s);
                    } else {
                        this.bind(canEdit);
                    }
                },
                bind(canEdit) {
                    const lists = this.$el.querySelectorAll('.kanban-col__list');
                    const self = this;
                    lists.forEach((list) => {
                        new Sortable(list, {
                            group: 'kanban',
                            animation: 180,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            dragClass: 'sortable-drag',
                            disabled: !canEdit,
                            onEnd: () => self.persist(),
                        });
                    });
                    self.cleanupEmptyStates();
                },
                cleanupEmptyStates() {
                    this.$el.querySelectorAll('.kanban-col__list').forEach((list) => {
                        const hasCard = list.querySelector('.kanban-card');
                        const empty = list.querySelector('.kanban-empty');
                        if (hasCard && empty) empty.remove();
                        if (! hasCard && ! empty) {
                            const ph = document.createElement('div');
                            ph.className = 'kanban-empty';
                            ph.textContent = 'Drop here';
                            list.appendChild(ph);
                        }
                    });
                },
                persist() {
                    const payload = {};
                    this.$el.querySelectorAll('.kanban-col__list').forEach((list) => {
                        const status = list.getAttribute('data-status');
                        const ids = Array.from(list.querySelectorAll('.kanban-card'))
                            .map((card) => parseInt(card.getAttribute('data-task-id'), 10))
                            .filter((id) => Number.isFinite(id));
                        payload[status] = ids;
                        const counter = this.$el.querySelector('[data-count-for="' + status + '"]');
                        if (counter) counter.textContent = ids.length;
                    });
                    this.cleanupEmptyStates();
                    this.$wire.reorder(payload);
                },
            }));
        });
    </script>
</x-filament-panels::page>
