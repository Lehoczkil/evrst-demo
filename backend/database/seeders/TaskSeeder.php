<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@evrst.test')->first();
        if (! $admin) return;

        $supervisor = User::where('email', '!=', $admin->email)->first() ?? $admin;
        $assigneeIds = User::where('email', '!=', $admin->email)->pluck('id')->take(2)->all();

        // Idempotent: keyed on title — re-running the seeder won't double up.
        $samples = [
            [
                'title' => 'Wire avionics harness',
                'description' => 'Solder + bench-test the new flight harness, then dry-fit in the airframe.',
                'status' => Task::STATUS_TODO,
                'due_date' => now()->addDays(5),
            ],
            [
                'title' => 'Static fire test',
                'description' => 'Bench test the propulsion stack and capture telemetry.',
                'status' => Task::STATUS_IN_PROGRESS,
                'due_date' => now()->addDays(2),
            ],
            [
                'title' => 'Update mission patch artwork',
                'description' => 'Iterate on patch v3 with the marketing team.',
                'status' => Task::STATUS_TESTING,
                'due_date' => null,
            ],
            [
                'title' => 'Press kit ready for Q3',
                'description' => 'Assemble the team bio + photos + project summary into a single PDF.',
                'status' => Task::STATUS_DONE,
                'due_date' => now()->subDays(3),
            ],
        ];

        foreach ($samples as $i => $sample) {
            $task = Task::updateOrCreate(
                ['title' => $sample['title']],
                array_merge($sample, [
                    'supervisor_id' => $supervisor->id,
                    'created_by' => $admin->id,
                    'position' => $i,
                ]),
            );
            if (! empty($assigneeIds)) {
                $task->assignees()->sync($assigneeIds);
            }
        }
    }
}
