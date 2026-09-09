<?php

namespace App\Filament\Resources\MemberApplications\Pages;

use App\Actions\IssueTempPassword;
use App\Auth\Perm;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Filament\Schemas\MemberPositionFields;
use App\Filament\Support\TempPasswordReport;
use App\Jobs\PostDiscordWebhook;
use App\Models\MemberApplication;
use App\Models\TeamMember;
use App\Models\Role;
use App\Models\User;
use App\Support\DiscordPayloads;
use App\Support\OrgEmail;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AcceptMemberApplication extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MemberApplicationResource::class;

    protected string $view = 'filament.resources.member-applications.pages.accept-member-application';

    /**
     * Livewire assigns the route parameter to any public property it
     * matches by name *before* mount() runs, so this has to accept the
     * raw key as well as the resolved model — the same union Filament's
     * own InteractsWithRecord uses. mount() replaces it with the model.
     */
    public MemberApplication | int | string | null $record = null;

    public ?array $data = [];

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can(Perm::APPLICATIONS_ACCEPT) ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.applications.accept') . ' — ' . $this->record->name;
    }

    public function mount(int|string $record): void
    {
        abort_unless(static::canAccess(), 403);

        $this->record = MemberApplication::findOrFail($record);

        if ($this->record->status === MemberApplication::STATUS_ACCEPTED) {
            Notification::make()
                ->title(__('admin.applications.already_accepted'))
                ->info()
                ->send();
            $this->redirect(MemberApplicationResource::getUrl('index'));
            return;
        }

        $this->form->fill([
            'name' => $this->record->name,
            // The address on the application is the applicant's personal
            // inbox — it becomes email_private, not the login.
            'email_private' => $this->record->email,
            'email' => OrgEmail::uniqueForName($this->record->name ?? ''),
            'degree_en' => null,
            'degree_hu' => null,
            'group_ids' => [],
            'main_position_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(120)
                    // Retyping the name re-derives the org address, but only
                    // while the admin hasn't hand-edited it — an override
                    // must survive a later typo fix in the name.
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (($get('email_is_custom') ?? false) === true) {
                            return;
                        }
                        $set('email', OrgEmail::uniqueForName((string) $state));
                    })
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->label(__('admin.applications.org_email'))
                    ->required()
                    ->email()
                    ->maxLength(180)
                    ->unique(table: User::class, column: 'email')
                    ->validationAttribute(__('admin.applications.org_email'))
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (callable $set) => $set('email_is_custom', true))
                    ->helperText(__('admin.applications.org_email_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Hidden::make('email_is_custom')
                    ->default(false)
                    ->dehydrated(false),
                TextInput::make('email_private')
                    ->label(__('admin.team.private_email'))
                    ->required()
                    ->email()
                    ->maxLength(180)
                    ->helperText(__('admin.applications.email_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('degree_en')
                    ->label(__('admin.team.degree') . ' (EN)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                TextInput::make('degree_hu')
                    ->label(__('admin.team.degree') . ' (HU)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                ...MemberPositionFields::components(),
            ])
            ->columns(12)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // The org address is the login. If an account already holds it,
        // this is not our call to make: updateOrCreate used to overwrite
        // that account's password, null its password_changed_at and demote
        // it to Member — one click away from locking out an admin. The
        // form's unique rule normally catches it; this is the backstop for
        // a race or a hand-edited address.
        if (User::where('email', $data['email'])->exists()) {
            Notification::make()
                ->title(__('admin.applications.login_taken'))
                ->body(__('admin.applications.login_taken_body', ['email' => $data['email']]))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $temp = IssueTempPassword::generate();
        $memberRole = Role::where('key', Perm::ROLE_MEMBER)->first();

        $degree = [];
        if (! empty($data['degree_en'])) $degree['en'] = $data['degree_en'];
        if (! empty($data['degree_hu'])) $degree['hu'] = $data['degree_hu'];

        // One transaction: the TeamMember insert can still fail on the
        // team_members.email unique index (it covers soft-deleted rows),
        // and without this that left an orphan user with a null
        // password_changed_at behind while the application stayed PENDING.
        [$user, $member] = DB::transaction(function () use ($data, $temp, $memberRole, $degree) {
            // Login is the org address. password_changed_at stays null so
            // RequirePasswordChange forces a reset on first sign-in.
            $user = User::create([
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => Hash::make($temp),
                'role_id' => $memberRole?->id,
                'password_changed_at' => null,
            ]);

            $member = TeamMember::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'email_private' => $data['email_private'],
                'degree' => $degree === [] ? null : $degree,
                'user_id' => $user->id,
                'joined_at' => now()->toDateString(),
            ]);

            $member->syncGroupAssignments(
                (array) ($data['group_ids'] ?? []),
                empty($data['main_position_id']) ? null : (int) $data['main_position_id'],
            );

            $this->record->withoutActivityLog(fn () => $this->record->update([
                'status' => MemberApplication::STATUS_ACCEPTED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'team_member_id' => $member->id,
            ]));
            $this->record->logActivity('accepted', [
                'team_member' => ['id' => $member->id, 'name' => $member->name],
            ]);

            return [$user, $member];
        });

        $user->setRelation('teamMember', $member);
        $delivery = IssueTempPassword::deliver($user, $temp);

        $payload = DiscordPayloads::applicationAccepted($this->record, auth()->user());
        PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference'])->afterResponse();

        // The application is accepted either way — say what happened to the
        // credentials separately rather than folding it into one message.
        Notification::make()
            ->title(__('admin.applications.accepted_title'))
            ->body(__('admin.applications.accepted_body', ['name' => $member->name]))
            ->success()
            ->send();

        TempPasswordReport::flash($delivery);

        $this->redirect(TeamMemberResource::getUrl('edit', ['record' => $member->id]));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.applications.accept_submit'))
                ->action('save')
                ->color('success'),
            Action::make('cancel')
                ->label(__('admin.common.cancel'))
                ->color('gray')
                ->url(MemberApplicationResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
