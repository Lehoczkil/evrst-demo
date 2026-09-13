<?php

use App\Models\Cms\AboutProject;
use Illuminate\Database\Migrations\Migration;

/**
 * Move the per-vehicle facts onto the vehicle.
 *
 * The SPA kept `state`, the year span and the apogee in its message files
 * and matched them to a project by its POSITION in the collection. Its own
 * comment called that "the honest limitation here" — reordering the
 * projects in the panel handed a rocket someone else's altitude, and none
 * of it could be edited without a deploy.
 *
 * `state` and `years` are backfilled in the order the SPA assumed, because
 * that order is what the site shows today and it is correct: first flown,
 * second in build, third in design.
 *
 * `apogee` is deliberately NOT backfilled. The bundled strings say 640 m
 * for the first vehicle while the seeded first project (Spirit) describes
 * reaching 2000 m — so copying them across would write a wrong number into
 * the database and make it look deliberate. The field starts empty, the
 * SPA leaves the line off a card that has none, and the team fills in the
 * real figures in the panel. That is the whole point of the change.
 */
return new class extends Migration
{
    private const BY_POSITION = [
        ['state' => 'flown',    'years' => '2024 — 2025'],
        ['state' => 'building', 'years' => '2025 — 2026'],
        ['state' => 'design',   'years' => '2027 —'],
    ];

    public function up(): void
    {
        AboutProject::query()
            ->orderBy('position')
            ->orderBy('created_at')
            ->get()
            ->each(function (AboutProject $project, int $i) {
                $meta = self::BY_POSITION[$i] ?? ['state' => 'design', 'years' => ''];

                // Never overwrite something already set by hand.
                if (blank($project->state)) {
                    $project->state = $meta['state'];
                }

                if (blank($project->years) && filled($meta['years'])) {
                    $project->years = $meta['years'];
                }

                $project->save();
            });
    }

    public function down(): void
    {
        AboutProject::query()->get()->each(function (AboutProject $project) {
            $project->state = null;
            $project->years = null;
            $project->apogee = null;
            $project->save();
        });
    }
};
