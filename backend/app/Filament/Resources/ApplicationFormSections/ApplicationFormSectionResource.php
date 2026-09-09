<?php

namespace App\Filament\Resources\ApplicationFormSections;

use App\Auth\Perm;
use App\Filament\Resources\ApplicationFormSections\Pages\ManageApplicationFormSections;
use App\Models\ApplicationFormSection;
use App\Models\TeamMemberGroup;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The numbered cards the join-us form is split into. Small enough to live
 * on a single manage page — create, rename, reorder, done.
 */
class ApplicationFormSectionResource extends Resource
{
    protected static ?string $model = ApplicationFormSection::class;

    protected static ?string $slug = 'application-form-sections';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Membership';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string { return __('admin.resources.application_form_section.p'); }
    public static function getModelLabel(): string { return __('admin.resources.application_form_section.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.application_form_section.p'); }

    public static function canViewAny(): bool { return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false; }
    public static function canCreate(): bool  { return auth()->user()?->can(Perm::CONTENT_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false; }

    /** Deleting a section would cascade to its questions and their answers. */
    public static function canDelete($record): bool
    {
        return $record->fields()->doesntExist() && (auth()->user()?->can(Perm::CONTENT_DELETE) ?? false);
    }

    public static function canDeleteAny(): bool { return false; }

    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                TextInput::make('title_en')
                    ->label(__('admin.application_form.section_title') . ' (EN)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('title_hu')
                    ->label(__('admin.application_form.section_title') . ' (HU)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('key')
                    ->label(__('admin.application_form.key'))
                    ->required()
                    ->maxLength(64)
                    ->rule('regex:/^[a-z][a-z0-9\-]*$/')
                    ->validationMessages(['regex' => __('admin.application_form.section_key_invalid')])
                    ->unique(ignoreRecord: true)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Toggle::make('is_active')
                    ->label(__('admin.application_form.active'))
                    ->default(true)
                    ->columnSpan(['default' => 12, 'md' => 4]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.application_form.section_title'))
                    ->state(fn (ApplicationFormSection $r) => TeamMemberGroup::pickLocale($r->title) ?: $r->key)
                    ->weight('semibold'),
                TextColumn::make('key')
                    ->label(__('admin.application_form.key'))
                    ->color('gray'),
                TextColumn::make('fields_count')
                    ->label(__('admin.resources.application_form.p'))
                    ->counts('fields')
                    ->alignEnd(),
                IconColumn::make('is_active')
                    ->label(__('admin.application_form.active'))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data) => self::packTitle($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->fillForm(fn (ApplicationFormSection $record) => [
                        'title_en' => $record->title['en'] ?? null,
                        'title_hu' => $record->title['hu'] ?? null,
                    ] + $record->attributesToArray())
                    ->mutateDataUsing(fn (array $data) => self::packTitle($data)),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('admin.application_form.sections_empty_h'))
            ->emptyStateDescription(__('admin.application_form.sections_empty_b'));
    }

    /** @return array<string, mixed> */
    public static function packTitle(array $data): array
    {
        $packed = [];
        foreach (['en', 'hu'] as $lang) {
            if (filled($data['title_' . $lang] ?? null)) {
                $packed[$lang] = $data['title_' . $lang];
            }
            unset($data['title_' . $lang]);
        }
        $data['title'] = $packed;

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApplicationFormSections::route('/'),
        ];
    }
}
