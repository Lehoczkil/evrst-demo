<?php

namespace App\Filament\Resources\MemberApplications\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Cms\TeamMembers\TeamMemberResource;
use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Filament\Schemas\MemberPositionFields;
use App\Jobs\PostDiscordWebhook;
use App\Models\MemberApplication;
use App\Models\TeamMember;
use App\Models\Role;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use App\Support\DiscordPayloads;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AcceptMemberApplication extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MemberApplicationResource::class;

    protected string $view = 'filament.resources.member-applications.pages.accept-member-application';

    public MemberApplication $record;

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
            'email' => $this->record->email,
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
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->label(__('admin.common.email'))
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

        $temp = Str::password(12);
        $memberRole = Role::where('key', Perm::ROLE_MEMBER)->first();

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make($temp),
                'role_id' => $memberRole?->id,
                'password_changed_at' => null,
            ],
        );

        $degree = [];
        if (! empty($data['degree_en'])) $degree['en'] = $data['degree_en'];
        if (! empty($data['degree_hu'])) $degree['hu'] = $data['degree_hu'];

        $member = TeamMember::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'degree' => $degree === [] ? null : $degree,
            'user_id' => $user->id,
            'joined_at' => now()->toDateString(),
        ]);

        $groupIds = array_values(array_filter((array) ($data['group_ids'] ?? [])));
        if ($groupIds !== []) {
            $member->groups()->sync(array_fill_keys($groupIds, ['is_primary' => false]));
        }
        if (! empty($data['main_position_id'])) {
            $member->setPrimaryGroup((int) $data['main_position_id']);
        }

        $this->record->withoutActivityLog(fn () => $this->record->update([
            'status' => MemberApplication::STATUS_ACCEPTED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'team_member_id' => $member->id,
        ]));
        $this->record->logActivity('accepted', [
            'team_member' => ['id' => $member->id, 'name' => $member->name],
        ]);

        $user->notify(new TeamMemberAccountCreated($temp));

        $payload = DiscordPayloads::applicationAccepted($this->record, auth()->user());
        PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference'])->afterResponse();

        Notification::make()
            ->title('Application accepted')
            ->body('Team member created and a temporary password emailed to ' . $user->email . '.')
            ->success()
            ->send();

        $this->redirect(TeamMemberResource::getUrl('edit', ['record' => $member->id]));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Create team member + admin account')
                ->action('save')
                ->color('success'),
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(MemberApplicationResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
