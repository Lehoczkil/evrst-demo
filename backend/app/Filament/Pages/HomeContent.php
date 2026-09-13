<?php

namespace App\Filament\Pages;

use App\Auth\Perm;
use App\Models\Resource as ResourceModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The home page's own copy: the hero and the rocket sheet.
 *
 * Everything else on the home page already came from somewhere editable —
 * events, sponsors, projects, goals, the roster, the About body. These two
 * blocks were the exception: the headline a visitor reads first, the three
 * scroll statements and the spec table all lived in the SPA's message
 * files, so changing a word meant a deploy.
 *
 * Stored as one `views` resource, the same shape AboutContent uses,
 * because the SPA already knows how to fetch a view by name and the
 * alternative — a row per string — makes the admin hunt.
 *
 * The name is 'home-copy', not 'home'. The SPA looks a view up by
 * `payload.name` alone (VITE_VIEWS_COLLECTION_ID is unset, so the
 * collection filter goes over the wire as "undefined"), and the CMS
 * `pages` collection already holds a row named 'home' — which sorts
 * first and would be handed back instead of this one.
 *
 * Every field is optional. The SPA falls back to its bundled copy per
 * string, so a half-filled form degrades to the current text rather than
 * to a blank hero.
 */
class HomeContent extends Page
{
    protected string $view = 'filament.pages.home-content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 1;

    /** Fixed id: this page edits one row, and it has to find it again. */
    protected const VIEW_RESOURCE_ID = 'c1d0e9a4-6f3b-4a21-9b7e-2f5a8c0d4e11';

    protected const VIEWS_COLLECTION_ID = 'f2a4ad4c-f5b8-4d7f-9c2c-9d4d6c0b3aaa';

    public ?array $data = [];

    public static function getNavigationLabel(): string { return __('admin.home_content.nav'); }

    public function getTitle(): string { return __('admin.home_content.title'); }

