<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Answers become a JSON map keyed by the form field's key.
 *
 * With the questions editable, a fixed column per question cannot work —
 * adding one would mean a migration, and a question the admin removes would
 * leave a column behind. name + email stay real columns: the accept flow
 * provisions a login from them, notifications address them, and the
 * applications table sorts and searches on them.
 *
 * The old columns are backfilled into `answers` under the same keys the
 * seeded form uses, so nothing already submitted is lost, and only then
 * dropped.
 */
return new class extends Migration
{
    private const MOVED = [
        'university',
        'education',
        'faculty',
        'why',
        'hours',
        'languages',
        'department',
        'tasks',
        'skills',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('member_applications', 'answers')) {
            Schema::table('member_applications', function (Blueprint $table) {
                $table->json('answers')->nullable()->after('name');
            });
        }

        $present = array_values(array_filter(
            self::MOVED,
            fn (string $column) => Schema::hasColumn('member_applications', $column),
        ));

        if ($present !== []) {
            DB::table('member_applications')
                ->select(array_merge(['id'], $present))
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($present) {
                    foreach ($rows as $row) {
                        $answers = [];

                        foreach ($present as $column) {
                            $value = $row->{$column};

                            if ($value === null || $value === '') {
                                continue;
                            }

                            // `languages` was an array cast, so it is stored
                            // as a JSON string; everything else is scalar.
                            if ($column === 'languages') {
                                $decoded = json_decode((string) $value, true);
                                $value = is_array($decoded) ? $decoded : [$value];
                            }

                            $answers[$column] = $value;
                        }

                        DB::table('member_applications')
                            ->where('id', $row->id)
                            ->update(['answers' => json_encode($answers, JSON_UNESCAPED_UNICODE)]);
                    }
                });

            Schema::table('member_applications', function (Blueprint $table) use ($present) {
                $table->dropColumn($present);
            });
        }
    }

    public function down(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->string('university')->nullable();
            $table->string('education')->nullable();
            $table->string('faculty')->nullable();
            $table->text('why')->nullable();
            $table->string('hours')->nullable();
            $table->text('languages')->nullable();
            $table->string('department')->nullable();
            $table->text('tasks')->nullable();
            $table->text('skills')->nullable();
        });

        DB::table('member_applications')
            ->select(['id', 'answers'])
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $answers = json_decode((string) $row->answers, true);

                    if (! is_array($answers)) {
                        continue;
                    }

                    $update = [];
                    foreach (self::MOVED as $column) {
                        if (! array_key_exists($column, $answers)) {
                            continue;
                        }

                        $value = $answers[$column];
                        $update[$column] = is_array($value)
                            ? json_encode($value, JSON_UNESCAPED_UNICODE)
                            : $value;
                    }

                    if ($update !== []) {
                        DB::table('member_applications')->where('id', $row->id)->update($update);
                    }
                }
            });

        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropColumn('answers');
        });
    }
};
