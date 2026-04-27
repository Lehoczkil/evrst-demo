@php
    $rows = $this->getCalendarGrid();
    // HU weekday abbreviations follow the conventional 1-3 char form
    // used in Hungarian calendars (H, K, Sze, Cs, P, Szo, V) so the row
    // has consistent visual rhythm under the centred header style.
    $weekdays = app()->getLocale() === 'hu'
        ? ['H', 'K', 'Sze', 'Cs', 'P', 'Szo', 'V']
        : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
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
        .cal-btn--danger {
            background: rgb(239 68 68); color: white;
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

        @media (max-width: 720px) {
            .cal-grid { grid-template-columns: 1fr; gap: 6px; }
            .cal-weekday { display: none; }
            .cal-day {
                min-height: 0;
                padding: 10px 12px;
                flex-direction: row;
                align-items: flex-start;
                gap: 12px;
            }
            .cal-day--out { display: none; }
            .cal-day__num {
                min-width: 36px;
                font-size: .85rem;
                margin: 0;
                flex-shrink: 0;
            }
            .cal-day > .cal-day__num,
            .cal-day > .cal-day__cards { flex: 1; min-width: 0; }
            .cal-day:not(:has(.cal-card)) { display: none; }   /* hide empty days on mobile */
        }
        @supports not selector(:has(*)) {
            @media (max-width: 720px) {
                .cal-day:empty { display: none; }
            }
        }

        .cal-day {
            background: white;
            border-radius: 10px;
            min-height: 120px;
            padding: 6px;
            display: flex; flex-direction: column;
            border: 1px solid rgba(15,23,42,.05);
            cursor: pointer;
            transition: background-color .08s ease, border-color .08s ease;
        }
        .dark .cal-day {
            background: rgb(30 41 59);
            border-color: rgba(255,255,255,.05);
        }
        .cal-day:hover {
            border-color: rgb(245 158 11);
        }
        .cal-day--out { opacity: .45; }
        .cal-day--out:hover { opacity: .7; }
        .cal-day--today {
            border: 2px solid rgb(245 158 11);
        }
        .cal-day__num {
            font-size: .7rem; font-weight: 700;
            color: rgb(15 23 42);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .cal-day__num { color: rgb(229 231 235); }
        .cal-day__num--today {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 999px;
            background: rgb(245 158 11); color: white;
        }
        .cal-day__add {
            opacity: 0;
            font-size: .9rem; line-height: 1;
            color: rgb(245 158 11);
            transition: opacity .12s ease;
        }
        .cal-day:hover .cal-day__add { opacity: 1; }

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
            cursor: pointer;
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
        .cal-card__badges { font-size: .55rem; opacity: .85; margin-top: 2px; }

        /* Modal */
        .cal-modal-backdrop {
            position: fixed; inset: 0; z-index: 60;
            background: rgba(15,23,42,.55);
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(4px);
        }
        .cal-modal {
            background: white; color: rgb(15 23 42);
            border-radius: 14px;
            width: 100%; max-width: 540px;
            box-shadow: 0 16px 60px rgba(15,23,42,.35);
            overflow: hidden;
        }
        .dark .cal-modal {
            background: rgb(15 23 42); color: rgb(226 232 240);
            box-shadow: 0 16px 60px rgba(0,0,0,.6);
        }
        .cal-modal__head {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(15,23,42,.08);
            display: flex; align-items: center; justify-content: space-between;
        }
        .dark .cal-modal__head { border-bottom-color: rgba(255,255,255,.08); }
        .cal-modal__title { font-size: 1rem; font-weight: 700; }
        .cal-modal__body { padding: 1rem 1.25rem; display: grid; gap: .75rem; }
        .cal-modal__foot {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(15,23,42,.08);
            display: flex; gap: .5rem; justify-content: flex-end;
            background: rgba(15,23,42,.02);
        }
        .dark .cal-modal__foot {
            border-top-color: rgba(255,255,255,.08);
            background: rgba(255,255,255,.02);
        }
        .cal-field { display: flex; flex-direction: column; gap: .25rem; }
        .cal-field label {
            font-size: .7rem; text-transform: uppercase; letter-spacing: .05em;
            font-weight: 600; color: rgb(100 116 139);
        }
        .dark .cal-field label { color: rgb(148 163 184); }
        .cal-input {
            width: 100%;
            padding: .5rem .65rem;
            border: 1px solid rgba(15,23,42,.12);
            border-radius: 8px; font-size: .9rem;
            background: white; color: rgb(15 23 42);
        }
        .dark .cal-input {
            background: rgb(30 41 59); color: rgb(226 232 240);
            border-color: rgba(255,255,255,.12);
        }
        .cal-input:focus { outline: 2px solid rgb(245 158 11); outline-offset: -1px; }
        .cal-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        @media (max-width: 540px) { .cal-row { grid-template-columns: 1fr; } }
        .cal-checkbox {
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: .85rem;
        }
        .cal-color-row { display: flex; align-items: center; gap: .5rem; }
    </style>

    <div class="cal-toolbar">
        <div class="cal-month">{{ $this->getMonthLabel() }}</div>
        <button type="button" class="cal-btn" wire:click="previousMonth" title="{{ __('admin.calendar.previous') }}">←</button>
        <button type="button" class="cal-btn" wire:click="today">{{ __('admin.calendar.today') }}</button>
        <button type="button" class="cal-btn" wire:click="nextMonth" title="{{ __('admin.calendar.next') }}">→</button>

        <div style="flex: 1"></div>

        <label class="cal-filter-pill">
            <input type="checkbox" wire:model.live="showEvents"> {{ __('admin.calendar.events') }}
        </label>
        <label class="cal-filter-pill">
            <input type="checkbox" wire:model.live="showProjects"> {{ __('admin.calendar.projects') }}
        </label>
        <label class="cal-filter-pill">
            <input type="checkbox" wire:model.live="showTasks"> {{ __('admin.calendar.tasks') }}
        </label>

        <select class="cal-select" wire:model.live="assigneeId">
            <option value="">{{ __('admin.calendar.all_assignees') }}</option>
            @foreach ($this->getAssigneeOptions() as $u)
                <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
            @endforeach
        </select>

        <button
            type="button"
            class="cal-btn cal-btn--primary"
            wire:click="openCreateModal('{{ now()->toDateString() }}')"
        >{{ __('admin.calendar.new_event') }}</button>
    </div>

    <div class="cal-grid">
        @foreach ($weekdays as $day)
            <div class="cal-weekday">{{ $day }}</div>
        @endforeach

        @foreach ($rows as $row)
            @foreach ($row as $cell)
                @php
                    $isToday = $cell['date']->isToday();
                    $dateString = $cell['date']->toDateString();
                @endphp
                <div
                    @class([
                        'cal-day',
                        'cal-day--out' => ! $cell['inMonth'],
                        'cal-day--today' => $isToday,
                    ])
                    wire:click="openCreateModal('{{ $dateString }}')"
                    title="{{ __('admin.calendar.click_hint') }}"
                >
                    <div class="cal-day__num">
                        @if ($isToday)
                            <span class="cal-day__num--today">{{ $cell['date']->day }}</span>
                        @else
                            <span>{{ $cell['date']->day }}</span>
                        @endif
                        <span class="cal-day__add">+</span>
                    </div>
                    <div class="cal-day__cards" style="display:contents;">
                        @foreach ($cell['items'] as $item)
                            @php
                                $start = $item['date'] ? \Carbon\Carbon::parse($item['date']) : null;
                                $end = $item['end'] ? \Carbon\Carbon::parse($item['end']) : null;
                                $isAllDay = $item['all_day'] ?? false;
                                $timeLabel = ($item['kind'] === 'task' || $isAllDay)
                                    ? null
                                    : ($end && $start
                                        ? $start->format('H:i') . '–' . $end->format('H:i')
                                        : ($start ? $start->format('H:i') : null));
                                $clickAttr = $item['kind'] === 'event'
                                    ? 'wire:click.stop="openEditModal(' . $item['id'] . ')"'
                                    : 'onclick="event.stopPropagation();window.location=' . "'" . ($item['url'] ?? '#') . "'" . '"';
                            @endphp
                            <div
                                class="cal-card"
                                style="border-left-color: {{ $item['color'] }}"
                                {!! $clickAttr !!}
                            >
                                <div class="cal-card__type" style="color: {{ $item['color'] }}">{{ $item['type'] }}</div>
                                <div class="cal-card__title">{{ $item['title'] }}</div>
                                @if ($timeLabel)
                                    <div class="cal-card__time">{{ $timeLabel }}</div>
                                @endif
                                @foreach ($item['badges'] as $badge)
                                    <div class="cal-card__badges">{{ $badge }}</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    @if ($showFormModal)
        <div class="cal-modal-backdrop" wire:click.self="closeFormModal" wire:keydown.escape.window="closeFormModal">
            <div class="cal-modal" role="dialog" aria-modal="true">
                <div class="cal-modal__head">
                    <div class="cal-modal__title">
                        {{ $editingId ? __('admin.calendar.modal.edit_title') : __('admin.calendar.modal.new_title') }}
                    </div>
                    <button type="button" class="cal-btn" wire:click="closeFormModal">✕</button>
                </div>

                <form wire:submit.prevent="saveEvent">
                    <div class="cal-modal__body">
                        <div class="cal-field">
                            <label>{{ __('admin.calendar.modal.title') }}</label>
                            <input type="text" class="cal-input" wire:model="eventTitle" maxlength="200" autofocus />
                        </div>

                        <div class="cal-row">
                            <div class="cal-field">
                                <label>{{ __('admin.calendar.modal.starts') }}</label>
                                <input type="datetime-local" class="cal-input" wire:model="eventStart" />
                            </div>
                            <div class="cal-field">
                                <label>{{ __('admin.calendar.modal.ends') }}</label>
                                <input type="datetime-local" class="cal-input" wire:model="eventEnd" />
                            </div>
                        </div>

                        <label class="cal-checkbox">
                            <input type="checkbox" wire:model="eventAllDay"> {{ __('admin.calendar.modal.all_day') }}
                        </label>

                        <div class="cal-field">
                            <label>{{ __('admin.calendar.modal.location') }}</label>
                            <input type="text" class="cal-input" wire:model="eventLocation" maxlength="200" placeholder="{{ __('admin.calendar.modal.optional') }}" />
                        </div>

                        <div class="cal-field">
                            <label>{{ __('admin.calendar.modal.description') }}</label>
                            <textarea class="cal-input" wire:model="eventDescription" rows="3" placeholder="{{ __('admin.calendar.modal.optional') }}"></textarea>
                        </div>

                        <div class="cal-field">
                            <label>{{ __('admin.calendar.modal.color') }}</label>
                            <div class="cal-color-row">
                                <input type="color" class="cal-input" wire:model="eventColor" style="width: 56px; padding: 0; height: 36px;" />
                                <input type="text" class="cal-input" wire:model="eventColor" pattern="^#[0-9a-fA-F]{6}$" />
                            </div>
                        </div>
                    </div>

                    <div class="cal-modal__foot">
                        @if ($editingId)
                            <button type="button" class="cal-btn cal-btn--danger" wire:click="deleteEvent" wire:confirm="{{ __('admin.calendar.modal.confirm_del') }}">{{ __('admin.calendar.modal.delete') }}</button>
                            <div style="flex:1"></div>
                        @endif
                        <button type="button" class="cal-btn" wire:click="closeFormModal">{{ __('admin.calendar.modal.cancel') }}</button>
                        <button type="submit" class="cal-btn cal-btn--primary">
                            {{ $editingId ? __('admin.calendar.modal.save') : __('admin.calendar.modal.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
