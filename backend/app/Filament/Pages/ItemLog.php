<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\ItemStock;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Who changed what in the inventory.
 *
 * The catalog and the stock table are open to everyone signed in — that is
 * a deliberate call, the whole team moves hardware around. The trade for
 * open write access is traceability: Item and ItemStock both carry
 * {@see \App\Concerns\LogsActivity}, and this page surfaces just their
 * slice of activity_logs to the same audience. (The full audit feed at
 * Membership → Activity log stays admin-only; it spans every resource.)
 *
 * Read-only: no actions, no bulk actions, nothing writes from here.
 */
class ItemLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Advanced';

    protected static ?int $navigationSort = 82;

    protected static ?string $slug = 'item-log';

    protected string $view = 'filament.pages.item-log';

    public static function getNavigationLabel(): string
    {
        return __('admin.items.log_nav');
    }

    public function getTitle(): string
    {
        return __('admin.items.log_title');
    }

    public function getHeading(): string
    {
        return __('admin.items.log_title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.items.log_sub');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityLog::query()
                    ->with('user:id,name')
                    ->whereIn('subject_type', [Item::class, ItemStock::class]),
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('admin.common.actor'))
                    ->placeholder(__('admin.common.system'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('event')
                    ->label(__('admin.common.event'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.activity.events.' . $state))
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),
                TextColumn::make('subject_label')
                    ->label(__('admin.common.subject'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('changes')
                    ->label(__('admin.items.log_changes'))
                    // The raw diff is a nested {field: {from, to}} map;
                    // flatten it to one readable line per changed column.
                    ->state(fn (ActivityLog $r) => self::summariseChanges($r))
                    ->wrap()
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('subject_type')
                    ->label(__('admin.common.type'))
                    ->options([
                        Item::class => __('admin.resources.item.s'),
                        ItemStock::class => __('admin.items.section_location'),
                    ]),
                SelectFilter::make('event')
                    ->label(__('admin.common.event'))
                    ->options([
                        'created' => __('admin.activity.events.created'),
                        'updated' => __('admin.activity.events.updated'),
                        'deleted' => __('admin.activity.events.deleted'),
                    ]),
            ])
            ->emptyStateHeading(__('admin.items.log_empty_h'))
            ->emptyStateDescription(__('admin.items.log_empty_b'))
            ->emptyStateIcon('heroicon-o-clock');
    }

    private static function summariseChanges(ActivityLog $log): string
    {
        $changes = $log->changes;

        if (! is_array($changes) || $changes === []) {
            return '—';
        }

        $parts = [];
        foreach ($changes as $field => $diff) {
            if (is_array($diff) && array_key_exists('to', $diff)) {
                $from = $diff['from'] ?? '—';
                $parts[] = $field . ': ' . self::scalar($from) . ' → ' . self::scalar($diff['to']);
            } else {
                $parts[] = $field . ': ' . self::scalar($diff);
            }
        }

        return implode(' · ', $parts);
    }

    private static function scalar(mixed $value): string
    {
        if ($value === null || $value === '') return '—';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_scalar($value)) return (string) $value;

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
    }
}
