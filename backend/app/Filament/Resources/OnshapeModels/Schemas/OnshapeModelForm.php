<?php

namespace App\Filament\Resources\OnshapeModels\Schemas;

use App\Models\OnshapeModel;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OnshapeModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.onshape.section_meta'))
                    ->components([
                        TextInput::make('title')
                            ->label(__('admin.common.title'))
                            ->required()
                            ->maxLength(200)
                            ->columnSpan(['default' => 12, 'md' => 8]),
                        Textarea::make('description')
                            ->label(__('admin.common.description'))
                            ->rows(2)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(12),

                Section::make(__('admin.onshape.section_link'))
                    ->description(__('admin.onshape.paste_help'))
                    ->components([
                        TextInput::make('share_url')
                            ->label(__('admin.onshape.share_url'))
                            ->placeholder('https://cad.onshape.com/documents/...')
                            ->url()
                            ->maxLength(500)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                $ids = OnshapeModel::parseShareUrl($state);
                                if ($ids['document_id'])  $set('document_id', $ids['document_id']);
                                if ($ids['workspace_id']) $set('workspace_id', $ids['workspace_id']);
                                if ($ids['element_id'])   $set('element_id', $ids['element_id']);
                            })
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.onshape_share_url'))
                            ->columnSpanFull(),
                        TextInput::make('document_id')
                            ->label(__('admin.onshape.document_id'))
                            ->required()
                            ->maxLength(64)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        TextInput::make('workspace_id')
                            ->label(__('admin.onshape.workspace_id'))
                            ->required()
                            ->maxLength(64)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        TextInput::make('element_id')
                            ->label(__('admin.onshape.element_id'))
                            ->maxLength(64)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                    ])
                    ->columns(12),

                Section::make(__('admin.onshape.section_preview'))
                    ->visible(fn ($record) => $record !== null)
                    ->components([
                        \Filament\Schemas\Components\View::make('filament.resources.onshape-models.components.embed')
                            ->viewData(fn ($record) => ['model' => $record])
                            ->columnSpanFull(),
                    ])
                    ->columns(12),

                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(fn ($state) => $state !== null),
            ]);
    }
}
