<?php

namespace App\Support;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\Cms\Events\EventResource;
use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\CalendarEvent;
use App\Models\Cms\Event;
use App\Models\MemberApplication;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Centralised builders for the embed shapes we hand to PostDiscordWebhook.
 * Keeps the per-event copy + colour palette in one place.
 */
class DiscordPayloads
{
    private const COLOR_APPLICATION = 0xF59E0B;
    private const COLOR_ACCEPTED    = 0x10B981;
    private const COLOR_REJECTED    = 0xEF4444;
    private const COLOR_EVENT       = 0x0EA5E9;
    private const COLOR_TASK        = 0xA855F7;

    public static function adminUrl(string $path = ''): string
    {
        $base = rtrim((string) env('ADMIN_URL', config('app.url') . '/admin'), '/');
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }

    /** @return array{content: string, embed: array<string, mixed>, reference: string} */
    public static function newApplication(MemberApplication $app): array
    {
        return [
            'content' => '📬 New member application',
            'embed' => [
                'title' => $app->name . ($app->department ? ' — ' . $app->department : ''),
                'description' => Str::limit((string) $app->why, 200) ?: null,
                'url' => MemberApplicationResource::getUrl('edit', ['record' => $app->id]),
                'color' => self::COLOR_APPLICATION,
                'fields' => array_values(array_filter([
                    $app->email ? ['name' => 'Email', 'value' => $app->email, 'inline' => true] : null,
                    $app->hours ? ['name' => 'Hours/wk', 'value' => $app->hours, 'inline' => true] : null,
                ])),
                'timestamp' => optional($app->created_at)->toIso8601String(),
            ],
            'reference' => 'application:' . $app->id,
        ];
    }

    /** @return array{content: string, embed: array<string, mixed>, reference: string} */
    public static function applicationAccepted(MemberApplication $app, ?User $reviewer): array
    {
        return [
            'content' => '✅ Application accepted',
            'embed' => [
                'title' => $app->name,
                'description' => 'A new team member account has been provisioned.',
                'url' => MemberApplicationResource::getUrl('edit', ['record' => $app->id]),
                'color' => self::COLOR_ACCEPTED,
                'fields' => array_values(array_filter([
                    ['name' => 'Email', 'value' => $app->email, 'inline' => true],
                    $reviewer ? ['name' => 'Reviewed by', 'value' => $reviewer->name, 'inline' => true] : null,
                ])),
                'timestamp' => now()->toIso8601String(),
            ],
            'reference' => 'application:accept:' . $app->id,
        ];
    }

    /** @return array{content: string, embed: array<string, mixed>, reference: string} */
    public static function applicationRejected(MemberApplication $app, ?User $reviewer): array
    {
        return [
            'content' => '🚫 Application rejected',
            'embed' => [
                'title' => $app->name,
                'description' => $app->department,
                'url' => MemberApplicationResource::getUrl('edit', ['record' => $app->id]),
                'color' => self::COLOR_REJECTED,
                'fields' => array_values(array_filter([
                    $reviewer ? ['name' => 'Reviewed by', 'value' => $reviewer->name, 'inline' => true] : null,
                ])),
                'timestamp' => now()->toIso8601String(),
            ],
            'reference' => 'application:reject:' . $app->id,
        ];
    }

    /** @return array{content: string, embed: array<string, mixed>, reference: string} */
    public static function newEvent(Event $event): array
    {
        $start = $event->payload['start_at'] ?? null;
        $end = $event->payload['end_at'] ?? null;

        $when = null;
        if ($start) {
            $startC = \Carbon\Carbon::parse($start);
            $when = $startC->translatedFormat('d M Y H:i');
            if ($end) {
                $endC = \Carbon\Carbon::parse($end);
                $when .= ' — ' . $endC->translatedFormat($startC->isSameDay($endC) ? 'H:i' : 'd M Y H:i');
            }
        }

        return [
            'content' => '📅 New event added to the calendar',
            'embed' => [
                'title' => $event->title ?? 'Event',
                'description' => Str::limit((string) ($event->content ?? ''), 200) ?: null,
                'url' => EventResource::getUrl('edit', ['record' => $event->id]),
                'color' => self::COLOR_EVENT,
                'fields' => array_values(array_filter([
                    $when ? ['name' => 'When', 'value' => $when, 'inline' => true] : null,
                    ! empty($event->payload['status']) ? ['name' => 'Status', 'value' => (string) $event->payload['status'], 'inline' => true] : null,
                ])),
                'timestamp' => optional($event->created_at)->toIso8601String(),
            ],
            'reference' => 'event:' . $event->id,
        ];
    }

    /** @return array{content: string, embed: array<string, mixed>, reference: string} */
    public static function newCalendarEvent(CalendarEvent $event): array
    {
        $when = null;
        if ($event->start_at) {
            $when = $event->start_at->translatedFormat($event->all_day ? 'd M Y' : 'd M Y H:i');
            if ($event->end_at) {
                $sameDay = $event->start_at->isSameDay($event->end_at);
                $endFmt = $event->all_day || $sameDay
                    ? ($event->all_day ? 'd M Y' : 'H:i')
                    : 'd M Y H:i';
                if ($event->end_at->ne($event->start_at)) {
                    $when .= ' — ' . $event->end_at->translatedFormat($endFmt);
                }
            }
        }

        return [
            'content' => '📅 New event added to the calendar',
            'embed' => [
                'title' => $event->title,
                'description' => Str::limit((string) ($event->description ?? ''), 200) ?: null,
                'url' => Calendar::getUrl(),
                'color' => self::COLOR_EVENT,
                'fields' => array_values(array_filter([
                    $when ? ['name' => 'When', 'value' => $when, 'inline' => true] : null,
                    $event->location ? ['name' => 'Where', 'value' => (string) $event->location, 'inline' => true] : null,
                ])),
                'timestamp' => optional($event->created_at)->toIso8601String(),
            ],
            'reference' => 'calendar-event:' . $event->id,
        ];
    }

    /** @return array{content: string, embed: array<string, mixed>, reference: string} */
    public static function newTaskAssigned(Task $task, User $assignee): array
    {
        return [
            'content' => '🛠 ' . $assignee->name . ' assigned to a task',
            'embed' => [
                'title' => $task->title,
                'description' => Str::limit((string) ($task->description ?? ''), 200) ?: null,
                'url' => TaskResource::getUrl('edit', ['record' => $task->id]),
                'color' => self::COLOR_TASK,
                'fields' => array_values(array_filter([
                    $task->due_date ? ['name' => 'Due', 'value' => $task->due_date->format('d M Y'), 'inline' => true] : null,
                    $task->supervisor?->name ? ['name' => 'Supervisor', 'value' => $task->supervisor->name, 'inline' => true] : null,
                ])),
                'timestamp' => now()->toIso8601String(),
            ],
            'reference' => 'task:assign:' . $task->id . ':' . $assignee->id,
        ];
    }
}
