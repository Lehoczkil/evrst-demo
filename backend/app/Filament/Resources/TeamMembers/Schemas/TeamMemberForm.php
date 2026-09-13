<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use App\Filament\Schemas\MemberPositionFields;
use App\Filament\Support\Uploads;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\OrgEmail;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    /**
     * Whether the current user may move the three fields that decide who
     * an account *is* and where its mail lands.
     *
     * `team.edit` is a Manager permission, but `email` is the login,
     * `email_private` is what User::deliveryEmail() routes password
     * resets to, and `user_id` is the link between a roster row and a
     * panel account. A manager able to edit any of them could point an
     * admin's reset mail at their own inbox and take the account over, so
     * all three are admin-only — disabled *and* not dehydrated, because a
     * disabled field is a rendering hint and the payload is attacker
     * controlled (same pattern as App\Filament\Auth\ForceChangeProfile).
     */
    private static function canEditIdentity(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('user_id')
                    ->label(__('admin.team.linked_user'))
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->disabled(fn () => ! self::canEditIdentity())
                    ->dehydrated(fn () => self::canEditIdentity())
                    ->helperText(fn () => self::canEditIdentity() ? null : __('admin.team.identity_locked'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->label(__('admin.team.org_email'))
                    ->email()
                    ->maxLength(180)
                    ->unique(ignoreRecord: true)
                    ->placeholder(fn () => OrgEmail::forName('Lehoczki László'))
                    ->helperText(__('admin.team.org_email_help'))
                    // Fill from the roster name on demand rather than
                    // live — an address already handed out is a login, and
                    // silently rewriting it would lock the member out.
                    ->suffixAction(
                        FormAction::make('deriveOrgEmail')
                            ->label(__('admin.team.org_email_generate'))
                            ->icon('heroicon-m-sparkles')
                            ->action(function (callable $get, callable $set, ?TeamMember $record) {
                                $set('email', OrgEmail::uniqueForName(
                                    (string) $get('name'),
                                    ignoreUserId: $record?->user_id,
                                    ignoreTeamMemberId: $record?->getKey(),
                                ));
                            }),
                    )
                    ->disabled(fn () => ! self::canEditIdentity())
                    ->dehydrated(fn () => self::canEditIdentity())
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email_private')
                    ->label(__('admin.team.private_email'))
                    ->email()
                    ->maxLength(180)
                    ->disabled(fn () => ! self::canEditIdentity())
                    ->dehydrated(fn () => self::canEditIdentity())
                    ->helperText(fn () => self::canEditIdentity()
                        ? __('admin.team.private_email_help')
                        : __('admin.team.identity_locked'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_nick')
                    ->label(__('admin.team.discord'))
                    ->maxLength(64)
                    ->placeholder('e.g. balint_klabacsek')
                    ->prefix('@')
                    ->helperText(__('admin.team.discord_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_username')
                    ->label(__('admin.team.discord_username'))
                    ->maxLength(64)
                    ->placeholder('e.g. balint_klabacsek')
                    ->helperText(__('admin.team.discord_username_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_id')
                    ->label(__('admin.team.discord_id'))
                    ->maxLength(32)
                    ->placeholder('123456789012345678')
                    ->rule('regex:/^\d{17,20}$/')
                    ->validationMessages(['regex' => __('admin.team.discord_id_invalid')])
                    ->helperText(__('admin.team.discord_id_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('degree_en')
                    ->label(__('admin.team.degree') . ' (EN)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                TextInput::make('degree_hu')
                    ->label(__('admin.team.degree') . ' (HU)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                DatePicker::make('joined_at')
                    ->label(__('admin.team.joined_at'))
                    ->native(false)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                DatePicker::make('left_at')
                    ->label(__('admin.team.left_at'))
                    ->native(false)
                    ->helperText(__('admin.team.left_at_help'))
                    ->columnSpan(['default' => 12, 'md' => 3]),
                ...MemberPositionFields::components(),
                Toggle::make('is_public')
                    ->label(__('admin.team.is_public'))
                    ->helperText(__('admin.team.is_public_help'))
                    ->default(true)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->integer()
                    ->minValue(0)
                    ->step(1)
                    ->helperText(__('admin.common.sort_help'))
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('photo_path')
                    ->label(__('admin.team.photo'))
                    ->acceptedFileTypes(Uploads::PHONE_IMAGE_TYPES)
                    ->getUploadedFileNameForStorageUsing(Uploads::storedName(...))
                    ->directory('team-members')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
