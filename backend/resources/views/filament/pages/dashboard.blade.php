<x-filament-panels::page>
    @php
        $stats     = $this->getStats();
        $myTasks   = $this->getMyOpenTasks();
        $upcoming  = $this->getUpcomingThisWeek();
        $latest    = $this->getLatestApplications();
        $activity  = $this->getRecentActivity();
        $actions   = $this->getQuickActions();

        // Applicant names and the audit feed are admin-grade data: the
        // tiles that carry them only render for viewers who could open
        // the matching resource. The page methods above return empty
        // collections for everyone else, so nothing is even queried.
        $canSeeApplications = $this->canSeeApplications();
        $canSeeActivity     = $this->canSeeActivity();

        $state    = $this->getMissionState();
        $target   = \Illuminate\Support\Carbon::parse($state['target']);
        $now      = now();
        $delta    = match ($state['mode']) {
            'countdown' => max(0, $target->getTimestamp() - $now->getTimestamp()),
            'sincelast' => max(0, $now->getTimestamp() - $target->getTimestamp()),
            default     => 0,
        };
        $pad = fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
        $d = $pad((int) floor($delta / 86400));
        $h = $pad((int) floor(($delta % 86400) / 3600));
        $m = $pad((int) floor(($delta % 3600) / 60));
        $s = $pad((int) ($delta % 60));
        $cdLabel = match ($state['mode']) {
            'countdown' => __('admin.widgets.countdown_to'),
            'sincelast' => __('admin.widgets.countdown_since'),
            default     => __('admin.widgets.countdown_idle'),
        };
        $cdId = 'evrst-cd-' . substr(md5($state['target']), 0, 8);
    @endphp

    {{-- ─────────  T-countdown strip  ───────── --}}
    <div
        id="{{ $cdId }}"
        class="evrst-countdown {{ $state['standby'] ? 'evrst-standby' : '' }}"
        data-target="{{ $state['target'] }}"
        data-mode="{{ $state['mode'] }}"
    >
        <div>
            <div class="evrst-countdown-label">{{ $cdLabel }}</div>
            <div class="evrst-countdown-digits">
                <span class="seg" data-seg="d">{{ $d }}</span><span class="unit">D</span>
                <span class="sep">:</span>
                <span class="seg" data-seg="h">{{ $h }}</span><span class="unit">H</span>
                <span class="sep">:</span>
                <span class="seg" data-seg="m">{{ $m }}</span><span class="unit">M</span>
                <span class="sep">:</span>
                <span class="seg" data-seg="s">{{ $s }}</span><span class="unit">S</span>
            </div>
        </div>
        <div class="evrst-countdown-meta">
            <div class="evrst-countdown-event">{{ \Illuminate\Support\Str::upper($state['title']) }}</div>
            <div class="evrst-countdown-sub">{{ $state['sub'] }}</div>
        </div>
    </div>
    <script>
    (function () {
        const root = document.getElementById(@js($cdId));
        if (! root || root.dataset.evrstBound === '1') return;
        root.dataset.evrstBound = '1';
        const segs = {
            d: root.querySelector('[data-seg="d"]'),
            h: root.querySelector('[data-seg="h"]'),
            m: root.querySelector('[data-seg="m"]'),
            s: root.querySelector('[data-seg="s"]'),
        };
        const target = new Date(root.dataset.target).getTime();
        const mode = root.dataset.mode;
        const last = { d: '', h: '', m: '', s: '' };
        const pad = (n) => String(Math.floor(n)).padStart(2, '0');
        function tick() {
            const ms = mode === 'countdown' ? Math.max(0, target - Date.now()) : Math.max(0, Date.now() - target);
            const sec = Math.floor(ms / 1000);
            const v = {
                d: pad(Math.floor(sec / 86400)),
                h: pad(Math.floor((sec % 86400) / 3600)),
                m: pad(Math.floor((sec % 3600) / 60)),
                s: pad(sec % 60),
            };
            for (const k of ['d','h','m','s']) {
                if (last[k] !== v[k]) {
                    segs[k].textContent = v[k];
                    segs[k].classList.remove('flip');
                    void segs[k].offsetWidth;
                    segs[k].classList.add('flip');
                    last[k] = v[k];
                }
            }
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>

    {{-- ─────────  6-tile grid  ───────── --}}
    <div class="evrst-tile-grid">

        {{-- 1 · PENDING APPLICATIONS --}}
        @if ($canSeeApplications)
            <div class="evrst-tile">
                <div class="evrst-tile-heading">
                    {{ __('admin.widgets.pending_apps') }}
                    <span class="evrst-tile-count">· {{ $stats['pending'] }}</span>
                </div>
                <div class="evrst-tile-big">{{ $stats['pending'] }}
                    @if ($stats['pending'] === 0)
                        <span class="evrst-tile-delta evrst-success">✓ {{ __('admin.widgets.inbox_clear') }}</span>
                    @else
                        <span class="evrst-tile-delta evrst-amber">{{ __('admin.widgets.awaiting_review') }}</span>
                    @endif
                </div>
                {{-- The row list that used to sit here ran an unfiltered query,
                     so accepted and rejected applicants showed up underneath a
                     count that only totalled the pending ones. Tile 4 lists
                     recent applications properly, with a status badge. --}}
            </div>
        @endif

        {{-- 2 · OPEN TASKS (YOU) --}}
        <div class="evrst-tile">
            <div class="evrst-tile-heading">
                {{ __('admin.widgets.my_tasks') }}
                <span class="evrst-tile-count">· {{ $myTasks->count() }}</span>
            </div>
            @if ($myTasks->isEmpty())
                <p class="evrst-tile-empty">{{ __('admin.widgets.my_tasks_empty') }}</p>
            @else
                <div class="evrst-tile-rows">
                    @foreach ($myTasks as $t)
                        @php
                            $dot = match ($t->priority ?? null) {
                                'urgent' => 'evrst-dot-amber',
                                'high'   => 'evrst-dot-danger',
                                'normal' => 'evrst-dot-info',
                                default  => 'evrst-dot-muted',
                            };
                            $due = $t->due_date
                                ? \Illuminate\Support\Str::upper($t->due_date->isPast() ? 'OVERDUE' : ('DUE ' . $t->due_date->format('d M')))
                                : 'NO DUE';
                        @endphp
                        <a href="{{ \App\Filament\Resources\Tasks\TaskResource::getUrl('edit', ['record' => $t->id]) }}" class="evrst-tile-row">
                            <span class="evrst-dot {{ $dot }}"></span>
                            <span class="evrst-tile-row-title">{{ $t->title }}</span>
                            <span class="evrst-tile-row-meta">{{ $due }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 3 · UPCOMING THIS WEEK --}}
        <div class="evrst-tile">
            <div class="evrst-tile-heading">{{ __('admin.widgets.upcoming') }}<span class="evrst-tile-count">· {{ $upcoming->count() }}</span></div>
            @if ($upcoming->isEmpty())
                <p class="evrst-tile-empty">{{ __('admin.widgets.no_upcoming') }}</p>
            @else
                <div class="evrst-tile-rows">
                    @foreach ($upcoming as $u)
                        @php
                            $dot = 'evrst-dot-' . ($u['color'] ?? 'muted');
                            $when = $u['when'];
                            $meta = \Illuminate\Support\Str::upper($when->format('D · H:i'));
                        @endphp
                        <a href="{{ $u['url'] }}" class="evrst-tile-row">
                            <span class="evrst-dot {{ $dot }}"></span>
                            <span class="evrst-tile-row-title">{{ $u['title'] }}</span>
                            <span class="evrst-tile-row-meta">{{ $meta }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 4 · LATEST APPLICATIONS --}}
        @if ($canSeeApplications)
            <div class="evrst-tile">
                <div class="evrst-tile-heading">{{ __('admin.widgets.recent_apps') }}<span class="evrst-tile-count">· {{ $latest->count() }}</span></div>
                @if ($latest->isEmpty())
                    <p class="evrst-tile-empty">{{ __('admin.widgets.recent_apps_empty') }}</p>
                @else
                    <div class="evrst-tile-rows">
                        @foreach ($latest as $a)
                            @php
                                $dot = match ($a->status) {
                                    'PENDING'  => 'evrst-dot-amber',
                                    'ACCEPTED' => 'evrst-dot-success',
                                    'REJECTED' => 'evrst-dot-danger',
                                    default    => 'evrst-dot-muted',
                                };
                            @endphp
                            <a href="{{ \App\Filament\Resources\MemberApplications\MemberApplicationResource::getUrl('edit', ['record' => $a->id]) }}" class="evrst-tile-row">
                                <span class="evrst-dot {{ $dot }}"></span>
                                <span class="evrst-tile-row-title">{{ $a->name }} @if ($a->department)<span class="evrst-tile-row-sub">· {{ $a->department }}</span>@endif</span>
                                <span class="evrst-tile-row-meta">{{ \Illuminate\Support\Str::upper($a->status) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- 5 · ACTIVITY --}}
        @if ($canSeeActivity)
            <div class="evrst-tile">
                <div class="evrst-tile-heading">{{ __('admin.resources.activity_log.p') }}</div>
                @if ($activity->isEmpty())
                    <p class="evrst-tile-empty">{{ __('admin.empty.activity_logs_b') }}</p>
                @else
                    <div class="evrst-tile-rows">
                        @foreach ($activity as $log)
                            @php
                                $dot = match ($log->event ?? '') {
                                    'created', 'accepted' => 'evrst-dot-success',
                                    'updated'             => 'evrst-dot-info',
                                    'deleted', 'rejected' => 'evrst-dot-danger',
                                    default               => 'evrst-dot-muted',
                                };
                                $who = $log->user?->name ?? 'system';
                            @endphp
                            <div class="evrst-tile-row">
                                <span class="evrst-dot {{ $dot }}"></span>
                                <span class="evrst-tile-row-title"><b>{{ $who }}</b> <span class="evrst-tile-row-sub">{{ $log->event }}</span> <span class="evrst-tile-row-target">{{ $log->subject_label ?: ($log->subject_type ?: '—') }}</span></span>
                                <span class="evrst-tile-row-meta">{{ \Illuminate\Support\Str::upper($log->created_at?->diffForHumans(null, true) ?? '') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- 6 · QUICK ACTIONS --}}
        <div class="evrst-tile">
            <div class="evrst-tile-heading">{{ __('admin.widgets.quick_actions') ?? 'Quick actions' }}</div>
            <div class="evrst-quick-actions">
                @foreach ($actions as $a)
                    <a href="{{ $a['url'] }}" class="evrst-qa">
                        <span class="evrst-qa-plus">+</span> {{ $a['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>
