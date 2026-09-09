<?php

namespace App\Filament\Resources\ApplicationForm\Schemas;

use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use App\Models\TeamMemberGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ApplicationFormFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.application_form.section_question'))
                    ->columns(12)
                    ->columnSpanFull()
                    ->components([
                        Select::make('section_id')
                            ->label(__('admin.application_form.section'))
                            ->options(fn () => ApplicationFormSection::orderBy('position')->get()
                                ->mapWithKeys(fn (ApplicationFormSection $s) => [
                                    $s->id => TeamMemberGroup::pickLocale($s->title) ?: $s->key,
                                ]))
                            ->required()
                            ->native(false)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Select::make('type')
                            ->label(__('admin.application_form.type'))
                            ->options(fn () => collect(ApplicationFormField::types())
                                ->mapWithKeys(fn (string $t) => [$t => __('admin.application_form.types.' . $t)]))
                            ->required()
                            ->native(false)
                            ->live()
                            // Changing the type of a question people have
                            // already answered would leave those answers in
                            // a shape the new type cannot render.
                            ->disabled(fn (?ApplicationFormField $record) => $record?->is_system ?? false)
                            ->helperText(fn (?ApplicationFormField $record) => $record?->is_system
                                ? __('admin.application_form.system_locked')
                                : null)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('label_en')
                            ->label(__('admin.application_form.label') . ' (EN)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('label_hu')
                            ->label(__('admin.application_form.label') . ' (HU)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('help_en')
                            ->label(__('admin.application_form.help') . ' (EN)')
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('help_hu')
                            ->label(__('admin.application_form.help') . ' (HU)')
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('placeholder_en')
                            ->label(__('admin.application_form.placeholder') . ' (EN)')
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('placeholder_hu')
                            ->label(__('admin.application_form.placeholder') . ' (HU)')
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ]),

                Section::make(__('admin.application_form.section_options'))
                    ->description(__('admin.application_form.section_options_help'))
                    ->visible(fn (callable $get) => in_array($get('type'), ApplicationFormField::CHOICE_TYPES, true))
                    ->columnSpanFull()
                    ->components([
                        Repeater::make('options')
                            ->label(__('admin.application_form.options'))
                            ->hiddenLabel()
                            ->columns(12)
                            ->reorderable()
                            ->defaultItems(1)
                            ->schema([
                                TextInput::make('value')
                                    ->label(__('admin.application_form.option_value'))
                                    ->required()
                                    ->maxLength(120)
                                    ->helperText(__('admin.application_form.option_value_help'))
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('label.en')
                                    ->label(__('admin.application_form.option_label') . ' (EN)')
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('label.hu')
                                    ->label(__('admin.application_form.option_label') . ' (HU)')
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                            ]),
                    ]),

                Section::make(__('admin.application_form.section_behaviour'))
                    ->columns(12)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('key')
                            ->label(__('admin.application_form.key'))
                            ->required()
                            ->maxLength(64)
                            ->rule('regex:/^[a-zA-Z][a-zA-Z0-9_]*$/')
                            ->validationMessages(['regex' => __('admin.application_form.key_invalid')])
                            ->unique(ignoreRecord: true)
                            // The key is what every stored answer is filed
                            // under. Editing it on a live question would
                            // orphan every answer already collected, so it
                            // is set once, at creation.
                            ->disabled(fn (?ApplicationFormField $record) => $record !== null)
                            ->dehydrated()
                            ->helperText(fn (?ApplicationFormField $record) => $record
                                ? __('admin.application_form.key_locked')
                                : __('admin.application_form.key_help'))
                            ->default(fn (callable $get) => Str::camel((string) $get('label_en')))
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        TextInput::make('max_length')
                            ->label(__('admin.application_form.max_length'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20000)
                            ->placeholder(fn (callable $get) => match ($get('type')) {
                                ApplicationFormField::TYPE_TEXTAREA => '5000',
                                default => '255',
                            })
                            ->helperText(__('admin.application_form.max_length_help'))
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        TextInput::make('position')
                            ->label(__('admin.common.sort'))
                            ->numeric()
                            ->default(0)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Toggle::make('is_required')
                            ->label(__('admin.application_form.required'))
                            ->helperText(__('admin.application_form.required_help'))
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Toggle::make('is_active')
                            ->label(__('admin.application_form.active'))
                            ->default(true)
                            ->helperText(__('admin.application_form.active_help'))
                            ->disabled(fn (?ApplicationFormField $record) => $record?->is_system ?? false)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Textarea::make('system_note')
                            ->label('')
                            ->visible(fn (?ApplicationFormField $record) => $record?->is_system ?? false)
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2)
                            ->default(__('admin.application_form.system_note'))
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(12);
    }
}
