<?php

namespace Database\Seeders;

use App\Domain\Services\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Frisør & skjønnhet',
                'description' => 'Skjønnhetssalonger, frisører, makeup-artister og spa-tjenester.',
                'icon' => '💇',
                'sort_order' => 1,
            ],
            [
                'name' => 'Restaurant & café',
                'description' => 'Restauranter, kafeer, bakeri og matlevering.',
                'icon' => '🍽️',
                'sort_order' => 2,
            ],
            [
                'name' => 'E-handel & nettbutikk',
                'description' => 'Nettbutikker, e-commerce og online shopping.',
                'icon' => '🛒',
                'sort_order' => 3,
            ],
            [
                'name' => 'Treningssenter & PT',
                'description' => 'Treningssentre, personlige trenere og fitness.',
                'icon' => '💪',
                'sort_order' => 4,
            ],
            [
                'name' => 'Eiendom & bolig',
                'description' => 'Eiendomsmeglere, boligsalg og utleie.',
                'icon' => '🏠',
                'sort_order' => 5,
            ],
            [
                'name' => 'Håndverkere & tjenester',
                'description' => 'Håndverkere, rørleggere, elektrikere og andre tjenester.',
                'icon' => '🔧',
                'sort_order' => 6,
            ],
            [
                'name' => 'Influencere & UGC',
                'description' => 'Innholdsskapere, influencere og brukergenerert innhold.',
                'icon' => '📱',
                'sort_order' => 7,
            ],
            [
                'name' => 'Teknologi & IT',
                'description' => 'Programvare, apper, webutvikling og IT-tjenester.',
                'icon' => '💻',
                'sort_order' => 8,
            ],
            [
                'name' => 'Helse & velvære',
                'description' => 'Helseklinikker, terapi, yoga og velvære.',
                'icon' => '🧘',
                'sort_order' => 9,
            ],
            [
                'name' => 'Utdanning & kurs',
                'description' => 'Online kurs, undervisning og opplæring.',
                'icon' => '📚',
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                array_merge($category, [
                    'slug' => Str::slug($category['name']),
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}
