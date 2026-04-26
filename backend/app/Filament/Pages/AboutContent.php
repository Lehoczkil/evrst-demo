<?php

namespace App\Filament\Pages;

use App\Auth\Perm;
use App\Models\Resource as ResourceModel;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AboutContent extends Page
{
    protected string $view = 'filament.pages.about-content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $title = 'About content';

    protected static ?string $navigationLabel = 'Content';

    protected static string|\UnitEnum|null $navigationGroup = 'About';

    protected static ?int $navigationSort = 10;

    protected const VIEW_RESOURCE_ID = '90116104-aefd-4240-8e0d-8887668e21a0';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $record = $this->view();
        $payload = $record->payload ?? [];
        $title = $payload['title'] ?? 'About';
        $content = $payload['content'] ?? '';

        $this->form->fill([
            'title' => is_array($title) ? ($title['en'] ?? '') : $title,
            'title_hu' => is_array($title) ? ($title['hu'] ?? '') : '',
            'content_en' => is_array($content) ? ($content['en'] ?? '') : (string) $content,
            'content_hu' => is_array($content) ? ($content['hu'] ?? '') : '',
        ]);
    }

    protected function view(): ResourceModel
    {
        return ResourceModel::firstOrNew(['id' => self::VIEW_RESOURCE_ID]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Who we are')
                    ->description('This text is shown above the project / goal cards on the home page.')
                    ->components([
                        TextInput::make('title')
                            ->label('Heading (EN)')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('title_hu')
                            ->label('Heading (HU)')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('content_en')
                            ->label('Body (EN)')
                            ->rows(8)
                            ->maxLength(2000)
                            ->required(),
                        Textarea::make('content_hu')
                            ->label('Body (HU)')
                            ->rows(8)
                            ->maxLength(2000)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $payload = $this->form->getState();

        $record = $this->view();
        $record->id = self::VIEW_RESOURCE_ID;
        $record->collection_id = 'f2a4ad4c-f5b8-4d7f-9c2c-9d4d6c0b3aaa';
        $record->payload = array_merge($record->payload ?? [], [
            'name' => 'about',
            'title' => [
                'en' => $payload['title'],
                'hu' => $payload['title_hu'],
            ],
            'content' => [
                'en' => $payload['content_en'],
                'hu' => $payload['content_hu'],
            ],
        ]);
        $record->save();

        Notification::make()
            ->title('About content saved')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save')
                ->action('save')
                ->color('primary'),
        ];
    }
}
