<?php

namespace App\Filament\Resources\Cms\Mentors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MentorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('photo')
                    ->disk('public')
                    ->circular()
                    ->size(48)
                    ->label(''),
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->label(__('admin.common.email'))
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.mentors_h'))
            ->emptyStateDescription(__('admin.empty.mentors_b'))
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
