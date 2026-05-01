<?php

namespace App\Filament\Resources\ContactGroups\Tables;

use App\Auth\Perm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('contacts'))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.contacts.group_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('admin.contacts.group_description'))
                    ->limit(80)
                    ->wrap()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('contacts_count')
                    ->label(__('admin.contacts.contacts_count'))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('admin.contacts.position'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false),
                ]),
            ])
            ->emptyStateHeading(__('admin.contacts.groups_empty_heading'))
            ->emptyStateDescription(__('admin.contacts.groups_empty_body'))
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }
}
