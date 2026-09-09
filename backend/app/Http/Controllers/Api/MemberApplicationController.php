<?php

namespace App\Http\Controllers\Api;

use App\Auth\Perm;
use App\Http\Controllers\Controller;
use App\Jobs\PostDiscordWebhook;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\NewMemberApplication;
use App\Support\ApplicationForm;
use App\Support\DiscordPayloads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MemberApplicationController extends Controller
{
    /**
     * The form definition the SPA renders — sections, fields, choices,
     * already resolved to one locale.
     *
     * @return array<string, mixed>
     */
    public function form(Request $request): array
    {
        return ApplicationForm::schema($this->resolveLang($request));
    }

    public function store(Request $request): JsonResponse
    {
        // Rules come from the same fields the SPA rendered, so a question
        // the admin adds is validated and one they remove is not — the two
        // can no longer drift apart the way the hand-written list did.
        $data = $request->validate(ApplicationForm::rules());

        [$columns, $answers] = ApplicationForm::split($data);

        $application = MemberApplication::create($columns + [
            'answers' => $answers,
            'status' => MemberApplication::STATUS_PENDING,
        ]);

        // Only fan out to users whose role grants notifications.see —
        // Manager + Member never get pinged about new applications.
        $recipients = User::whereHas('role.permissions', fn ($q) => $q->where('key', Perm::NOTIFICATIONS_SEE))->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewMemberApplication($application));
        }

        $p = DiscordPayloads::newApplication($application);
        PostDiscordWebhook::dispatch($p['content'], $p['embed'], $p['reference'])->afterResponse();

        return response()->json(['id' => $application->id], 201);
    }

    /**
     * Same resolution order as TeamController: an explicit ?lang wins, then
     * whatever SetLocale worked out from the header.
     */
    private function resolveLang(Request $request): string
    {
        $lang = (string) $request->query('lang', '');

        return in_array($lang, ['en', 'hu'], true) ? $lang : app()->getLocale();
    }
}
