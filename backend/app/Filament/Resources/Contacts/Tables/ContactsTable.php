<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Auth\Perm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.contacts.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(),
                TextColumn::make('group.name')
                    ->label(__('admin.contacts.group'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('admin.contacts.email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label(__('admin.contacts.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.contacts.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('contact_group_id')
                    ->label(__('admin.contacts.group'))
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
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
            ->emptyStateHeading(__('admin.contacts.empty_heading'))
            ->emptyStateDescription(__('admin.contacts.empty_body'))
            ->emptyStateIcon('heroicon-o-identification');
    }
}
