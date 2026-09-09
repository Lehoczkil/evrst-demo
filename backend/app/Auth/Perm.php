<?php

namespace App\Auth;

/**
 * Permission key constants. Use these everywhere instead of typing the
 * string literals — keeps grepability + autocomplete + cuts down typos.
 */
final class Perm
{
    public const EVENTS_CREATE = 'events.create';
    public const EVENTS_EDIT   = 'events.edit';
    public const EVENTS_DELETE = 'events.delete';

    public const SPONSORS_CREATE = 'sponsors.create';
    public const SPONSORS_EDIT   = 'sponsors.edit';
    public const SPONSORS_DELETE = 'sponsors.delete';

    public const TEAM_CREATE = 'team.create';
    public const TEAM_EDIT   = 'team.edit';
    public const TEAM_DELETE = 'team.delete';

    public const CONTENT_CREATE = 'content.create';
    public const CONTENT_EDIT   = 'content.edit';
    public const CONTENT_DELETE = 'content.delete';

    public const PROJECTS_CREATE = 'projects.create';
    public const PROJECTS_EDIT   = 'projects.edit';
    public const PROJECTS_DELETE = 'projects.delete';

    public const GOALS_CREATE = 'goals.create';
    public const GOALS_EDIT   = 'goals.edit';
    public const GOALS_DELETE = 'goals.delete';

    public const APPLICATIONS_ACCEPT = 'applications.accept';
    public const APPLICATIONS_REFUSE = 'applications.refuse';
    public const APPLICATIONS_EDIT   = 'applications.edit';

    public const TASKS_CREATE = 'tasks.create';
    public const TASKS_EDIT   = 'tasks.edit';
    public const TASKS_DELETE = 'tasks.delete';

    /**
     * Move a task you are on through the workflow — open its edit page,
     * attach proof, change the status — *without* being able to edit
     * anyone's task or rewrite its definition. Held by Member, and always
     * checked together with "am I the assignee or the supervisor here".
     * TASKS_EDIT remains the unrestricted one.
     */
    public const TASKS_PROGRESS = 'tasks.progress';

    public const MODELS_VIEW   = 'models.view';
    public const MODELS_CREATE = 'models.create';
    public const MODELS_EDIT   = 'models.edit';
    public const MODELS_DELETE = 'models.delete';

    /** Bug reports — anyone signed in can file + view; triage is gated. */
    public const BUGS_REPORT = 'bugs.report';
    public const BUGS_VIEW   = 'bugs.view';
    public const BUGS_TRIAGE = 'bugs.triage';
    public const BUGS_DELETE = 'bugs.delete';

    public const CONTACTS_VIEW   = 'contacts.view';
    public const CONTACTS_CREATE = 'contacts.create';
    public const CONTACTS_EDIT   = 'contacts.edit';
    public const CONTACTS_DELETE = 'contacts.delete';

    public const NOTIFICATIONS_SEE = 'notifications.see';

    public const ROLE_ADMIN   = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_MEMBER  = 'member';

