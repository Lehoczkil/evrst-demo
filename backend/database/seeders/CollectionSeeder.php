<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public const COLLECTIONS = [
        'pages' => [
            'id' => 'ced793f7-414b-41a7-8693-1e94627227df',
            'name' => 'Pages',
            'description' => 'Dynamic CMS pages rendered as MDX.',
        ],
        'events' => [
            'id' => '36b42185-3a49-43ee-ba79-5cc73075b0d2',
            'name' => 'Events',
            'description' => 'Past and upcoming team events.',
        ],
        'mentors' => [
            'id' => '8639b34c-3415-40cc-85d0-e5ac0eb8d456',
            'name' => 'Mentors',
            'description' => 'Faculty and external mentors.',
        ],
        'sponsors' => [
            'id' => '8aadff44-5a0b-4d84-b570-324db3f11a94',
            'name' => 'Sponsors',
            'description' => 'Companies and orgs that support the team.',
        ],
        'about-projects' => [
            'id' => 'b9b3d531-12cf-4d83-9c39-90a86b4d7c74',
            'name' => 'About — projects',
            'description' => 'Project cards rendered in the About section.',
        ],
        'about-goals' => [
            'id' => 'd3aaad26-e2e3-4f73-9e75-2cba9a85b0a6',
            'name' => 'About — goals',
            'description' => 'Goal cards rendered in the About section.',
        ],
        'views' => [
            'id' => 'f2a4ad4c-f5b8-4d7f-9c2c-9d4d6c0b3aaa',
            'name' => 'Views',
            'description' => 'Long-form MDX content blocks (e.g. About body).',
        ],
    ];

    public function run(): void
    {
        foreach (self::COLLECTIONS as $slug => $info) {
            Collection::updateOrCreate(
                ['id' => $info['id']],
                [
                    'name' => $info['name'],
                    'slug' => $slug,
                    'description' => $info['description'] ?? null,
                ],
            );
        }
    }
}
