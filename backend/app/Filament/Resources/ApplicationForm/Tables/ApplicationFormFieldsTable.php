<?php

namespace App\Filament\Resources\ApplicationForm\Tables;

use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use App\Models\TeamMemberGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationFormFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('section'))
            ->defaultSort('position')
            // Drag to reorder writes `position`, which is the order the
            // public form renders in.
            ->reorderable('position')
            ->columns([
                TextColumn::make('label')
                    ->label(__('admin.application_form.label'))
                    ->state(fn (ApplicationFormField $r) => TeamMemberGroup::pickLocale($r->label) ?: $r->key)
                    ->searchable(['key'])
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('key')
                    ->label(__('admin.application_form.key'))
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('section.title')
                    ->label(__('admin.application_form.section'))
                    ->state(fn (ApplicationFormField $r) => TeamMemberGroup::pickLocale($r->section?->title) ?: '—')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->label(__('admin.application_form.type'))
                    ->formatStateUsing(fn (string $state) => __('admin.application_form.types.' . $state))
                    ->badge()
                    ->color('primary'),
                TextColumn::make('options')
                    ->label(__('admin.application_form.options'))
                    ->state(fn (ApplicationFormField $r) => $r->isChoice() ? count($r->optionValues()) : '—')
                    ->alignEnd()
                    ->toggleable(),
                IconColumn::make('is_required')
                    ->label(__('admin.application_form.required'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('admin.application_form.active'))
                    ->boolean(),
                IconColumn::make('is_system')
                    ->label(__('admin.application_form.system'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('section_id')
                    ->label(__('admin.application_form.section'))
                    ->options(fn () => ApplicationFormSection::orderBy('position')->get()
                        ->mapWithKeys(fn (ApplicationFormSection $s) => [
                            $s->id => TeamMemberGroup::pickLocale($s->title) ?: $s->key,
                        ])),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (ApplicationFormField $r) => ! $r->is_system),
            ])
            ->emptyStateHeading(__('admin.application_form.empty_h'))
            ->emptyStateDescription(__('admin.application_form.empty_b'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