    /** @return array<int, array{key: string, label: string}> */
    public static function catalog(): array
    {
        return [
            ['key' => self::EVENTS_CREATE,       'label' => 'Create events'],
            ['key' => self::EVENTS_EDIT,         'label' => 'Edit events'],
            ['key' => self::EVENTS_DELETE,       'label' => 'Delete events'],
            ['key' => self::SPONSORS_CREATE,     'label' => 'Create sponsors'],
            ['key' => self::SPONSORS_EDIT,       'label' => 'Edit sponsors'],
            ['key' => self::SPONSORS_DELETE,     'label' => 'Delete sponsors'],
            ['key' => self::TEAM_CREATE,         'label' => 'Create team members'],
            ['key' => self::TEAM_EDIT,           'label' => 'Edit team members'],
            ['key' => self::TEAM_DELETE,         'label' => 'Delete team members'],
            ['key' => self::CONTENT_CREATE,      'label' => 'Create content'],
            ['key' => self::CONTENT_EDIT,        'label' => 'Edit content'],
            ['key' => self::CONTENT_DELETE,      'label' => 'Delete content'],
            ['key' => self::PROJECTS_CREATE,     'label' => 'Create projects'],
            ['key' => self::PROJECTS_EDIT,       'label' => 'Edit projects'],
            ['key' => self::PROJECTS_DELETE,     'label' => 'Delete projects'],
            ['key' => self::GOALS_CREATE,        'label' => 'Create goals'],
            ['key' => self::GOALS_EDIT,          'label' => 'Edit goals'],
            ['key' => self::GOALS_DELETE,        'label' => 'Delete goals'],
            ['key' => self::APPLICATIONS_ACCEPT, 'label' => 'Accept new member entries'],
            ['key' => self::APPLICATIONS_REFUSE, 'label' => 'Refuse new member entries'],
            ['key' => self::APPLICATIONS_EDIT,   'label' => 'Edit new member entries'],
            ['key' => self::TASKS_CREATE,        'label' => 'Create tasks'],
            ['key' => self::TASKS_EDIT,          'label' => 'Edit tasks'],
            ['key' => self::TASKS_DELETE,        'label' => 'Delete tasks'],
            ['key' => self::TASKS_PROGRESS,      'label' => 'Progress own tasks'],
            ['key' => self::MODELS_VIEW,         'label' => 'View 3D models'],
            ['key' => self::MODELS_CREATE,       'label' => 'Create 3D models'],
            ['key' => self::MODELS_EDIT,         'label' => 'Edit 3D models'],
            ['key' => self::MODELS_DELETE,       'label' => 'Delete 3D models'],
            ['key' => self::BUGS_REPORT,         'label' => 'Report bugs'],
            ['key' => self::BUGS_VIEW,           'label' => 'View bug reports'],
            ['key' => self::BUGS_TRIAGE,         'label' => 'Triage / resolve bug reports'],
            ['key' => self::BUGS_DELETE,         'label' => 'Delete bug reports'],
            ['key' => self::CONTACTS_VIEW,       'label' => 'View outer contacts'],
            ['key' => self::CONTACTS_CREATE,     'label' => 'Create outer contacts'],
            ['key' => self::CONTACTS_EDIT,       'label' => 'Edit outer contacts'],
            ['key' => self::CONTACTS_DELETE,     'label' => 'Delete outer contacts'],
            ['key' => self::NOTIFICATIONS_SEE,   'label' => 'See notifications about new member entries'],
        ];
    }

    /** @return array<int, string> */
    public static function adminPermissions(): array
    {
        return array_map(fn ($p) => $p['key'], self::catalog());
    }

    /**
     * Manager: most admin perms minus sponsors, applications,
     * notifications, and any explicit deny-listed key. Every new
     * admin-only key MUST be added to $deny below — otherwise it
     * falls through and managers automatically receive it.
     *
     * @return array<int, string>
     */
    public static function managerPermissions(): array
    {
        $deny = [
            self::NOTIFICATIONS_SEE,
            self::CONTACTS_DELETE,
        ];

        return array_values(array_filter(self::adminPermissions(), function (string $key) use ($deny) {
            return ! in_array($key, $deny, true)
                && ! str_starts_with($key, 'sponsors.')
                && ! str_starts_with($key, 'applications.');
        }));
    }

    /**
     * Member: read-only — except they can file + view bug reports so the
     * report-a-bug page is reachable without admin privileges, and move
     * their own tasks through the workflow (TASKS_PROGRESS is always
     * paired with an assignee / supervisor check on the record itself).
     *
     * @return array<int, string>
     */
    public static function memberPermissions(): array
    {
        return [
            self::BUGS_REPORT,
            self::BUGS_VIEW,
            self::CONTACTS_VIEW,
            self::TASKS_PROGRESS,
        ];
    }
}
