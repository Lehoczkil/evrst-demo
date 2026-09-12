<?php

namespace App\Filament\Resources\TeamMembers\Tables;

use App\Auth\Perm;
use App\Filament\Support\MemberLoginReport;
use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use App\Support\AlumniStatus;
use App\Support\MemberLogin;
use App\Support\OrgEmail;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['groups']))
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('photo_path')
                    ->disk('public')
                    ->circular()
                    ->size(48)
                    ->label('')->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')->toggleable(),
                TextColumn::make('email')
                    ->label(__('admin.team.org_email'))
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('email_private')
                    ->label(__('admin.team.private_email'))
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('discord_nick')
                    ->label(__('admin.team.discord'))
                    ->searchable()
                    ->prefix('@')
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('discord_username')
                    ->label(__('admin.team.discord_username_col'))
                    ->searchable()
                    ->prefix('@')
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discord_id')
                    ->label(__('admin.team.discord_id_col'))
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('degree')
                    ->label(__('admin.team.degree'))
                    ->state(fn ($record) => TeamMemberGroup::pickLocale($record->degree) ?? '—')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('primary_group')
                    ->label(__('admin.team.main_position'))
                    ->state(function ($record) {
                        $primary = $record->groups->firstWhere('pivot.is_primary', true);
                        return $primary ? (TeamMemberGroup::pickLocale($primary->name) ?? '—') : '—';
                    })
                    ->badge()
                    ->color('primary')->toggleable(),
                TextColumn::make('groups')
                    ->label(__('admin.team.positions'))
                    ->state(fn ($record) => $record->groups
                        ->map(fn ($g) => TeamMemberGroup::pickLocale($g->name))
                        ->filter()
                        ->values()
                        ->all())
                    ->badge()
                    ->color('gray')->toggleable(),
                IconColumn::make('is_public')
                    ->label(__('admin.team.is_public'))
                    ->boolean()
                    ->toggleable(),
                /*
                  Alumni is a state the roster is read for, so it gets a
                  badge rather than living only in a date column that is
                  hidden by default.
                */
                TextColumn::make('alumni')
                    ->label(__('admin.team.status'))
                    ->state(fn (TeamMember $record) => AlumniStatus::isAlumni($record)
                        ? __('admin.team.status_alumni')
                        : __('admin.team.status_active'))
                    ->badge()
                    ->color(fn (TeamMember $record) => AlumniStatus::isAlumni($record) ? 'gray' : 'success')
                    ->toggleable(),
                TextColumn::make('left_at')
                    ->label(__('admin.team.left_at'))
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('primary_group')
                    ->label(__('admin.team.main_position'))
                    ->options(fn () => TeamMemberGroup::all()
                        ->mapWithKeys(fn ($g) => [$g->id => TeamMemberGroup::pickLocale($g->name) ?? $g->slug]))
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) return;
                        $query->whereHas('groups', fn ($q) => $q
                            ->where('team_member_groups.id', $value)
                            ->where('team_member_team_member_group.is_primary', true));
                    }),
                TernaryFilter::make('left_at')
                    ->label(__('admin.team.alumni_filter'))
                    ->placeholder(__('admin.team.alumni_filter_all'))
                    ->trueLabel(__('admin.team.alumni_filter_alumni'))
                    ->falseLabel(__('admin.team.alumni_filter_active'))
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('left_at'),
                        false: fn ($q) => $q->whereNull('left_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),

                /*
                  Same act as the edit page's header button, on the row.

                  This is the way back from deleting an account: the user
                  is gone, the roster row survives with user_id cleared,
                  and the admin should not have to open the member to give
                  them a login again.
                */
                Action::make('create_login')
                    ->label(__('admin.team.login_create'))
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (TeamMember $record) => $record->user_id === null && MemberLogin::canProvision())
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.team.login_create_modal'))
                    ->modalDescription(fn (TeamMember $record) => __('admin.team.login_create_modal_body', [
                        'email' => filled($record->email)
                            ? $record->email
                            : (OrgEmail::forName((string) $record->name) ?? '—'),
                    ]))
                    ->action(fn (TeamMember $record) => MemberLoginReport::flash(
                        MemberLogin::provision($record),
                        $record,
                    )),

                /*
                  The alumni switch. One click, both directions, and it
                  says which one it is about to do rather than relying on
                  the admin to know what an empty "Left at" field means.

                  It changes nothing about their account or role — see
                  App\Support\AlumniStatus.
                */
                Action::make('toggle_alumni')
                    ->label(fn (TeamMember $record) => AlumniStatus::isAlumni($record)
                        ? __('admin.team.alumni_restore')
                        : __('admin.team.alumni_mark'))
                    ->icon(fn (TeamMember $record) => AlumniStatus::isAlumni($record)
                        ? 'heroicon-o-arrow-uturn-left'
                        : 'heroicon-o-academic-cap')
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->can(Perm::TEAM_EDIT) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading(fn (TeamMember $record) => AlumniStatus::isAlumni($record)
                        ? __('admin.team.alumni_restore_modal', ['name' => $record->name])
                        : __('admin.team.alumni_mark_modal', ['name' => $record->name]))
                    ->modalDescription(fn (TeamMember $record) => AlumniStatus::isAlumni($record)
                        ? __('admin.team.alumni_restore_body')
                        : __('admin.team.alumni_mark_body'))
                    ->action(function (TeamMember $record) {
                        AlumniStatus::toggle($record);

                        Notification::make()
                            ->title(AlumniStatus::isAlumni($record)
                                ? __('admin.team.alumni_marked', ['name' => $record->name])
                                : __('admin.team.alumni_restored', ['name' => $record->name]))
                            ->success()
                            ->send();
                    }),

                // Say what else goes: deleting a member deletes their
                // panel account, which is not what "delete" usually
                // implies and is not undoable from this screen.
                DeleteAction::make()
                    ->modalDescription(fn (TeamMember $record) => $record->user_id
                        ? __('admin.team.delete_with_login_body', ['login' => $record->user?->email ?? '—'])
                        : __('filament-actions::delete.single.modal.description')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // A cohort graduates together, so this is a batch job
                    // more often than a one-off.
                    BulkAction::make('mark_alumni')
                        ->label(__('admin.team.alumni_mark'))
                        ->icon('heroicon-o-academic-cap')
                        ->color('gray')
                        ->visible(fn () => auth()->user()?->can(Perm::TEAM_EDIT) ?? false)
                        ->requiresConfirmation()
                        ->modalDescription(__('admin.team.alumni_mark_body'))
                        ->action(function (Collection $records) {
                            $marked = $records->reject(fn (TeamMember $r) => AlumniStatus::isAlumni($r));
                            $marked->each(fn (TeamMember $r) => AlumniStatus::mark($r));

                            Notification::make()
                                ->title(__('admin.team.alumni_marked_bulk', ['count' => $marked->count()]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->modalDescription(__('admin.team.delete_bulk_with_login_body')),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.team_members_h'))
            ->emptyStateDescription(__('admin.empty.team_members_b'))
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
