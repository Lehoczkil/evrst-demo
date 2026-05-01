<?php

namespace App\Filament\Resources\Cms\AboutProjects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AboutProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')->toggleable(),
                TextColumn::make('description')
                    ->label(__('admin.common.description'))
                    ->limit(80)
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.about_projects_h'))
            ->emptyStateDescription(__('admin.empty.about_projects_b'))
            ->emptyStateIcon('heroicon-o-rocket-launch');
    }
}
