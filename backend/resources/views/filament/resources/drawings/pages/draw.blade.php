<x-filament-panels::page>
    <style>
        :root {
            --ds-bg: rgba(255,255,255,.7);
            --ds-bg-strong: white;
            --ds-border: rgba(15,23,42,.08);
            --ds-text: rgb(15 23 42);
            --ds-muted: rgb(100 116 139);
            --ds-accent: rgb(245 158 11);
            --ds-accent-soft: rgb(254 243 199);
            --ds-accent-strong: rgb(146 64 14);
            --ds-input: white;
        }
        .dark {
            --ds-bg: rgba(15,23,42,.4);
            --ds-bg-strong: rgb(30 41 59);
            --ds-border: rgba(255,255,255,.08);
            --ds-text: rgb(226 232 240);
            --ds-muted: rgb(148 163 184);
            --ds-input: rgb(15 23 42);
        }

        .ds-shell {
            display: grid;
            grid-template-columns: 64px 1fr 280px;
            gap: .75rem;
            min-height: 78vh;
            position: relative;
        }
        @media (max-width: 1100px) {
            .ds-shell { grid-template-columns: 56px 1fr; }
            .ds-properties { display: none; }
        }
        /* Mobile layout — single column with a fixed bottom toolbar that
           exposes the small set of controls users actually need on a
           phone. The canvas stops above the toolbar instead of being
           covered by it. */
        @media (max-width: 720px) {
            .ds-shell {
                grid-template-columns: 1fr;
                min-height: auto;
                padding-bottom: 5.5rem; /* room for the fixed bottom bar */
            }
            .ds-tools {
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                position: static;
                top: auto;
                gap: .25rem;
                padding: .35rem;
                scrollbar-width: thin;
                -webkit-overflow-scrolling: touch;
            }
            .ds-tools .ds-tool {
                flex: 0 0 auto;
                width: 40px; height: 40px;
            }
            .ds-tools .ds-tool svg { width: 18px; height: 18px; }
            .ds-tools .ds-tool__hot { display: none; }

            .ds-properties { display: none; }

            .ds-canvas-wrap {
                min-height: 50vh;
                padding: .5rem;
            }
            .ds-status { bottom: 6rem; right: .5rem; font-size: .65rem; }

            .ds-topbar {
                position: static; /* no overlap with canvas while scrolling */
                padding: .35rem .5rem;
                gap: .35rem;
                margin-bottom: .5rem;
            }
            .ds-topbar .ds-divider { display: none; }
            .ds-topbar .ds-btn { padding: .35rem .5rem; font-size: .75rem; }
            .ds-topbar .ds-btn svg { width: 14px; height: 14px; }
            .ds-topbar .ds-select, .ds-topbar .ds-input { font-size: .72rem; padding: .3rem .45rem; }

            .ds-mobile-bar {
                position: fixed;
                left: 0; right: 0; bottom: 0;
                z-index: 30;
                padding: .55rem .65rem calc(.55rem + env(safe-area-inset-bottom, 0px));
                background: var(--ds-bg-strong);
                border-top: 1px solid var(--ds-border);
                box-shadow: 0 -8px 24px rgba(15,23,42,.12);
                display: flex; align-items: center; gap: .5rem;
                overflow-x: auto;
            }
            .ds-mobile-bar input[type="color"] {
                width: 38px; height: 36px; padding: 0;
                border: 1px solid var(--ds-border); border-radius: .5rem;
                background: var(--ds-input);
            }
            .ds-mobile-bar input[type="range"] {
                flex: 1 1 100px; min-width: 100px;
            }
            .ds-mobile-bar .ds-btn { padding: .4rem .55rem; }
        }
        @media (min-width: 721px) {
            .ds-mobile-bar { display: none; }
        }

        /* Top toolbar */
        .ds-topbar {
            display: flex; flex-wrap: wrap; align-items: center;
            gap: .5rem; padding: .5rem .75rem;
            background: var(--ds-bg);
            border: 1px solid var(--ds-border);
            border-radius: .75rem;
            margin-bottom: .75rem;
        }
        .ds-title-input {
            flex: 1 1 200px; min-width: 0;
            background: transparent; border: 0; outline: none;
            font-size: 1rem; font-weight: 600;
            color: var(--ds-text);
            padding: .35rem .5rem;
            border-radius: .375rem;
        }
        .ds-title-input:focus {
            background: var(--ds-input);
            box-shadow: 0 0 0 2px var(--ds-accent);
        }
        .ds-divider {
            width: 1px; height: 22px; background: var(--ds-border);
            margin: 0 .15rem;
        }

        .ds-btn {
            display: inline-flex; align-items: center; gap: .35rem;
            border: 1px solid var(--ds-border);
            background: var(--ds-bg-strong);
            color: var(--ds-text);
            border-radius: .5rem;
            padding: .4rem .65rem;
            font-size: .8rem; font-weight: 500;
            cursor: pointer;
            transition: all .12s ease;
        }
        .ds-btn:hover { border-color: var(--ds-accent); color: var(--ds-accent-strong); }
        .ds-btn[disabled] { opacity: .5; cursor: not-allowed; }
        .ds-btn--ghost { background: transparent; border-color: transparent; }
        .ds-btn--accent {
            background: var(--ds-accent); color: var(--ds-accent-strong);
            border-color: rgb(217 119 6); font-weight: 600;
        }
        .ds-btn--accent:hover { background: rgb(252 211 77); color: var(--ds-accent-strong); }
        .ds-btn svg { width: 16px; height: 16px; }

        .ds-select, .ds-input {
            background: var(--ds-input);
            color: var(--ds-text);
            border: 1px solid var(--ds-border);
            border-radius: .5rem;
            padding: .35rem .55rem;
            font-size: .8rem;
        }
        .ds-input { width: 100%; }
        .ds-select:focus, .ds-input:focus {
            outline: 2px solid var(--ds-accent); outline-offset: -1px;
        }

        /* Tools rail */
        .ds-tools {
            display: flex; flex-direction: column;
            gap: .25rem; padding: .35rem;
            background: var(--ds-bg);
            border: 1px solid var(--ds-border);
            border-radius: .75rem;
            align-self: start;
            position: sticky; top: .75rem;
        }
        .ds-tool {
            position: relative;
            width: 44px; height: 44px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            border: 1px solid transparent;
            background: transparent;
            color: var(--ds-text);
            border-radius: .5rem;
            cursor: pointer;
            transition: all .12s ease;
        }
        .ds-tool:hover {
            background: var(--ds-bg-strong);
            border-color: var(--ds-border);
        }
        .ds-tool[aria-pressed="true"] {
            background: var(--ds-accent-soft);
            color: var(--ds-accent-strong);
            border-color: var(--ds-accent);
        }
        .dark .ds-tool[aria-pressed="true"] {
            background: rgb(120 53 15);
            color: rgb(254 243 199);
        }
        .ds-tool svg { width: 20px; height: 20px; }
        .ds-tool__hot {
            position: absolute; bottom: 2px; right: 4px;
            font-size: 9px; opacity: .5; font-weight: 700;
        }

        /* Tooltips */
        [data-tip] { position: relative; }
        /* Sidebars contain tooltips, so they must allow overflow + sit above the canvas. */
        .ds-tools, .ds-properties { overflow: visible !important; z-index: 20; }
        .ds-tools:hover, .ds-properties:hover,
        .ds-tools:focus-within, .ds-properties:focus-within { z-index: 70; }
        .ds-topbar { z-index: 25; position: relative; }
        .ds-canvas-wrap { position: relative; z-index: 1; }

        [data-tip]::before,
        [data-tip]::after {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            transition: opacity .12s ease, transform .12s ease;
            z-index: 1000;
        }
        [data-tip]::before {
            content: attr(data-tip);
            background: rgba(15,23,42,.94);
            color: white;
            font-size: .72rem;
            font-weight: 500;
            line-height: 1.25;
            white-space: nowrap;
            padding: .35rem .55rem;
            border-radius: .375rem;
            box-shadow: 0 4px 14px rgba(0,0,0,.25);
            z-index: 1000;
        }
        [data-tip]::after {
            content: '';
            border: 5px solid transparent;
            z-index: 1000;
        }
        [data-tip]:hover,
        [data-tip]:focus-visible {
            z-index: 100;
        }
        [data-tip]:hover::before,
        [data-tip]:hover::after,
        [data-tip]:focus-visible::before,
        [data-tip]:focus-visible::after {
            opacity: 1;
        }
        /* Default placement: bottom */
        [data-tip][data-tip-pos="bottom"]::before,
        [data-tip]:not([data-tip-pos])::before {
            top: calc(100% + 8px); left: 50%; transform: translateX(-50%);
        }
        [data-tip][data-tip-pos="bottom"]::after,
        [data-tip]:not([data-tip-pos])::after {
            top: calc(100% + 3px); left: 50%; transform: translateX(-50%);
            border-bottom-color: rgba(15,23,42,.94);
        }
        /* Right placement (used by the left tool rail) */
        [data-tip][data-tip-pos="right"]::before {
            left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
        }
        [data-tip][data-tip-pos="right"]::after {
            left: calc(100% + 5px); top: 50%; transform: translateY(-50%);
            border-right-color: rgba(15,23,42,.94);
        }
        /* Left placement (used by the right properties panel) */
        [data-tip][data-tip-pos="left"]::before {
            right: calc(100% + 10px); top: 50%; transform: translateY(-50%);
        }
        [data-tip][data-tip-pos="left"]::after {
            right: calc(100% + 5px); top: 50%; transform: translateY(-50%);
            border-left-color: rgba(15,23,42,.94);
        }
        /* Top placement */
        [data-tip][data-tip-pos="top"]::before {
            bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%);
        }
        [data-tip][data-tip-pos="top"]::after {
            bottom: calc(100% + 3px); left: 50%; transform: translateX(-50%);
            border-top-color: rgba(15,23,42,.94);
        }
        /* Hide native browser tooltips on elements that have data-tip */
        [data-tip][title] { /* keep title for a11y, only :hover suppresses native */ }

        /* Properties panel — overflow stays visible so tooltips can escape. */
        .ds-properties {
            display: flex; flex-direction: column; gap: 1rem;
            padding: 1rem;
            background: var(--ds-bg);
            border: 1px solid var(--ds-border);
            border-radius: .75rem;
            align-self: start;
            position: sticky; top: .75rem;
        }
        .ds-prop-section { display: flex; flex-direction: column; gap: .5rem; }
        .ds-section-label {
            font-size: .68rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            color: var(--ds-muted);
        }

        .ds-color-row { display: grid; grid-template-columns: repeat(8, 1fr); gap: .25rem; }
        .ds-swatch {
            width: 100%; aspect-ratio: 1;
            border-radius: .375rem;
            border: 2px solid transparent;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,.08);
        }
        .ds-swatch[aria-pressed="true"] {
            border-color: var(--ds-accent);
            transform: scale(1.08);
        }
        .ds-color-pickers { display: flex; align-items: center; gap: .35rem; }
        .ds-color-pickers input[type="color"] {
            width: 38px; height: 32px; padding: 0;
            border-radius: .375rem;
            border: 1px solid var(--ds-border);
            background: var(--ds-input);
        }

        .ds-row { display: flex; align-items: center; gap: .5rem; font-size: .8rem; }
        .ds-row label { font-size: .75rem; color: var(--ds-muted); min-width: 6ch; }
        .ds-num { width: 4ch; text-align: right; font-variant-numeric: tabular-nums; font-size: .8rem; }

        .ds-toggle {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .8rem; cursor: pointer;
        }
        .ds-toggle input { accent-color: var(--ds-accent); }

        /* Canvas area */
        .ds-canvas-wrap {
            border: 1px solid var(--ds-border);
            border-radius: .75rem;
            background:
                repeating-conic-gradient(rgba(15,23,42,.06) 0% 25%, transparent 0% 50%) 50% / 18px 18px;
            padding: 1rem;
            overflow: auto;
            position: relative;
            display: flex; align-items: flex-start; justify-content: center;
            min-height: 70vh;
        }
        .dark .ds-canvas-wrap {
            background:
                repeating-conic-gradient(rgba(255,255,255,.06) 0% 25%, transparent 0% 50%) 50% / 18px 18px;
        }
        .ds-canvas-stage {
            position: relative;
            box-shadow: 0 4px 20px rgba(15,23,42,.18);
        }
        #ds-canvas, #ds-overlay {
            display: block;
            background: white;
            max-width: 100%;
            height: auto;
            touch-action: none;
        }
        #ds-overlay {
            position: absolute; inset: 0;
            background: transparent;
            pointer-events: none;
        }
        .ds-canvas-stage[data-tool="eyedropper"] #ds-canvas { cursor: copy; }
        .ds-canvas-stage[data-tool="bucket"]      #ds-canvas { cursor: cell; }
        .ds-canvas-stage[data-tool="select"]      #ds-canvas { cursor: move; }

        /* Image stamp UI (overlay handles) */
        .ds-stamp-controls {
            position: absolute; left: 0; top: 0;
            display: none; pointer-events: auto;
        }
        .ds-stamp-controls.visible { display: block; }
        .ds-stamp-bar {
            position: absolute; left: 0; bottom: -38px;
            display: flex; gap: .25rem;
            background: var(--ds-bg-strong);
            border: 1px solid var(--ds-border);
            padding: .25rem; border-radius: .5rem;
            box-shadow: 0 4px 14px rgba(15,23,42,.15);
            white-space: nowrap;
        }

        /* Status pill */
        .ds-status {
            position: absolute; right: .75rem; bottom: .75rem;
            font-size: .72rem; padding: .25rem .55rem;
            border-radius: 999px;
            background: rgba(15,23,42,.65); color: white;
            pointer-events: none;
        }

        /* Toast */
        .ds-toast {
            position: fixed; left: 50%; bottom: 1.25rem;
            transform: translateX(-50%);
            background: rgba(15,23,42,.92); color: white;
            padding: .55rem 1rem; border-radius: 999px;
            font-size: .85rem; box-shadow: 0 10px 30px rgba(0,0,0,.3);
            z-index: 80;
        }
    </style>

    <div
        x-data="drawingStudio({
            width: {{ $this->canvasWidth }},
            height: {{ $this->canvasHeight }},
            sourceUrl: @js($this->sourceUrl),
        })"
        x-init="init()"
        @keydown.window="onKeydown($event)"
        @paste.window="onPaste($event)"
    >
        {{-- TOP TOOLBAR --}}
        <div class="ds-topbar">
            <input
                type="text"
                class="ds-title-input"
                wire:model="drawingTitle"
                placeholder="{{ __('admin.drawing.untitled') }}"
                data-tip="{{ __('admin.drawing.tip.title') }}"
                data-tip-pos="bottom"
            />

            <div class="ds-divider"></div>

            <select class="ds-select" x-model="preset" @change="applyPreset()" data-tip="{{ __('admin.drawing.tip.preset') }}" data-tip-pos="bottom">
                <option value="custom">Custom</option>
                <option value="square">Square 1080×1080</option>
                <option value="hd">HD 1920×1080</option>
                <option value="story">Story 1080×1920</option>
                <option value="banner">Banner 1500×500</option>
                <option value="a4-portrait">A4 portrait 1240×1754</option>
                <option value="a4-landscape">A4 landscape 1754×1240</option>
            </select>

            <input type="number" class="ds-input" style="width:80px" x-model.number="width"  @change="resizeCanvas()" min="64" max="4096" data-tip="{{ __('admin.drawing.tip.width') }}" data-tip-pos="bottom" />
            <span style="color: var(--ds-muted); font-size: .8rem;">×</span>
            <input type="number" class="ds-input" style="width:80px" x-model.number="height" @change="resizeCanvas()" min="64" max="4096" data-tip="{{ __('admin.drawing.tip.height') }}" data-tip-pos="bottom" />

            <div class="ds-divider"></div>

            <button type="button" class="ds-btn ds-btn--ghost" @click="undo()" :disabled="undoStack.length <= 1" data-tip="{{ __('admin.drawing.tip.undo') }}" data-tip-pos="bottom" aria-label="Undo">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4M5 10h9a5 5 0 010 10h-3"/></svg>
            </button>
            <button type="button" class="ds-btn ds-btn--ghost" @click="redo()" :disabled="redoStack.length === 0" data-tip="{{ __('admin.drawing.tip.redo') }}" data-tip-pos="bottom" aria-label="Redo">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 14l4-4-4-4M19 10h-9a5 5 0 000 10h3"/></svg>
            </button>

            <div class="ds-divider"></div>

            <button type="button" class="ds-btn" @click="$refs.fileInput.click()" data-tip="{{ __('admin.drawing.tip.image') }}" data-tip-pos="bottom">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a2 2 0 002 2h14a2 2 0 002-2v-1.5M16 10l-4-4m0 0l-4 4m4-4v12"/></svg>
                {{ __('admin.drawing.image') }}
            </button>
            <input type="file" x-ref="fileInput" accept="image/*" class="hidden" style="display:none" @change="onImageFile($event)" />

            <div style="flex:1"></div>

            <select class="ds-select" x-model="exportFormat" data-tip="{{ __('admin.drawing.tip.export_fmt') }}" data-tip-pos="bottom">
                <option value="png">PNG</option>
                <option value="jpeg">JPG</option>
            </select>
            <button type="button" class="ds-btn" @click="downloadLocal()" data-tip="{{ __('admin.drawing.tip.export') }}" data-tip-pos="bottom">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a2 2 0 002 2h14a2 2 0 002-2v-1.5M7 10l5 5 5-5M12 15V3"/></svg>
                {{ __('admin.drawing.export') }}
            </button>
            <button type="button" class="ds-btn ds-btn--accent" @click="save()" :disabled="saving" data-tip="{{ __('admin.drawing.tip.save') }}" data-tip-pos="bottom" x-text="saving ? @js(__('admin.drawing.saving')) : @js(__('admin.drawing.save_to_gallery'))"></button>
        </div>

        <div class="ds-shell">
            {{-- LEFT: TOOL RAIL --}}
            <aside class="ds-tools">
                @foreach ([
                    ['key' => 'pen',        'label' => __('admin.drawing.tools.pen'),        'hot' => 'B', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zM19.5 7.125l-3.375-3.375"/>'],
                    ['key' => 'line',       'label' => __('admin.drawing.tools.line'),       'hot' => 'L', 'svg' => '<path stroke-linecap="round" d="M4 20L20 4"/>'],
                    ['key' => 'arrow',      'label' => __('admin.drawing.tools.arrow'),      'hot' => 'A', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 20L20 4M20 4h-7M20 4v7"/>'],
                    ['key' => 'rect',       'label' => __('admin.drawing.tools.rect'),       'hot' => 'R', 'svg' => '<rect x="4" y="5" width="16" height="14" rx="1"/>'],
                    ['key' => 'ellipse',    'label' => __('admin.drawing.tools.ellipse'),    'hot' => 'O', 'svg' => '<ellipse cx="12" cy="12" rx="8" ry="6"/>'],
                    ['key' => 'polygon',    'label' => __('admin.drawing.tools.polygon'),    'hot' => 'P', 'svg' => '<path d="M12 3l9 6.5-3.4 10.5h-11L3 9.5z"/>'],
                    ['key' => 'text',       'label' => __('admin.drawing.tools.text'),       'hot' => 'T', 'svg' => '<path stroke-linecap="round" d="M4 6h16M12 6v14M9 20h6"/>'],
                    ['key' => 'bucket',     'label' => __('admin.drawing.tools.bucket'),     'hot' => 'F', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9-9 9zM3 18a3 3 0 003 3"/>'],
                    ['key' => 'eyedropper', 'label' => __('admin.drawing.tools.eyedropper'), 'hot' => 'I', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 5l3 3-9 9-4 1 1-4 9-9z"/>'],
                    ['key' => 'eraser',     'label' => __('admin.drawing.tools.eraser'),     'hot' => 'E', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75L20.25 7.5 9 18.75H5.25v-3.75L16.5 3.75zM14 6l4 4"/>'],
                ] as $t)
                <button type="button" class="ds-tool" :aria-pressed="tool === '{{ $t['key'] }}'" @click="setTool('{{ $t['key'] }}')" data-tip="{{ $t['label'] }} ({{ $t['hot'] }})" data-tip-pos="right" aria-label="{{ $t['label'] }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">{!! $t['svg'] !!}</svg>
                    <span class="ds-tool__hot">{{ $t['hot'] }}</span>
                </button>
                @endforeach
            </aside>

            {{-- CENTER: CANVAS --}}
            <div class="ds-canvas-wrap">
                <div class="ds-canvas-stage" :data-tool="tool" x-ref="stage">
                    <canvas
                        id="ds-canvas"
                        x-ref="canvas"
                        :width="width"
                        :height="height"
                        @pointerdown.prevent="onPointerDown($event)"
                        @pointermove.prevent="onPointerMove($event)"
                        @pointerup.prevent="onPointerUp($event)"
                        @pointerleave="onPointerUp($event)"
                    ></canvas>
                    <canvas id="ds-overlay" x-ref="overlay" :width="width" :height="height"></canvas>

                    <div class="ds-stamp-controls" :class="{ visible: stamp.active }">
                        <div class="ds-stamp-bar">
                            <button type="button" class="ds-btn ds-btn--accent" @click="commitStamp()">Place</button>
                            <button type="button" class="ds-btn" @click="cancelStamp()">Cancel</button>
                        </div>
                    </div>
                </div>
                <div class="ds-status" x-text="statusText()"></div>
            </div>

            {{-- RIGHT: PROPERTIES --}}
            <aside class="ds-properties">
                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.color') }}</div>
                    <div class="ds-color-row">
                        <template x-for="c in palette" :key="c">
                            <button type="button" class="ds-swatch" :style="`background:${c}`" :aria-pressed="color === c" @click="color = c" :data-tip="c" data-tip-pos="left"></button>
                        </template>
                    </div>
                    <div class="ds-color-pickers">
                        <input type="color" x-model="color" data-tip="{{ __('admin.drawing.tip.color_picker') }}" data-tip-pos="left" />
                        <input type="text" class="ds-input" x-model="color" data-tip="{{ __('admin.drawing.tip.color_hex') }}" data-tip-pos="left" />
                    </div>
                </div>

                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.brush') }}</div>
                    <div class="ds-row">
                        <input type="range" min="1" max="80" x-model.number="size" style="flex:1" data-tip="{{ __('admin.drawing.tip.brush_size') }}" data-tip-pos="left" />
                        <span class="ds-num" x-text="size + 'px'"></span>
                    </div>
                </div>

                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.opacity') }}</div>
                    <div class="ds-row">
                        <input type="range" min="10" max="100" step="5" x-model.number="opacity" style="flex:1" data-tip="{{ __('admin.drawing.tip.opacity') }}" data-tip-pos="left" />
                        <span class="ds-num" x-text="opacity + '%'"></span>
                    </div>
                </div>

                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.shape') }}</div>
                    <label class="ds-toggle" data-tip="{{ __('admin.drawing.tip.fill_shapes') }}" data-tip-pos="left">
                        <input type="checkbox" x-model="shapeFill"> {{ __('admin.drawing.tip.fill_shapes') }}
                    </label>
                    <div class="ds-row" x-show="tool === 'polygon'">
                        <label>{{ __('admin.drawing.tip.sides') }}</label>
                        <input type="range" min="3" max="12" x-model.number="polygonSides" style="flex:1" data-tip="{{ __('admin.drawing.tip.sides') }}" data-tip-pos="left" />
                        <span class="ds-num" x-text="polygonSides"></span>
                    </div>
                </div>

                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.background') }}</div>
                    <div class="ds-color-pickers">
                        <input type="color" x-model="bgColor" data-tip="{{ __('admin.drawing.tip.bg_color') }}" data-tip-pos="left" />
                        <input type="text" class="ds-input" x-model="bgColor" data-tip="{{ __('admin.drawing.tip.color_hex') }}" data-tip-pos="left" />
                        <button type="button" class="ds-btn" @click="applyBackground()" data-tip="{{ __('admin.drawing.tip.bg_apply') }}" data-tip-pos="left">{{ __('admin.drawing.apply') }}</button>
                    </div>
                </div>

                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.view') }}</div>
                    <label class="ds-toggle" data-tip="{{ __('admin.drawing.tip.show_grid') }}" data-tip-pos="left">
                        <input type="checkbox" x-model="showGrid" @change="redrawOverlay()"> {{ __('admin.drawing.tip.show_grid') }}
                    </label>
                    <div class="ds-row" x-show="showGrid">
                        <label>{{ __('admin.drawing.tip.grid_step') }}</label>
                        <input type="range" min="10" max="120" step="5" x-model.number="gridStep" @input="redrawOverlay()" style="flex:1" data-tip="{{ __('admin.drawing.tip.grid_step') }}" data-tip-pos="left" />
                        <span class="ds-num" x-text="gridStep + 'px'"></span>
                    </div>
                </div>

                <div class="ds-prop-section">
                    <div class="ds-section-label">{{ __('admin.drawing.sections.canvas') }}</div>
                    <button type="button" class="ds-btn" @click="clearCanvas()" data-tip="{{ __('admin.drawing.tip.clear') }}" data-tip-pos="left">{{ __('admin.drawing.clear_canvas') }}</button>
                </div>
            </aside>
        </div>

        {{-- Mobile-only bottom bar: shown via CSS media query, gives
             phone users the essentials (color, brush size, undo / redo,
             clear, save) without needing the desktop right panel. --}}
        <div class="ds-mobile-bar">
            <input type="color" x-model="color" aria-label="Color" />
            <input type="range" min="1" max="80" x-model.number="size" aria-label="Brush size" />
            <button type="button" class="ds-btn ds-btn--ghost" @click="undo()" :disabled="undoStack.length <= 1" aria-label="Undo">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4M5 10h9a5 5 0 010 10h-3"/></svg>
            </button>
            <button type="button" class="ds-btn ds-btn--ghost" @click="redo()" :disabled="redoStack.length === 0" aria-label="Redo">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 14l4-4-4-4M19 10h-9a5 5 0 000 10h3"/></svg>
            </button>
            <button type="button" class="ds-btn" @click="clearCanvas()" aria-label="{{ __('admin.drawing.clear_canvas') }}" style="white-space:nowrap;">⌫</button>
            <button type="button" class="ds-btn ds-btn--accent" @click="save()" :disabled="saving" style="margin-left:auto; white-space:nowrap;" x-text="saving ? @js(__('admin.drawing.saving')) : @js(__('admin.drawing.save_to_gallery'))"></button>
        </div>

        <div class="ds-toast" x-show="toast" x-transition x-text="toast" style="display:none;"></div>
    </div>

    <script>
        function drawingStudio({ width, height, sourceUrl }) {
            return {
                // canvas + tools
                width, height,
                tool: 'pen',
                color: '#0f172a',
                bgColor: '#ffffff',
                size: 6,
                opacity: 100,
                shapeFill: false,
                polygonSides: 5,
                preset: 'custom',
                exportFormat: 'png',

                palette: [
                    '#000000', '#475569', '#94a3b8', '#ef4444',
                    '#f97316', '#f59e0b', '#eab308', '#84cc16',
                    '#10b981', '#06b6d4', '#0ea5e9', '#6366f1',
                    '#8b5cf6', '#d946ef', '#ec4899', '#ffffff',
                ],

                // grid overlay
                showGrid: false,
                gridStep: 40,

                // pointer state
                drawing: false,
                start: null,
                last: null,
                snapshot: null,

                // image stamp state
                stamp: { active: false, img: null, x: 0, y: 0, w: 0, h: 0, dragMode: null, sx: 0, sy: 0, sw: 0, sh: 0, mx: 0, my: 0 },

                undoStack: [],
                redoStack: [],

                saving: false,
                toast: '',
                toastTimer: null,

                ctx: null,
                octx: null,

                init() {
                    this.ctx = this.$refs.canvas.getContext('2d');
                    this.octx = this.$refs.overlay.getContext('2d');
                    this.ctx.fillStyle = this.bgColor;
                    this.ctx.fillRect(0, 0, this.width, this.height);

                    if (sourceUrl) {
                        const img = new Image();
                        img.crossOrigin = 'anonymous';
                        img.onload = () => {
                            this.ctx.drawImage(img, 0, 0, this.width, this.height);
                            this.pushHistory();
                            this.redrawOverlay();
                        };
                        img.onerror = () => this.pushHistory();
                        img.src = sourceUrl;
                    } else {
                        this.pushHistory();
                    }
                    this.redrawOverlay();
                },

                setTool(name) {
                    if (this.stamp.active) this.commitStamp();
                    this.tool = name;
                },

                applyPreset() {
                    const p = {
                        'square':       [1080, 1080],
                        'hd':           [1920, 1080],
                        'story':        [1080, 1920],
                        'banner':       [1500, 500],
                        'a4-portrait':  [1240, 1754],
                        'a4-landscape': [1754, 1240],
                    }[this.preset];
                    if (!p) return;
                    this.width = p[0];
                    this.height = p[1];
                    this.resizeCanvas();
                },

                resizeCanvas() {
                    this.width = Math.max(64, Math.min(4096, this.width|0));
                    this.height = Math.max(64, Math.min(4096, this.height|0));
                    // Preserve current pixels by drawing existing image into the new size.
                    const prev = this.$refs.canvas.toDataURL('image/png');
                    setTimeout(() => {
                        this.ctx.fillStyle = this.bgColor;
                        this.ctx.fillRect(0, 0, this.width, this.height);
                        const img = new Image();
                        img.onload = () => {
                            this.ctx.drawImage(img, 0, 0);
                            this.pushHistory();
                            this.redrawOverlay();
                        };
                        img.src = prev;
                    }, 0);
                },

                applyBackground() {
                    // Composite the current pixels above a freshly-painted bg.
                    const tmp = document.createElement('canvas');
                    tmp.width = this.width; tmp.height = this.height;
                    const tctx = tmp.getContext('2d');
                    tctx.fillStyle = this.bgColor;
                    tctx.fillRect(0, 0, this.width, this.height);
                    tctx.drawImage(this.$refs.canvas, 0, 0);
                    this.ctx.clearRect(0, 0, this.width, this.height);
                    this.ctx.drawImage(tmp, 0, 0);
                    this.pushHistory();
                    this.flash('Background applied');
                },

                getPos(e) {
                    const r = this.$refs.canvas.getBoundingClientRect();
                    const sx = this.width / r.width;
                    const sy = this.height / r.height;
                    return {
                        x: (e.clientX - r.left) * sx,
                        y: (e.clientY - r.top) * sy,
                    };
                },

                rgba() {
                    const a = (this.opacity / 100);
                    const c = this.color.replace('#', '');
                    if (c.length !== 6) return this.color;
                    const r = parseInt(c.slice(0, 2), 16);
                    const g = parseInt(c.slice(2, 4), 16);
                    const b = parseInt(c.slice(4, 6), 16);
                    return `rgba(${r}, ${g}, ${b}, ${a})`;
                },

                onPointerDown(e) {
                    if (this.stamp.active) return this.onStampPointerDown(e);
                    this.$refs.canvas.setPointerCapture?.(e.pointerId);
                    const p = this.getPos(e);

                    if (this.tool === 'eyedropper') {
                        this.pickColor(p);
                        return;
                    }
                    if (this.tool === 'bucket') {
                        this.bucketFill(p.x|0, p.y|0);
                        this.pushHistory();
                        return;
                    }
                    if (this.tool === 'text') {
                        const text = window.prompt('Text to draw');
                        if (text) {
                            this.ctx.fillStyle = this.rgba();
                            this.ctx.font = (this.size * 3) + 'px ui-sans-serif, system-ui, sans-serif';
                            this.ctx.textBaseline = 'top';
                            this.ctx.fillText(text, p.x, p.y);
                            this.pushHistory();
                        }
                        return;
                    }

                    this.drawing = true;
                    this.start = p;
                    this.last = p;
                    this.snapshot = this.ctx.getImageData(0, 0, this.width, this.height);

                    if (this.tool === 'pen' || this.tool === 'eraser') {
                        this.ctx.beginPath();
                        this.ctx.moveTo(p.x, p.y);
                    }
                },

                onPointerMove(e) {
                    if (this.stamp.active) return this.onStampPointerMove(e);
                    if (!this.drawing) return;
                    const p = this.getPos(e);

                    if (this.tool === 'pen' || this.tool === 'eraser') {
                        this.ctx.globalCompositeOperation = this.tool === 'eraser' ? 'destination-out' : 'source-over';
                        this.ctx.strokeStyle = this.rgba();
                        this.ctx.lineWidth = this.size;
                        this.ctx.lineCap = 'round';
                        this.ctx.lineJoin = 'round';
                        this.ctx.lineTo(p.x, p.y);
                        this.ctx.stroke();
                        this.last = p;
                        return;
                    }

                    this.ctx.globalCompositeOperation = 'source-over';
                    this.ctx.putImageData(this.snapshot, 0, 0);
                    this.ctx.strokeStyle = this.rgba();
                    this.ctx.fillStyle = this.rgba();
                    this.ctx.lineWidth = this.size;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';

                    if (this.tool === 'line') {
                        this.ctx.beginPath();
                        this.ctx.moveTo(this.start.x, this.start.y);
                        this.ctx.lineTo(p.x, p.y);
                        this.ctx.stroke();
                    } else if (this.tool === 'arrow') {
                        this.drawArrow(this.start, p);
                    } else if (this.tool === 'rect') {
                        const x = Math.min(this.start.x, p.x);
                        const y = Math.min(this.start.y, p.y);
                        const w = Math.abs(p.x - this.start.x);
                        const h = Math.abs(p.y - this.start.y);
                        if (this.shapeFill) this.ctx.fillRect(x, y, w, h);
                        this.ctx.strokeRect(x, y, w, h);
                    } else if (this.tool === 'ellipse') {
                        const cx = (this.start.x + p.x) / 2;
                        const cy = (this.start.y + p.y) / 2;
                        const rx = Math.abs(p.x - this.start.x) / 2;
                        const ry = Math.abs(p.y - this.start.y) / 2;
                        this.ctx.beginPath();
                        this.ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
                        if (this.shapeFill) this.ctx.fill();
                        this.ctx.stroke();
                    } else if (this.tool === 'polygon') {
                        this.drawPolygon(this.start, p, this.polygonSides);
                    }
                },

                onPointerUp(e) {
                    if (this.stamp.active) return this.onStampPointerUp(e);
                    if (!this.drawing) return;
                    this.drawing = false;
                    this.ctx.globalCompositeOperation = 'source-over';
                    this.snapshot = null;
                    this.pushHistory();
                },

                drawArrow(a, b) {
                    const head = Math.max(this.size * 3, 12);
                    const angle = Math.atan2(b.y - a.y, b.x - a.x);
                    this.ctx.beginPath();
                    this.ctx.moveTo(a.x, a.y);
                    this.ctx.lineTo(b.x, b.y);
                    this.ctx.stroke();
                    this.ctx.beginPath();
                    this.ctx.moveTo(b.x, b.y);
                    this.ctx.lineTo(b.x - head * Math.cos(angle - Math.PI / 7), b.y - head * Math.sin(angle - Math.PI / 7));
                    this.ctx.lineTo(b.x - head * Math.cos(angle + Math.PI / 7), b.y - head * Math.sin(angle + Math.PI / 7));
                    this.ctx.closePath();
                    this.ctx.fill();
                },

                drawPolygon(a, b, sides) {
                    const cx = (a.x + b.x) / 2;
                    const cy = (a.y + b.y) / 2;
                    const r = Math.hypot(b.x - cx, b.y - cy);
                    const start = Math.atan2(b.y - cy, b.x - cx);
                    this.ctx.beginPath();
                    for (let i = 0; i < sides; i++) {
                        const a2 = start + (i * 2 * Math.PI / sides);
                        const x = cx + r * Math.cos(a2);
                        const y = cy + r * Math.sin(a2);
                        if (i === 0) this.ctx.moveTo(x, y); else this.ctx.lineTo(x, y);
                    }
                    this.ctx.closePath();
                    if (this.shapeFill) this.ctx.fill();
                    this.ctx.stroke();
                },

                /* ---- Bucket fill ---- */
                bucketFill(x, y) {
                    if (x < 0 || y < 0 || x >= this.width || y >= this.height) return;
                    const data = this.ctx.getImageData(0, 0, this.width, this.height);
                    const arr = data.data;
                    const idx = (y * this.width + x) * 4;
                    const target = [arr[idx], arr[idx+1], arr[idx+2], arr[idx+3]];
                    const c = this.color.replace('#', '');
                    if (c.length !== 6) return;
                    const fill = [parseInt(c.slice(0,2),16), parseInt(c.slice(2,4),16), parseInt(c.slice(4,6),16), Math.round(255 * (this.opacity / 100))];
                    if (target.every((v, i) => v === fill[i])) return;

                    const stack = [[x, y]];
                    const tol = 6;
                    const match = (i) => Math.abs(arr[i] - target[0]) <= tol
                        && Math.abs(arr[i+1] - target[1]) <= tol
                        && Math.abs(arr[i+2] - target[2]) <= tol
                        && Math.abs(arr[i+3] - target[3]) <= tol;

                    while (stack.length) {
                        const [px, py] = stack.pop();
                        if (px < 0 || py < 0 || px >= this.width || py >= this.height) continue;
                        const i = (py * this.width + px) * 4;
                        if (!match(i)) continue;
                        arr[i] = fill[0]; arr[i+1] = fill[1]; arr[i+2] = fill[2]; arr[i+3] = fill[3];
                        stack.push([px+1, py], [px-1, py], [px, py+1], [px, py-1]);
                    }
                    this.ctx.putImageData(data, 0, 0);
                },

                pickColor(p) {
                    const d = this.ctx.getImageData(p.x|0, p.y|0, 1, 1).data;
                    const hex = '#' + [d[0], d[1], d[2]].map(v => v.toString(16).padStart(2, '0')).join('');
                    this.color = hex;
                    this.flash('Picked ' + hex);
                    this.tool = 'pen';
                },

                /* ---- Image insertion / paste ---- */
                onImageFile(e) {
                    const file = e.target.files?.[0];
                    if (file) this.loadImageFile(file);
                    e.target.value = '';
                },
                onPaste(e) {
                    const items = e.clipboardData?.items;
                    if (!items) return;
                    for (const it of items) {
                        if (it.type?.startsWith('image/')) {
                            const f = it.getAsFile();
                            if (f) { e.preventDefault(); this.loadImageFile(f); return; }
                        }
                    }
                },
                loadImageFile(file) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const img = new Image();
                        img.onload = () => this.beginStamp(img);
                        img.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                },
                beginStamp(img) {
                    // Fit by default into 60% of canvas, centred
                    const ratio = img.width / img.height;
                    let w = Math.min(img.width, this.width * 0.6);
                    let h = w / ratio;
                    if (h > this.height * 0.6) {
                        h = this.height * 0.6;
                        w = h * ratio;
                    }
                    this.stamp = {
                        active: true,
                        img,
                        x: (this.width - w) / 2,
                        y: (this.height - h) / 2,
                        w, h,
                        dragMode: null, sx: 0, sy: 0, sw: 0, sh: 0, mx: 0, my: 0,
                    };
                    this.redrawOverlay();
                    this.flash('Drag to position, Enter to place, Esc to cancel');
                },
                onStampPointerDown(e) {
                    const p = this.getPos(e);
                    const s = this.stamp;
                    const handle = 14;
                    // bottom-right resize handle
                    if (p.x >= s.x + s.w - handle && p.x <= s.x + s.w + handle && p.y >= s.y + s.h - handle && p.y <= s.y + s.h + handle) {
                        s.dragMode = 'resize';
                    } else if (p.x >= s.x && p.x <= s.x + s.w && p.y >= s.y && p.y <= s.y + s.h) {
                        s.dragMode = 'move';
                    } else {
                        return;
                    }
                    s.sx = s.x; s.sy = s.y; s.sw = s.w; s.sh = s.h; s.mx = p.x; s.my = p.y;
                    this.$refs.canvas.setPointerCapture?.(e.pointerId);
                },
                onStampPointerMove(e) {
                    const s = this.stamp;
                    if (!s.dragMode) return;
                    const p = this.getPos(e);
                    if (s.dragMode === 'move') {
                        s.x = s.sx + (p.x - s.mx);
                        s.y = s.sy + (p.y - s.my);
                    } else {
                        const ratio = s.sw / s.sh;
                        s.w = Math.max(20, s.sw + (p.x - s.mx));
                        s.h = s.w / ratio;
                    }
                    this.redrawOverlay();
                },
                onStampPointerUp() {
                    this.stamp.dragMode = null;
                },
                commitStamp() {
                    const s = this.stamp;
                    if (!s.active || !s.img) return;
                    this.ctx.drawImage(s.img, s.x, s.y, s.w, s.h);
                    this.stamp = { active: false, img: null, x: 0, y: 0, w: 0, h: 0, dragMode: null, sx: 0, sy: 0, sw: 0, sh: 0, mx: 0, my: 0 };
                    this.pushHistory();
                    this.redrawOverlay();
                },
                cancelStamp() {
                    this.stamp = { active: false, img: null, x: 0, y: 0, w: 0, h: 0, dragMode: null, sx: 0, sy: 0, sw: 0, sh: 0, mx: 0, my: 0 };
                    this.redrawOverlay();
                },

                /* ---- Overlay (grid + stamp preview) ---- */
                redrawOverlay() {
                    if (!this.octx) return;
                    this.octx.clearRect(0, 0, this.width, this.height);

                    if (this.showGrid) {
                        this.octx.save();
                        this.octx.strokeStyle = 'rgba(15,23,42,.10)';
                        this.octx.lineWidth = 1;
                        for (let x = this.gridStep; x < this.width; x += this.gridStep) {
                            this.octx.beginPath(); this.octx.moveTo(x, 0); this.octx.lineTo(x, this.height); this.octx.stroke();
                        }
                        for (let y = this.gridStep; y < this.height; y += this.gridStep) {
                            this.octx.beginPath(); this.octx.moveTo(0, y); this.octx.lineTo(this.width, y); this.octx.stroke();
                        }
                        this.octx.restore();
                    }

                    if (this.stamp.active && this.stamp.img) {
                        const s = this.stamp;
                        this.octx.save();
                        this.octx.globalAlpha = .92;
                        this.octx.drawImage(s.img, s.x, s.y, s.w, s.h);
                        this.octx.globalAlpha = 1;
                        this.octx.strokeStyle = 'rgba(245, 158, 11, .9)';
                        this.octx.setLineDash([6, 4]);
                        this.octx.lineWidth = 2;
                        this.octx.strokeRect(s.x, s.y, s.w, s.h);
                        this.octx.setLineDash([]);
                        // resize handle
                        this.octx.fillStyle = 'rgba(245, 158, 11, 1)';
                        this.octx.fillRect(s.x + s.w - 7, s.y + s.h - 7, 14, 14);
                        this.octx.restore();
                    }
                },

                /* ---- History ---- */
                pushHistory() {
                    try {
                        this.undoStack.push(this.$refs.canvas.toDataURL('image/png'));
                        if (this.undoStack.length > 40) this.undoStack.shift();
                        this.redoStack = [];
                    } catch (e) { /* ignore */ }
                },
                restore(dataUrl) {
                    const img = new Image();
                    img.onload = () => {
                        this.ctx.clearRect(0, 0, this.width, this.height);
                        this.ctx.drawImage(img, 0, 0);
                    };
                    img.src = dataUrl;
                },
                undo() {
                    if (this.undoStack.length <= 1) return;
                    this.redoStack.push(this.undoStack.pop());
                    this.restore(this.undoStack[this.undoStack.length - 1]);
                },
                redo() {
                    if (!this.redoStack.length) return;
                    const next = this.redoStack.pop();
                    this.undoStack.push(next);
                    this.restore(next);
                },
                clearCanvas() {
                    if (!confirm('Clear the canvas?')) return;
                    this.ctx.clearRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = this.bgColor;
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.pushHistory();
                },

                /* ---- Save / export ---- */
                async save() {
                    if (this.saving) return;
                    if (this.stamp.active) this.commitStamp();
                    this.saving = true;
                    try {
                        const dataUrl = this.$refs.canvas.toDataURL('image/png');
                        await @this.call('save', dataUrl, this.width, this.height);
                    } finally {
                        this.saving = false;
                    }
                },
                downloadLocal() {
                    if (this.stamp.active) this.commitStamp();
                    const mime = this.exportFormat === 'jpeg' ? 'image/jpeg' : 'image/png';
                    const url = this.exportFormat === 'jpeg'
                        ? this.flattenJpegDataUrl()
                        : this.$refs.canvas.toDataURL(mime);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = (document.querySelector('.ds-title-input')?.value || 'drawing').replace(/[^a-z0-9_\-]+/gi, '_') + (this.exportFormat === 'jpeg' ? '.jpg' : '.png');
                    a.click();
                },
                flattenJpegDataUrl() {
                    const tmp = document.createElement('canvas');
                    tmp.width = this.width; tmp.height = this.height;
                    const t = tmp.getContext('2d');
                    t.fillStyle = this.bgColor;
                    t.fillRect(0, 0, this.width, this.height);
                    t.drawImage(this.$refs.canvas, 0, 0);
                    return tmp.toDataURL('image/jpeg', 0.92);
                },

                /* ---- Keyboard ---- */
                onKeydown(e) {
                    if (e.target?.tagName === 'INPUT' || e.target?.tagName === 'TEXTAREA') return;
                    const meta = e.ctrlKey || e.metaKey;
                    if (meta && (e.key === 'z' || e.key === 'Z') && !e.shiftKey) { e.preventDefault(); this.undo(); return; }
                    if (meta && ((e.key === 'z' || e.key === 'Z') && e.shiftKey || e.key === 'y' || e.key === 'Y')) { e.preventDefault(); this.redo(); return; }
                    if (e.key === 'Escape' && this.stamp.active) { e.preventDefault(); this.cancelStamp(); return; }
                    if (e.key === 'Enter' && this.stamp.active) { e.preventDefault(); this.commitStamp(); return; }
                    const map = { b: 'pen', l: 'line', a: 'arrow', r: 'rect', o: 'ellipse', p: 'polygon', t: 'text', f: 'bucket', i: 'eyedropper', e: 'eraser' };
                    const t = map[e.key.toLowerCase?.()];
                    if (t) { this.setTool(t); }
                },

                statusText() {
                    if (this.stamp.active) return 'Image: drag to move, corner to resize, Enter to place';
                    return `${this.tool}  ·  ${this.size}px  ·  ${this.opacity}%`;
                },

                flash(msg) {
                    this.toast = msg;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => this.toast = '', 2000);
                },
            };
        }
    </script>
</x-filament-panels::page>
