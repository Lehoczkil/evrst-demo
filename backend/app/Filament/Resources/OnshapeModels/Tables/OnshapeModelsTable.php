<?php

namespace App\Filament\Resources\OnshapeModels\Tables;

use App\Models\OnshapeModel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OnshapeModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label(__('admin.drawing.author'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('document_id')
                    ->label(__('admin.onshape.document_id'))
                    ->limit(12)
                    ->color('gray')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('admin.drawing.author'))
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('open_in_onshape')
                    ->label(__('admin.onshape.view_in_onshape'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (OnshapeModel $r) => $r->share_url ?: $r->embed_url, true),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.onshape.empty_heading'))
            ->emptyStateDescription(__('admin.onshape.empty_body'))
            ->emptyStateIcon('heroicon-o-cube');
    }
}