    public function getSubheading(): ?string { return __('admin.home_content.sub'); }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false;
    }

    protected function record(): ResourceModel
    {
        return ResourceModel::firstOrNew(['id' => self::VIEW_RESOURCE_ID]);
    }

    /** One locale out of a `{en, hu}` map, tolerating a plain string. */
    private static function pick(mixed $value, string $lang): string
    {
        if (is_array($value)) {
            return (string) ($value[$lang] ?? '');
        }

        return $lang === 'en' ? (string) $value : '';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $payload = $this->record()->payload ?? [];
        $hero = $payload['hero'] ?? [];
        $rocket = $payload['rocket'] ?? [];

        $flat = [];
        foreach (['eyebrow', 'title1', 'title2', 'lede', 'ctaJoin', 'ctaMission'] as $key) {
            $flat["hero_{$key}_en"] = self::pick($hero[$key] ?? null, 'en');
            $flat["hero_{$key}_hu"] = self::pick($hero[$key] ?? null, 'hu');
        }
        foreach (['eyebrow', 'title', 'status'] as $key) {
            $flat["rocket_{$key}_en"] = self::pick($rocket[$key] ?? null, 'en');
            $flat["rocket_{$key}_hu"] = self::pick($rocket[$key] ?? null, 'hu');
        }

        $this->form->fill($flat + [
            // Not localised: a measurement reads the same in both.
            'rocket_dimHeight' => (string) ($rocket['dimHeight'] ?? ''),
            'rocket_dimDiameter' => (string) ($rocket['dimDiameter'] ?? ''),
            'hero_says' => collect($hero['says'] ?? [])
                ->map(fn ($line) => [
                    'en' => self::pick($line, 'en'),
                    'hu' => self::pick($line, 'hu'),
                ])
                ->all(),
            'rocket_specs' => collect($rocket['specs'] ?? [])
                ->map(fn ($spec) => [
                    'label_en' => self::pick($spec['label'] ?? null, 'en'),
                    'label_hu' => self::pick($spec['label'] ?? null, 'hu'),
                    'value' => (string) ($spec['value'] ?? ''),
                    'unit_en' => self::pick($spec['unit'] ?? null, 'en'),
                    'unit_hu' => self::pick($spec['unit'] ?? null, 'hu'),
                ])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.home_content.hero'))
                    ->description(__('admin.home_content.hero_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('hero_eyebrow', __('admin.home_content.hero_eyebrow'), 120),
                        ...self::pair('hero_title1', __('admin.home_content.hero_title1'), 60),
                        ...self::pair('hero_title2', __('admin.home_content.hero_title2'), 60),
                        ...self::pair('hero_lede', __('admin.home_content.hero_lede'), 300, textarea: true),
                        ...self::pair('hero_ctaJoin', __('admin.home_content.hero_cta_join'), 40),
                        ...self::pair('hero_ctaMission', __('admin.home_content.hero_cta_mission'), 40),
                    ]),

                Section::make(__('admin.home_content.says'))
                    ->description(__('admin.home_content.says_help'))
                    ->components([
                        Repeater::make('hero_says')
                            ->label('')
                            ->addActionLabel(__('admin.home_content.says_add'))
                            ->reorderable()
                            ->defaultItems(0)
                            ->columns(2)
                            ->schema([
                                Textarea::make('en')
                                    ->label(__('admin.home_content.line') . ' (EN)')
                                    ->rows(2)
                                    ->maxLength(200),
                                Textarea::make('hu')
                                    ->label(__('admin.home_content.line') . ' (HU)')
                                    ->rows(2)
                                    ->maxLength(200),
                            ]),
                    ]),

                Section::make(__('admin.home_content.rocket'))
                    ->description(__('admin.home_content.rocket_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('rocket_eyebrow', __('admin.home_content.rocket_eyebrow'), 60),
                        ...self::pair('rocket_title', __('admin.home_content.rocket_title'), 60),
                        ...self::pair('rocket_status', __('admin.home_content.rocket_status'), 40),
                        TextInput::make('rocket_dimHeight')
                            ->label(__('admin.home_content.dim_height'))
                            ->maxLength(40)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('rocket_dimDiameter')
                            ->label(__('admin.home_content.dim_diameter'))
                            ->maxLength(40)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ]),

                Section::make(__('admin.home_content.specs'))
                    ->description(__('admin.home_content.specs_help'))
                    ->components([
                        Repeater::make('rocket_specs')
                            ->label('')
                            ->addActionLabel(__('admin.home_content.specs_add'))
                            ->reorderable()
                            ->defaultItems(0)
                            ->columns(12)
                            ->schema([
                                TextInput::make('label_en')
                                    ->label(__('admin.home_content.spec_label') . ' (EN)')
                                    ->maxLength(60)
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('label_hu')
                                    ->label(__('admin.home_content.spec_label') . ' (HU)')
                                    ->maxLength(60)
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('value')
                                    ->label(__('admin.home_content.spec_value'))
                                    ->maxLength(40)
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('unit_en')
                                    ->label(__('admin.home_content.spec_unit') . ' (EN)')
                                    ->maxLength(30)
                                    ->columnSpan(['default' => 6, 'md' => 3]),
                                TextInput::make('unit_hu')
                                    ->label(__('admin.home_content.spec_unit') . ' (HU)')
                                    ->maxLength(30)
                                    ->columnSpan(['default' => 6, 'md' => 3]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * An EN/HU pair of inputs for one string.
     *
     * @return array<int, TextInput|Textarea>
     */
    private static function pair(string $key, string $label, int $max, bool $textarea = false): array
    {
        $make = fn (string $suffix, string $lang) => ($textarea
            ? Textarea::make("{$key}_{$suffix}")->rows(3)
            : TextInput::make("{$key}_{$suffix}"))
            ->label("{$label} ({$lang})")
            ->maxLength($max)
            ->columnSpan(['default' => 12, 'md' => 6]);

        return [$make('en', 'EN'), $make('hu', 'HU')];
    }

    public function save(): void
    {
        // Same reasoning as AboutContent::save() — mount() only runs on
        // the initial GET, and this is reachable over /livewire/update.
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $map = fn (string $key) => [
            'en' => (string) ($data["{$key}_en"] ?? ''),
            'hu' => (string) ($data["{$key}_hu"] ?? ''),
        ];

        $hero = [];
        foreach (['eyebrow', 'title1', 'title2', 'lede', 'ctaJoin', 'ctaMission'] as $key) {
            $hero[$key] = $map("hero_{$key}");
        }
        // Drop empty rows rather than shipping blank lines the SPA would
        // render as gaps in the sequence.
        $hero['says'] = collect($data['hero_says'] ?? [])
            ->filter(fn ($line) => filled($line['en'] ?? null) || filled($line['hu'] ?? null))
            ->map(fn ($line) => ['en' => (string) ($line['en'] ?? ''), 'hu' => (string) ($line['hu'] ?? '')])
            ->values()
            ->all();

        $rocket = [];
        foreach (['eyebrow', 'title', 'status'] as $key) {
            $rocket[$key] = $map("rocket_{$key}");
        }
        $rocket['dimHeight'] = (string) ($data['rocket_dimHeight'] ?? '');
        $rocket['dimDiameter'] = (string) ($data['rocket_dimDiameter'] ?? '');
        $rocket['specs'] = collect($data['rocket_specs'] ?? [])
            ->filter(fn ($spec) => filled($spec['label_en'] ?? null) || filled($spec['label_hu'] ?? null))
            ->map(fn ($spec) => [
                'label' => ['en' => (string) ($spec['label_en'] ?? ''), 'hu' => (string) ($spec['label_hu'] ?? '')],
                'value' => (string) ($spec['value'] ?? ''),
                'unit' => ['en' => (string) ($spec['unit_en'] ?? ''), 'hu' => (string) ($spec['unit_hu'] ?? '')],
            ])
            ->values()
            ->all();

        $record = $this->record();
        $record->id = self::VIEW_RESOURCE_ID;
        $record->collection_id = self::VIEWS_COLLECTION_ID;
        $record->payload = array_merge($record->payload ?? [], [
            'name' => 'home-copy',
            'hero' => $hero,
            'rocket' => $rocket,
        ]);
        $record->save();

        Notification::make()
            ->title(__('admin.home_content.saved'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.common.save'))
                ->action('save')
                ->color('primary'),
        ];
    }
}
