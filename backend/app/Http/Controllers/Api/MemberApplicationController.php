<?php

namespace App\Http\Controllers\Api;

use App\Auth\Perm;
use App\Http\Controllers\Controller;
use App\Jobs\PostDiscordWebhook;
use App\Models\MemberApplication;
use App\Models\User;
use App\Notifications\NewMemberApplication;
use App\Support\DiscordPayloads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MemberApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'university' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:64'],
            'faculty' => ['nullable', 'string', 'max:255'],
            'why' => ['nullable', 'string', 'max:5000'],
            'hours' => ['nullable', 'string', 'max:64'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'tasks' => ['nullable', 'string', 'max:5000'],
            'skills' => ['nullable', 'string', 'max:5000'],
        ]);

        $application = MemberApplication::create($data + [
            'status' => MemberApplication::STATUS_PENDING,
        ]);

        // Only fan out to users whose role grants notifications.see —
        // Manager + Member never get pinged about new applications.
        $recipients = User::whereHas('role.permissions', fn ($q) => $q->where('key', Perm::NOTIFICATIONS_SEE))->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewMemberApplication($application));
        }

        $p = DiscordPayloads::newApplication($application);
        PostDiscordWebhook::dispatch($p['content'], $p['embed'], $p['reference']);

        return response()->json(['id' => $application->id], 201);
    }
}
