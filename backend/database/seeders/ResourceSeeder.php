<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class ResourceSeeder extends Seeder
{
    private const NAMESPACE_UUID = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    private function deterministicUuid(string $name): string
    {
        return Uuid::uuid5(self::NAMESPACE_UUID, $name)->toString();
    }

    public function run(): void
    {
        $collections = CollectionSeeder::COLLECTIONS;

        // Home page (rocket model placement data). The brand name is intentionally
        // not translated.
        Resource::updateOrCreate(
            ['id' => 'f8e49c86-d46a-4720-8f26-3d01499b13c4'],
            [
                'collection_id' => $collections['pages']['id'],
                'payload' => [
                    'name' => 'home',
                    'title' => 'Escape Velocity Rocketry',
                    'content' => null,
                    'data' => [
                        'rocket' => [
                            'scale' => 1.2,
                            'position' => [0, -1.4, 0],
                        ],
                    ],
                ],
                'position' => 0,
            ],
        );

        // About view. "Escape Velocity Rocketry Student Team" stays untranslated
        // inside the localized strings.
        Resource::updateOrCreate(
            ['id' => '90116104-aefd-4240-8e0d-8887668e21a0'],
            [
                'collection_id' => $collections['views']['id'],
                'payload' => [
                    'name' => 'about',
                    'title' => [
                        'en' => 'About',
                        'hu' => 'Rólunk',
                    ],
                    'content' => [
                        'en' => 'Escape Velocity Rocketry Student Team (EVRST) is a student-led rocketry team based at Óbuda University. We design, build, and launch experimental rockets, push the limits of student engineering, and prepare to compete on the international stage.',
                        'hu' => 'Az Escape Velocity Rocketry Student Team (EVRST) az Óbudai Egyetem hallgatói rakétacsapata. Kísérleti rakétákat tervezünk, építünk és indítunk, miközben a hallgatói mérnöki munka határait feszegetjük, és felkészülünk a nemzetközi megmérettetésekre.',
                    ],
                ],
            ],
        );

        // About projects. `title` is the project name and is not translated.
        $projects = [
            [
                'title' => 'Atlas-1',
                'description' => [
                    'en' => 'Our first sounding rocket prototype: a low-altitude testbed for avionics, recovery, and propulsion subsystems.',
                    'hu' => 'Első kísérleti rakétánk: kis magasságú tesztplatform az avionika, mentés és hajtóművek validálására.',
                ],
            ],
            [
                'title' => 'Helios',
                'description' => [
                    'en' => 'A mid-power rocket targeting 3 km apogee to validate flight computer and dual-deployment recovery.',
                    'hu' => 'Közepes teljesítményű rakéta 3 km-es csúcsmagasságra a fedélzeti számítógép és kettős mentés tesztelésére.',
                ],
            ],
            [
                'title' => 'Voyager',
                'description' => [
                    'en' => 'Long-term: a competition-grade vehicle for international student rocketry challenges.',
                    'hu' => 'Hosszú távú cél: versenyképes rakéta nemzetközi diákversenyekre.',
                ],
            ],
        ];
        foreach ($projects as $i => $project) {
            Resource::updateOrCreate(
                ['id' => $this->deterministicUuid('project-' . $i)],
                [
                    'collection_id' => $collections['about-projects']['id'],
                    'payload' => $project,
                    'position' => $i,
                ],
            );
        }

        // About goals.
        $goals = [
            [
                'title' => [
                    'en' => 'Engineering excellence',
                    'hu' => 'Mérnöki kiválóság',
                ],
                'description' => [
                    'en' => 'Train students through hands-on aerospace engineering — from CAD to test stand to launch pad.',
                    'hu' => 'Hallgatók képzése valós űrtechnikai feladatokon — a CAD-tól a próbapadig és a kilövésig.',
                ],
            ],
            [
                'title' => [
                    'en' => 'Compete internationally',
                    'hu' => 'Nemzetközi verseny',
                ],
                'description' => [
                    'en' => 'Represent Óbuda University at competitions like EuRoC and the Spaceport America Cup.',
                    'hu' => 'Az Óbudai Egyetem képviselete az EuRoC és a Spaceport America Cup versenyeken.',
                ],
            ],
            [
                'title' => [
                    'en' => 'Inspire the next generation',
                    'hu' => 'Inspiráció',
                ],
                'description' => [
                    'en' => 'Share our work openly and grow the Hungarian student space community.',
                    'hu' => 'Munkánk megosztása és a hazai diák-űrközösség erősítése.',
                ],
            ],
        ];
        foreach ($goals as $i => $goal) {
            Resource::updateOrCreate(
                ['id' => $this->deterministicUuid('goal-' . $i)],
                [
                    'collection_id' => $collections['about-goals']['id'],
                    'payload' => $goal,
                    'position' => $i,
                ],
            );
        }

        // Team members + positions live in TeamSeeder.

        // Mentors. Names + emails are not translated.
        $mentors = [
            ['name' => 'Dr. Levente Kiss', 'email' => 'levente.kiss@uni-obuda.hu'],
            ['name' => 'Prof. Mária Németh', 'email' => 'maria.nemeth@uni-obuda.hu'],
        ];
        foreach ($mentors as $i => $mentor) {
            Resource::updateOrCreate(
                ['id' => $this->deterministicUuid('mentor-' . $i)],
                [
                    'collection_id' => $collections['mentors']['id'],
                    'payload' => $mentor,
                    'position' => $i,
                ],
            );
        }

        // Sponsors. `name` is the org name and is not translated.
        $sponsors = [
            [
                'name' => 'Óbuda University',
                'description' => [
                    'en' => 'Host institution.',
                    'hu' => 'Befogadó intézmény.',
                ],
                'year' => 2024,
                'url' => 'https://uni-obuda.hu',
                'logo' => null,
            ],
            [
                'name' => 'SpaceLab',
                'description' => [
                    'en' => 'Lab partner.',
                    'hu' => 'Laborpartner.',
                ],
                'year' => 2024,
                'url' => 'https://spacelab.uni-obuda.hu',
                'logo' => null,
            ],
        ];
        foreach ($sponsors as $i => $sponsor) {
            Resource::updateOrCreate(
                ['id' => $this->deterministicUuid('sponsor-' . $i)],
                [
                    'collection_id' => $collections['sponsors']['id'],
                    'payload' => $sponsor,
                    'position' => $i,
                ],
            );
        }
    }
}
