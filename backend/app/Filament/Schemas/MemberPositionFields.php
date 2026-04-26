<?php

namespace App\Filament\Schemas;

use App\Models\Cms\TeamMemberGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

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
                ->label('Positions')
                ->helperText('All positions this member holds.')
                ->options(fn () => TeamMemberGroup::all()
                    ->mapWithKeys(fn ($g) => [$g->id => $g->name ?? $g->id]))
                ->multiple()
                ->searchable()
                ->preload()
                ->live()
                ->columnSpan($positionsSpan),
            Select::make('main_position_id')
                ->label('Main position')
                ->helperText('Used for the org chart on the site.')
                ->options(function (Get $get) {
                    $ids = (array) ($get('position_ids') ?? []);
                    if (empty($ids)) {
                        return TeamMemberGroup::all()
                            ->mapWithKeys(fn ($g) => [$g->id => $g->name ?? $g->id])
                            ->all();
                    }
                    return TeamMemberGroup::whereIn('id', $ids)->get()
                        ->mapWithKeys(fn ($g) => [$g->id => $g->name ?? $g->id])
                        ->all();
                })
                ->searchable()
                ->preload()
                ->columnSpan($mainSpan),
        ];
    }
}
