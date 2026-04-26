@php
    $rows = $this->getCalendarGrid();
    $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
@endphp

<x-filament-panels::page>
    <style>
        .cal-toolbar {
            display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem;
            padding-bottom: .75rem; border-bottom: 1px solid rgba(15,23,42,.08);
            margin-bottom: 1rem;
        }
        .dark .cal-toolbar { border-bottom-color: rgba(255,255,255,.08); }
        .cal-month {
            font-size: 1.05rem; font-weight: 700; min-width: 12ch;
        }
        .cal-btn {
            background: rgba(15,23,42,.06); color: rgb(15 23 42);
            border-radius: 999px; padding: 4px 12px; font-size: .8rem;
            border: 0; cursor: pointer;
        }
        .dark .cal-btn { background: rgba(255,255,255,.08); color: rgb(241 245 249); }
        .cal-btn:hover { filter: brightness(.95); }
        .cal-btn--primary {
            background: rgb(245 158 11); color: rgb(120 53 15);
        }
        .cal-filter-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 999px;
            font-size: .75rem; font-weight: 500;
            background: rgba(15,23,42,.05); color: rgb(15 23 42);
            cursor: pointer; user-select: none;
        }
        .dark .cal-filter-pill { background: rgba(255,255,255,.06); color: rgb(229 231 235); }
        .cal-filter-pill input { accent-color: rgb(245 158 11); }
        .cal-select {
            font-size: .8rem; padding: 4px 8px; border-radius: 8px;
            border: 1px solid rgba(15,23,42,.12);
            background: white; color: rgb(15 23 42);
        }
        .dark .cal-select {
            background: rgb(30 41 59); color: rgb(241 245 249);
            border-color: rgba(255,255,255,.1);
        }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 4px;
        }
        .cal-weekday {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .05em; color: rgb(100 116 139);
            padding: 6px 8px; text-align: center;
        }
        .dark .cal-weekday { color: rgb(148 163 184); }

        .cal-day {
            background: white;
            border-radius: 10px;
            min-height: 120px;
            padding: 6px;
            display: flex; flex-direction: column;
            border: 1px solid rgba(15,23,42,.05);
        }
        .dark .cal-day {
            background: rgb(30 41 59);
            border-color: rgba(255,255,255,.05);
        }
        .cal-day--out { opacity: .45; }
        .cal-day--today {
            border: 2px solid rgb(245 158 11);
        }
        .cal-day__num {
            font-size: .7rem; font-weight: 700;
            color: rgb(15 23 42);
            margin-bottom: 4px;
        }
        .dark .cal-day__num { color: rgb(229 231 235); }
        .cal-day__num--today {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 999px;
            background: rgb(245 158 11); color: white;
        }

        .cal-card {
            display: block;
            text-decoration: none;
            font-size: .7rem;
            line-height: 1.25;
            background: rgba(15,23,42,.04);
            color: rgb(15 23 42);
            padding: 4px 6px;
            border-radius: 6px;
            margin-bottom: 3px;
            border-left: 3px solid;
            transition: background-color .15s ease;
        }
        .dark .cal-card {
            background: rgba(255,255,255,.06);
            color: rgb(241 245 249);
        }
        .cal-card:hover { background: rgba(15,23,42,.08); }
        .dark .cal-card:hover { background: rgba(255,255,255,.1); }
        .cal-card__type {
            font-size: .55rem;
            text-transform: uppercase; letter-spacing: .04em;
            color: rgb(100 116 139); font-weight: 700;
        }
        .dark .cal-card__type { color: rgb(148 163 184); }
        .cal-card__title { font-weight: 600; }
        .cal-card__time { font-size: .6rem; opacity: .8; }
        .cal-card__badges {
            font-size: .55rem; opacity: .85;
            margin-top: 2px;
        }
    </style>

    <div class="cal-toolbar">
        <div class="cal-month">{{ $this->getMonthLabel() }}</div>
        <button type="button" class="cal-btn" wire:click="previousMonth">←</button>
        <button type="button" class="cal-btn" wire:click="today">Today</button>
        <button type="button" class="cal-btn" wire:click="nextMonth">→</button>

        <div style="flex: 1"></div>

        <label class="cal-filter-pill">
            <input type="checkbox" wire:model.live="showEvents"> Events
        </label>
        <label class="cal-filter-pill">
            <input type="checkbox" wire:model.live="showProjects"> Projects
        </label>
        <label class="cal-filter-pill">
            <input type="checkbox" wire:model.live="showTasks"> Tasks
        </label>

        <select class="cal-select" wire:model.live="assigneeId">
            <option value="">All assignees</option>
            @foreach ($this->getAssigneeOptions() as $u)
                <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
            @endforeach
        </select>

        @if ($this->canCreateEvents())
            <a
                href="{{ \App\Filament\Resources\Cms\Events\EventResource::getUrl('create') }}"
                class="cal-btn cal-btn--primary"
            >+ New event</a>
        @endif
    </div>

    <div class="cal-grid">
        @foreach ($weekdays as $day)
            <div class="cal-weekday">{{ $day }}</div>
        @endforeach

        @foreach ($rows as $row)
            @foreach ($row as $cell)
                @php
                    $isToday = $cell['date']->isToday();
                @endphp
                <div @class([
                    'cal-day',
                    'cal-day--out' => ! $cell['inMonth'],
                    'cal-day--today' => $isToday,
                ])>
                    <div class="cal-day__num">
                        @if ($isToday)
                            <span class="cal-day__num--today">{{ $cell['date']->day }}</span>
                        @else
                            {{ $cell['date']->day }}
                        @endif
                    </div>
                    @foreach ($cell['items'] as $item)
                        @php
                            $start = \Carbon\Carbon::parse($item['date']);
                            $end = $item['end'] ? \Carbon\Carbon::parse($item['end']) : null;
                            $timeLabel = $item['type'] === 'task'
                                ? null
                                : ($end
                                    ? $start->format('H:i') . '–' . $end->format('H:i')
                                    : $start->format('H:i'));
                        @endphp
                        <a
                            href="{{ $item['url'] }}"
                            class="cal-card"
                            style="border-left-color: {{ $item['color'] }}"
                        >
                            <div class="cal-card__type" style="color: {{ $item['color'] }}">{{ ucfirst($item['type']) }}</div>
                            <div class="cal-card__title">{{ $item['title'] }}</div>
                            @if ($timeLabel)
                                <div class="cal-card__time">{{ $timeLabel }}</div>
                            @endif
                            @foreach ($item['badges'] as $badge)
                                <div class="cal-card__badges">{{ $badge }}</div>
                            @endforeach
                        </a>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>
</x-filament-panels::page>
