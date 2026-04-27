<?php

namespace App\Filament\Resources\Drawings\Pages;

use App\Filament\Resources\Drawings\DrawingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDrawings extends ListRecords
{
    protected static string $resource = DrawingResource::class;

    public function getTitle(): string
    {
        return __('admin.drawing.gallery_title');
    }

    public function getHeading(): string
    {
        return __('admin.drawing.gallery_title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.drawing.gallery_sub');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newDrawing')
                ->label(__('admin.drawing.new'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(DrawingResource::getUrl('create')),
        ];
    }
}
