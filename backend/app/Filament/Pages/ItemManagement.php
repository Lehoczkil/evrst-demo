<?php

namespace App\Filament\Pages;

use App\Models\Cms\TeamMember;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Venue;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Inventory view: one row per (item, venue, optional owner) — i.e. the
 * `item_stocks` table. The Items resource handles the *catalog*; this
 * page handles the actual where-is-what tracking.
 */
class ItemManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Advanced';

    protected static ?int $navigationSort = 81;

    protected string $view = 'filament.pages.item-management';

    public static function getNavigationLabel(): string
    {
        return __('admin.items.management_nav');
    }

    public function getTitle(): string
    {
        return __('admin.items.management_title');
    }

    public function getHeading(): string
    {
        return __('admin.items.management_title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.items.management_sub');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ItemStock::query()->with(['item', 'venue', 'ownerTeamMember']))
            ->defaultSort('item_id')
            ->recordAction('edit')
            ->columns([
                TextColumn::make('item.name')
                    ->label(__('admin.resources.item.s'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('quantity')
                    ->label(__('admin.items.quantity'))
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('venue.name')
                    ->label(__('admin.items.venue'))
                    ->badge()
                    ->color(fn ($record) => $record?->venue?->isPrivate() ? 'warning' : 'info')
                    ->sortable(),
                TextColumn::make('ownerTeamMember.name')
                    ->label(__('admin.items.owner'))
                    ->placeholder('—')
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.items.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('venue_id')
                    ->label(__('admin.items.venue'))
                    ->options(fn () => Venue::query()->pluck('name', 'id')->all()),
                SelectFilter::make('item_id')
                    ->label(__('admin.resources.item.s'))
                    ->relationship('item', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(__('admin.items.edit_stock'))
                    ->modalWidth('lg')
                    ->schema(fn (Schema $schema) => self::stockForm($schema)),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('admin.items.empty_stock_heading'))
            ->emptyStateDescription(__('admin.items.empty_stock_body'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.items.add_stock'))
                ->modalHeading(__('admin.items.add_stock'))
                ->modalWidth('lg')
                ->icon('heroicon-o-plus')
                ->model(ItemStock::class)
                ->schema(fn (Schema $schema) => self::stockForm($schema)),
        ];
    }

    /**
     * Shared schema for create + edit. Item picker supports
     * createOption so a brand-new catalog entry can be added inline
     * without bouncing to the Items resource.
     */
    protected static function stockForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('item_id')
                ->label(__('admin.resources.item.s'))
                ->options(fn () => Item::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label(__('admin.items.name'))
                        ->required()
                        ->maxLength(200),
                ])
                ->createOptionUsing(fn (array $data) => Item::create($data)->getKey()),
            TextInput::make('quantity')
                ->label(__('admin.items.quantity'))
                ->numeric()
                ->minValue(0)
                ->default(1)
                ->required(),
            Select::make('venue_id')
                ->label(__('admin.items.venue'))
                ->options(fn () => Venue::query()->pluck('name', 'id')->all())
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    $venue = $state ? Venue::find($state) : null;
                    if (! $venue || ! $venue->isPrivate()) {
                        $set('owner_team_member_id', null);
                    }
                }),
            Select::make('owner_team_member_id')
                ->label(__('admin.items.owner'))
                ->options(fn () => TeamMember::query()
                    ->get()
                    ->mapWithKeys(fn (TeamMember $tm) => [$tm->id => $tm->name ?? $tm->id])
                    ->all())
                ->searchable()
                ->placeholder(__('admin.items.owner_placeholder'))
                ->visible(fn (callable $get) => self::isPrivate($get('venue_id')))
                ->required(fn (callable $get) => self::isPrivate($get('venue_id')))
                ->dehydrated(fn ($state, callable $get) => self::isPrivate($get('venue_id'))),
        ]);
    }

    protected static function isPrivate(int|string|null $venueId): bool
    {
        if (! $venueId) return false;
        return Venue::find($venueId)?->isPrivate() ?? false;
    }
}
