<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    public function members(Request $request): array
    {
        $lang = $this->resolveLang($request);

        $members = TeamMember::query()
            ->with(['groups' => function ($q) {
                $q->where('is_public', true)->orderBy('position');
            }])
            ->whereNull('left_at')
            ->where('is_public', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $members->map(function (TeamMember $member) use ($lang) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'degree' => TeamMemberGroup::pickLocale($member->degree, $lang),
                'photo_url' => $member->photo_path
                    ? Storage::disk('public')->url($member->photo_path)
                    : null,
                'groups' => $member->groups->map(fn (TeamMemberGroup $g) => [
                    'id' => $g->id,
                    'slug' => $g->slug,
                    'name' => TeamMemberGroup::pickLocale($g->name, $lang),
                    'is_primary' => (bool) $g->pivot->is_primary,
                ])->values()->all(),
                'position' => $member->position,
            ];
        })->all();
    }

    public function groups(Request $request): array
    {
        $lang = $this->resolveLang($request);

        return TeamMemberGroup::query()
            ->where('is_public', true)
            ->orderBy('position')
            ->get()
            ->map(fn (TeamMemberGroup $g) => [
                'id' => $g->id,
                'slug' => $g->slug,
                'name' => TeamMemberGroup::pickLocale($g->name, $lang),
                'kind' => $g->kind,
                'position' => $g->position,
            ])
            ->all();
    }

    private function resolveLang(Request $request): string
    {
        $lang = (string) $request->query('lang', 'en');
        return in_array($lang, ['en', 'hu'], true) ? $lang : 'en';
    }
}
