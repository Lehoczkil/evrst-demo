@php
    $state = $this->getMissionState();
@endphp

<div
    class="evrst-countdown {{ $state['standby'] ? 'evrst-standby' : '' }}"
    x-data="evrstCountdown({
        target: @js($state['target']),
        mode: @js($state['mode'])
    })"
    x-init="start()"
>
    <div>
        <div class="evrst-countdown-label">
            <span x-text="label"></span>
        </div>
        <div class="evrst-countdown-digits">
            <span class="seg" x-text="d"></span><span class="unit">D</span>
            <span class="sep">:</span>
            <span class="seg" x-text="h"></span><span class="unit">H</span>
            <span class="sep">:</span>
            <span class="seg" x-text="m"></span><span class="unit">M</span>
            <span class="sep">:</span>
            <span class="seg" x-text="s"></span><span class="unit">S</span>
        </div>
    </div>
    <div class="evrst-countdown-meta">
        <div class="evrst-countdown-event">{{ \Illuminate\Support\Str::upper($state['title']) }}</div>
        <div class="evrst-countdown-sub">{{ $state['sub'] }}</div>
    </div>
</div>

<script>
    if (! window.evrstCountdown) {
        window.evrstCountdown = function ({ target, mode }) {
            return {
                d: '00', h: '00', m: '00', s: '00',
                label: mode === 'countdown' ? 'T-minus to next event' : (mode === 'sincelast' ? 'T+ since last event' : 'mission standby'),
                _interval: null,
                start() {
                    this.tick();
                    this._interval = setInterval(() => this.tick(), 1000);
                },
                pad(n) { return String(Math.floor(n)).padStart(2, '0'); },
                tick() {
                    const t = new Date(target).getTime();
                    const now = Date.now();
                    let ms = mode === 'countdown' ? Math.max(0, t - now) : Math.max(0, now - t);
                    const sec = Math.floor(ms / 1000);
                    const days = Math.floor(sec / 86400);
                    const hrs  = Math.floor((sec % 86400) / 3600);
                    const mins = Math.floor((sec % 3600) / 60);
                    const ss   = sec % 60;
                    this.d = this.pad(days);
                    this.h = this.pad(hrs);
                    this.m = this.pad(mins);
                    this.s = this.pad(ss);
                },
            };
        };
    }
</script>
