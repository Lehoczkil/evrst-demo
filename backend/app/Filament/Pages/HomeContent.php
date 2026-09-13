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
 * The home page's own copy.
 *
 * Everything on the home page that comes from a record — events,
 * sponsors, projects, goals, the roster, the About body — was already
 * editable. Everything else lived in the SPA's message files, so changing
 * one word meant a deploy: the headline a visitor reads first, the scroll
 * statements, the rocket's spec table, the sponsor pitch, the join block,
 * even the address in the footer.
 *
 * Stored as one `views` resource, the shape AboutContent uses, because the
 * SPA already knows how to fetch a view by name and a row per string would
 * make the admin hunt.
 *
 * The name is 'home-copy', not 'home'. The SPA looks a view up by
 * `payload.name` alone (VITE_VIEWS_COLLECTION_ID is unset, so the
 * collection filter goes over the wire as "undefined"), and the CMS
 * `pages` collection already holds a row named 'home' — which sorts first
 * and would be handed back instead of this one.
 *
 * Every field is optional. The SPA falls back to its bundled copy PER
 * STRING, so a half-filled form degrades to the current text rather than
 * to a blank hero, and clearing a field restores the built-in copy instead
 * of emptying the page.
 *
 * ## Adding a field
 *
 * Put the key in TEXTS (translated) or PLAIN (not), give it a label under
 * `admin.home_content.<group>_<key>`, and call `text('<group>.<key>')`
 * from the component — `useHomeCopy` resolves that same path against the
 * payload and falls back to the message file under the identical key.
 * Nothing else here needs touching.
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

    /** Translated strings, as `group => [keys]`. */
    private const TEXTS = [
        'hero' => ['eyebrow', 'title1', 'title2', 'lede', 'ctaJoin', 'ctaMission'],
        'rocket' => ['eyebrow', 'title', 'status'],
        'manifesto' => ['second', 'mark1', 'mark2', 'mark3'],
        'sponsors' => ['pitchTitle', 'pitchBody', 'cta'],
        'join' => ['eyebrow', 'title', 'lede', 'cta', 'question'],
        'contact' => ['address'],
    ];

    /** Values that read the same in both languages — a measurement, a year. */
    private const PLAIN = [
        'rocket' => ['dimHeight', 'dimDiameter'],
        'manifesto' => ['founded'],
    ];

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
        $state = [];

        foreach (self::TEXTS as $group => $keys) {
            foreach ($keys as $key) {
                $value = $payload[$group][$key] ?? null;
                $state["{$group}_{$key}_en"] = self::pick($value, 'en');
                $state["{$group}_{$key}_hu"] = self::pick($value, 'hu');
            }
        }

        foreach (self::PLAIN as $group => $keys) {
            foreach ($keys as $key) {
                $state["{$group}_{$key}"] = (string) ($payload[$group][$key] ?? '');
            }
        }

        $state['hero_says'] = collect($payload['hero']['says'] ?? [])
            ->map(fn ($line) => ['en' => self::pick($line, 'en'), 'hu' => self::pick($line, 'hu')])
            ->all();

        $state['rocket_specs'] = collect($payload['rocket']['specs'] ?? [])
            ->map(fn ($spec) => [
                'label_en' => self::pick($spec['label'] ?? null, 'en'),
                'label_hu' => self::pick($spec['label'] ?? null, 'hu'),
                'value' => (string) ($spec['value'] ?? ''),
                'unit_en' => self::pick($spec['unit'] ?? null, 'en'),
                'unit_hu' => self::pick($spec['unit'] ?? null, 'hu'),
            ])
            ->all();

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.home_content.hero'))
                    ->description(__('admin.home_content.hero_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('hero.eyebrow', 120),
                        ...self::pair('hero.title1', 60),
                        ...self::pair('hero.title2', 60),
                        ...self::pair('hero.lede', 300, textarea: true),
                        ...self::pair('hero.ctaJoin', 40),
                        ...self::pair('hero.ctaMission', 40),
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
                                Textarea::make('en')->label(__('admin.home_content.line') . ' (EN)')->rows(2)->maxLength(200),
                                Textarea::make('hu')->label(__('admin.home_content.line') . ' (HU)')->rows(2)->maxLength(200),
                            ]),
                    ]),

                Section::make(__('admin.home_content.manifesto'))
                    ->description(__('admin.home_content.manifesto_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('manifesto.second', 300, textarea: true),
                        TextInput::make('manifesto_founded')
                            ->label(__('admin.home_content.manifesto_founded'))
                            ->helperText(__('admin.home_content.founded_help'))
                            ->maxLength(12)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        ...self::pair('manifesto.mark1', 80),
                        ...self::pair('manifesto.mark2', 80),
                        ...self::pair('manifesto.mark3', 80),
                    ]),

                Section::make(__('admin.home_content.rocket'))
                    ->description(__('admin.home_content.rocket_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('rocket.eyebrow', 60),
                        ...self::pair('rocket.title', 60),
                        ...self::pair('rocket.status', 40),
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
                                TextInput::make('label_en')->label(__('admin.home_content.spec_label') . ' (EN)')->maxLength(60)->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('label_hu')->label(__('admin.home_content.spec_label') . ' (HU)')->maxLength(60)->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('value')->label(__('admin.home_content.spec_value'))->maxLength(40)->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('unit_en')->label(__('admin.home_content.spec_unit') . ' (EN)')->maxLength(30)->columnSpan(['default' => 6, 'md' => 3]),
                                TextInput::make('unit_hu')->label(__('admin.home_content.spec_unit') . ' (HU)')->maxLength(30)->columnSpan(['default' => 6, 'md' => 3]),
                            ]),
                    ]),

                Section::make(__('admin.home_content.sponsors'))
                    ->description(__('admin.home_content.sponsors_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('sponsors.pitchTitle', 120),
                        ...self::pair('sponsors.pitchBody', 400, textarea: true),
                        ...self::pair('sponsors.cta', 40),
                    ]),

                Section::make(__('admin.home_content.join'))
                    ->description(__('admin.home_content.join_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('join.eyebrow', 80),
                        ...self::pair('join.title', 80),
                        ...self::pair('join.lede', 300, textarea: true),
                        ...self::pair('join.cta', 40),
                        ...self::pair('join.question', 40),
                    ]),

                Section::make(__('admin.home_content.contact'))
                    ->description(__('admin.home_content.contact_help'))
                    ->columns(12)
                    ->components([
                        ...self::pair('contact.address', 200),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * An EN/HU pair of inputs for one `group.key`.
     *
     * The label comes from `admin.home_content.<group>_<key>`, so adding a
     * field is a line in TEXTS plus a line in each message file.
     *
     * @return array<int, TextInput|Textarea>
     */
    private static function pair(string $path, int $max, bool $textarea = false): array
    {
        [$group, $key] = explode('.', $path);
        $name = "{$group}_{$key}";
        $label = __("admin.home_content.{$name}");

        $make = fn (string $lang) => ($textarea
            ? Textarea::make("{$name}_" . strtolower($lang))->rows(3)
            : TextInput::make("{$name}_" . strtolower($lang)))
            ->label("{$label} ({$lang})")
            ->maxLength($max)
            ->columnSpan(['default' => 12, 'md' => 6]);

        return [$make('EN'), $make('HU')];
    }

    public function save(): void
    {
        // Same reasoning as AboutContent::save() — mount() only runs on
        // the initial GET, and this is reachable over /livewire/update.
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $payload = $this->record()->payload ?? [];

        foreach (self::TEXTS as $group => $keys) {
            foreach ($keys as $key) {
                $payload[$group][$key] = [
                    'en' => (string) ($data["{$group}_{$key}_en"] ?? ''),
                    'hu' => (string) ($data["{$group}_{$key}_hu"] ?? ''),
                ];
            }
        }

        foreach (self::PLAIN as $group => $keys) {
            foreach ($keys as $key) {
                $payload[$group][$key] = (string) ($data["{$group}_{$key}"] ?? '');
            }
        }

        // Empty rows are dropped rather than shipped as blanks the SPA
        // would render as gaps in the sequence.
        $payload['hero']['says'] = collect($data['hero_says'] ?? [])
            ->filter(fn ($line) => filled($line['en'] ?? null) || filled($line['hu'] ?? null))
            ->map(fn ($line) => ['en' => (string) ($line['en'] ?? ''), 'hu' => (string) ($line['hu'] ?? '')])
            ->values()
            ->all();

        $payload['rocket']['specs'] = collect($data['rocket_specs'] ?? [])
            ->filter(fn ($spec) => filled($spec['label_en'] ?? null) || filled($spec['label_hu'] ?? null))
            ->map(fn ($spec) => [
                'label' => ['en' => (string) ($spec['label_en'] ?? ''), 'hu' => (string) ($spec['label_hu'] ?? '')],
                'value' => (string) ($spec['value'] ?? ''),
                'unit' => ['en' => (string) ($spec['unit_en'] ?? ''), 'hu' => (string) ($spec['unit_hu'] ?? '')],
            ])
            ->values()
            ->all();

        $payload['name'] = 'home-copy';

        $record = $this->record();
        $record->id = self::VIEW_RESOURCE_ID;
        $record->collection_id = self::VIEWS_COLLECTION_ID;
        $record->payload = $payload;
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
