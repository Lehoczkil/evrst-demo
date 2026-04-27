@php
    $tables = $this->getTablesIndex();
    $details = $this->getSelectedTableDetails();
    $driver = $this->getDriver();
@endphp

<x-filament-panels::page>
    <style>
        .dbi-shell {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1rem;
            min-height: 70vh;
        }
        @media (max-width: 900px) {
            .dbi-shell { grid-template-columns: 1fr; }
            .dbi-list { max-height: 12rem; }
        }
        .dbi-card {
            background: rgba(255,255,255,.6);
            border: 1px solid rgba(15,23,42,.08);
            border-radius: .75rem;
            overflow: hidden;
        }
        .dark .dbi-card {
            background: rgba(15,23,42,.4);
            border-color: rgba(255,255,255,.08);
        }
        .dbi-driver {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .7rem; padding: .15rem .55rem;
            background: rgba(245,158,11,.15);
            color: rgb(146 64 14);
            border-radius: 9999px;
            margin-left: .5rem;
            text-transform: uppercase; letter-spacing: .05em; font-weight: 700;
        }
        .dark .dbi-driver { background: rgba(245,158,11,.18); color: rgb(252 211 77); }

        .dbi-list {
            overflow: auto;
            display: flex; flex-direction: column;
        }
        .dbi-list-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: .55rem .75rem;
            border-bottom: 1px solid rgba(15,23,42,.06);
            cursor: pointer;
            font-size: .85rem;
            transition: background-color .12s ease;
            color: rgb(15 23 42);
        }
        .dark .dbi-list-row {
            color: rgb(226 232 240);
            border-bottom-color: rgba(255,255,255,.06);
        }
        .dbi-list-row:last-child { border-bottom: 0; }
        .dbi-list-row:hover {
            background: rgba(245,158,11,.08);
        }
        .dbi-list-row[aria-selected="true"] {
            background: rgba(245,158,11,.16);
            color: rgb(146 64 14);
            font-weight: 600;
        }
        .dark .dbi-list-row[aria-selected="true"] {
            background: rgba(245,158,11,.18);
            color: rgb(252 211 77);
        }
        .dbi-list-row code {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: .8rem;
        }
        .dbi-row-count {
            font-size: .68rem;
            background: rgba(15,23,42,.08);
            padding: 1px 7px; border-radius: 999px;
            color: rgb(71 85 105);
            font-variant-numeric: tabular-nums;
        }
        .dark .dbi-row-count {
            background: rgba(255,255,255,.08);
            color: rgb(148 163 184);
        }

        .dbi-detail-head {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(15,23,42,.08);
            display: flex; align-items: baseline; gap: .75rem;
            flex-wrap: wrap;
        }
        .dark .dbi-detail-head { border-bottom-color: rgba(255,255,255,.08); }
        .dbi-detail-name {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 1.05rem; font-weight: 700;
            color: rgb(15 23 42);
        }
        .dark .dbi-detail-name { color: rgb(241 245 249); }
        .dbi-detail-rows {
            font-size: .8rem; color: rgb(100 116 139);
            font-variant-numeric: tabular-nums;
        }

        .dbi-section {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(15,23,42,.06);
        }
        .dark .dbi-section { border-bottom-color: rgba(255,255,255,.06); }
        .dbi-section:last-child { border-bottom: 0; }
        .dbi-section-title {
            font-size: .68rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            color: rgb(100 116 139);
            margin-bottom: .55rem;
        }

        .dbi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
        }
        .dbi-table th {
            text-align: left;
            font-size: .65rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .04em;
            color: rgb(100 116 139);
            padding: .35rem .55rem;
            border-bottom: 1px solid rgba(15,23,42,.08);
        }
        .dark .dbi-table th { border-bottom-color: rgba(255,255,255,.1); }
        .dbi-table td {
            padding: .4rem .55rem;
            border-bottom: 1px solid rgba(15,23,42,.04);
            color: rgb(30 41 59);
            vertical-align: top;
        }
        .dark .dbi-table td {
            border-bottom-color: rgba(255,255,255,.04);
            color: rgb(226 232 240);
        }
        .dbi-table tr:last-child td { border-bottom: 0; }
        .dbi-table code {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: .75rem;
        }

        .dbi-tag {
            display: inline-flex; align-items: center;
            font-size: .62rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em;
            padding: 1px 7px; border-radius: 999px;
            margin-right: .25rem;
        }
        .dbi-tag--pk { background: rgba(245,158,11,.18); color: rgb(146 64 14); }
        .dbi-tag--unique { background: rgba(14,165,233,.16); color: rgb(7 89 133); }
        .dbi-tag--null { background: rgba(100,116,139,.16); color: rgb(71 85 105); }
        .dbi-tag--fk { background: rgba(16,185,129,.16); color: rgb(6 95 70); }
        .dbi-tag--idx { background: rgba(139,92,246,.16); color: rgb(91 33 182); }
        .dark .dbi-tag--pk { color: rgb(252 211 77); }
        .dark .dbi-tag--unique { color: rgb(125 211 252); }
        .dark .dbi-tag--null { color: rgb(148 163 184); }
        .dark .dbi-tag--fk { color: rgb(110 231 183); }
        .dark .dbi-tag--idx { color: rgb(196 181 253); }

        .dbi-empty {
            padding: 2rem 1.25rem;
            text-align: center;
            color: rgb(100 116 139);
            font-size: .85rem;
        }
    </style>

    <div class="dbi-shell">
        <aside class="dbi-card dbi-list">
            @foreach ($tables as $t)
                <button
                    type="button"
                    wire:click="selectTable(@js($t['name']))"
                    aria-selected="{{ $t['name'] === $selectedTable ? 'true' : 'false' }}"
                    class="dbi-list-row"
                >
                    <code>{{ $t['name'] }}</code>
                    <span class="dbi-row-count">{{ number_format($t['rows']) }}</span>
                </button>
            @endforeach
        </aside>

        <section class="dbi-card">
            @if ($details === null)
                <div class="dbi-empty">{{ __('admin.db_inspector.empty') }}</div>
            @else
                <div class="dbi-detail-head">
                    <div class="dbi-detail-name">{{ $details['name'] }}</div>
                    <span class="dbi-driver">{{ $driver }}</span>
                    <div style="flex: 1"></div>
                    <div class="dbi-detail-rows">
                        {{ __('admin.db_inspector.row_count', ['count' => number_format($details['row_count'])]) }}
                    </div>
                </div>

                <div class="dbi-section">
                    <div class="dbi-section-title">{{ __('admin.db_inspector.columns') }} ({{ count($details['columns']) }})</div>
                    <table class="dbi-table">
                        <thead>
                            <tr>
                                <th>{{ __('admin.db_inspector.name') }}</th>
                                <th>{{ __('admin.db_inspector.type') }}</th>
                                <th>{{ __('admin.db_inspector.flags') }}</th>
                                <th>{{ __('admin.db_inspector.default') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($details['columns'] as $col)
                                <tr>
                                    <td><code>{{ $col['name'] }}</code></td>
                                    <td><code>{{ $col['type'] ?? $col['type_name'] ?? '' }}</code></td>
                                    <td>
                                        @if (! ($col['nullable'] ?? false))
                                            <span class="dbi-tag dbi-tag--pk" style="background:rgba(15,23,42,.08);color:rgb(15 23 42);">NOT NULL</span>
                                        @else
                                            <span class="dbi-tag dbi-tag--null">nullable</span>
                                        @endif
                                        @if ($col['auto_increment'] ?? false)
                                            <span class="dbi-tag dbi-tag--pk">auto</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (($col['default'] ?? null) !== null)
                                            <code>{{ $col['default'] }}</code>
                                        @else
                                            <span style="color: rgb(148 163 184);">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="dbi-section">
                    <div class="dbi-section-title">{{ __('admin.db_inspector.indexes') }} ({{ count($details['indexes']) }})</div>
                    @if (empty($details['indexes']))
                        <div style="font-size: .8rem; color: rgb(148 163 184);">{{ __('admin.db_inspector.no_indexes') }}</div>
                    @else
                        <table class="dbi-table">
                            <thead>
                                <tr>
                                    <th>{{ __('admin.db_inspector.name') }}</th>
                                    <th>{{ __('admin.db_inspector.columns') }}</th>
                                    <th>{{ __('admin.db_inspector.flags') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details['indexes'] as $idx)
                                    <tr>
                                        <td><code>{{ $idx['name'] ?: '—' }}</code></td>
                                        <td><code>{{ implode(', ', $idx['columns'] ?? []) }}</code></td>
                                        <td>
                                            @if ($idx['primary'] ?? false)
                                                <span class="dbi-tag dbi-tag--pk">primary</span>
                                            @endif
                                            @if ($idx['unique'] ?? false)
                                                <span class="dbi-tag dbi-tag--unique">unique</span>
                                            @endif
                                            @if (! ($idx['primary'] ?? false) && ! ($idx['unique'] ?? false))
                                                <span class="dbi-tag dbi-tag--idx">index</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="dbi-section">
                    <div class="dbi-section-title">{{ __('admin.db_inspector.foreign_keys') }} ({{ count($details['foreign_keys']) }})</div>
                    @if (empty($details['foreign_keys']))
                        <div style="font-size: .8rem; color: rgb(148 163 184);">{{ __('admin.db_inspector.no_fks') }}</div>
                    @else
                        <table class="dbi-table">
                            <thead>
                                <tr>
                                    <th>{{ __('admin.db_inspector.columns') }}</th>
                                    <th>{{ __('admin.db_inspector.references') }}</th>
                                    <th>{{ __('admin.db_inspector.on_update') }}</th>
                                    <th>{{ __('admin.db_inspector.on_delete') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details['foreign_keys'] as $fk)
                                    <tr>
                                        <td><code>{{ implode(', ', $fk['columns'] ?? []) }}</code></td>
                                        <td>
                                            <code>{{ $fk['foreign_table'] ?? '?' }}</code>
                                            (<code>{{ implode(', ', $fk['foreign_columns'] ?? []) }}</code>)
                                        </td>
                                        <td><code>{{ $fk['on_update'] ?? '—' }}</code></td>
                                        <td><code>{{ $fk['on_delete'] ?? '—' }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
