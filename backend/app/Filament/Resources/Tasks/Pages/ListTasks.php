<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label(__('admin.tasks.kanban'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(TaskResource::getUrl('kanban')),
            CreateAction::make(),
        ];
    }
}
