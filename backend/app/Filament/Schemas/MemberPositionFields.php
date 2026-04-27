<?php

namespace App\Filament\Schemas;

use App\Models\Cms\TeamMemberGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Cache;

/**
 * Shared form fragment: positions multi-select + a main-position single
 * select constrained to the chosen positions. Used by both the team
 * member form and the application-accept form.
 */
class MemberPositionFields
{
    /**
     * @param  array{positions?: int|array, main?: int|array}  $columnSpans
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function components(array $columnSpans = []): array
    {
        $positionsSpan = $columnSpans['positions'] ?? ['default' => 12, 'md' => 6];
        $mainSpan = $columnSpans['main'] ?? ['default' => 12, 'md' => 6];

        return [
            Select::make('position_ids')
                ->label(__('admin.team.positions'))
                ->options(fn () => self::groupOptions())
                ->multiple()
                ->searchable()
                ->preload()
                ->live()
                ->columnSpan($positionsSpan),
            Select::make('main_position_id')
                ->label(__('admin.team.main_position'))
                ->options(function (Get $get) {
                    $ids = (array) ($get('position_ids') ?? []);
                    $all = self::groupOptions();
                    if (empty($ids)) return $all;
                    return array_intersect_key($all, array_flip($ids));
                })
                ->searchable()
                ->preload()
                ->columnSpan($mainSpan),
        ];
    }

    /**
     * Cached id => name map for the position selects. 5 min TTL with
     * automatic invalidation in AppServiceProvider on save / delete.
     *
     * @return array<int|string, string>
     */
    private static function groupOptions(): array
    {
        return Cache::remember('options:team-member-groups', 300, fn () => TeamMemberGroup::all()
            ->mapWithKeys(fn ($g) => [$g->id => $g->name ?? $g->id])
            ->all());
    }
}
