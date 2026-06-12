<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalitySeeder extends Seeder
{
    public function run(): void
    {
        // Nationality forms (not country names). updateOrInsert keyed on
        // `code` so the seed is idempotent and safe to re-run.
        // Sorted the same way as CountrySeeder for muscle-memory parity:
        // GCC → MENA → South/SE Asia → East/West Africa → global → 'Other'.
        $rows = [
            // GCC
            ['code' => 'SAU', 'name' => 'سعودي',         'en_name' => 'Saudi',          'sorting' => 1],
            ['code' => 'ARE', 'name' => 'إماراتي',       'en_name' => 'Emirati',        'sorting' => 2],
            ['code' => 'KWT', 'name' => 'كويتي',         'en_name' => 'Kuwaiti',        'sorting' => 3],
            ['code' => 'QAT', 'name' => 'قطري',          'en_name' => 'Qatari',         'sorting' => 4],
            ['code' => 'BHR', 'name' => 'بحريني',        'en_name' => 'Bahraini',       'sorting' => 5],
            ['code' => 'OMN', 'name' => 'عُماني',         'en_name' => 'Omani',          'sorting' => 6],

            // MENA
            ['code' => 'YEM', 'name' => 'يمني',          'en_name' => 'Yemeni',         'sorting' => 10],
            ['code' => 'JOR', 'name' => 'أردني',         'en_name' => 'Jordanian',      'sorting' => 11],
            ['code' => 'PSE', 'name' => 'فلسطيني',       'en_name' => 'Palestinian',    'sorting' => 12],
            ['code' => 'LBN', 'name' => 'لبناني',        'en_name' => 'Lebanese',       'sorting' => 13],
            ['code' => 'SYR', 'name' => 'سوري',          'en_name' => 'Syrian',         'sorting' => 14],
            ['code' => 'IRQ', 'name' => 'عراقي',         'en_name' => 'Iraqi',          'sorting' => 15],
            ['code' => 'EGY', 'name' => 'مصري',          'en_name' => 'Egyptian',       'sorting' => 20],
            ['code' => 'SDN', 'name' => 'سوداني',        'en_name' => 'Sudanese',       'sorting' => 21],
            ['code' => 'LBY', 'name' => 'ليبي',          'en_name' => 'Libyan',         'sorting' => 22],
            ['code' => 'TUN', 'name' => 'تونسي',         'en_name' => 'Tunisian',       'sorting' => 23],
            ['code' => 'DZA', 'name' => 'جزائري',        'en_name' => 'Algerian',       'sorting' => 24],
            ['code' => 'MAR', 'name' => 'مغربي',         'en_name' => 'Moroccan',       'sorting' => 25],
            ['code' => 'MRT', 'name' => 'موريتاني',       'en_name' => 'Mauritanian',    'sorting' => 26],
            ['code' => 'DJI', 'name' => 'جيبوتي',        'en_name' => 'Djiboutian',     'sorting' => 27],
            ['code' => 'SOM', 'name' => 'صومالي',        'en_name' => 'Somali',         'sorting' => 28],
            ['code' => 'TUR', 'name' => 'تركي',          'en_name' => 'Turkish',        'sorting' => 29],
            ['code' => 'IRN', 'name' => 'إيراني',        'en_name' => 'Iranian',        'sorting' => 30],

            // South Asia
            ['code' => 'PAK', 'name' => 'باكستاني',      'en_name' => 'Pakistani',      'sorting' => 40],
            ['code' => 'IND', 'name' => 'هندي',          'en_name' => 'Indian',         'sorting' => 41],
            ['code' => 'BGD', 'name' => 'بنغلاديشي',     'en_name' => 'Bangladeshi',    'sorting' => 42],
            ['code' => 'LKA', 'name' => 'سريلانكي',      'en_name' => 'Sri Lankan',     'sorting' => 43],
            ['code' => 'NPL', 'name' => 'نيبالي',         'en_name' => 'Nepali',         'sorting' => 44],
            ['code' => 'AFG', 'name' => 'أفغاني',        'en_name' => 'Afghan',         'sorting' => 45],

            // SE Asia
            ['code' => 'PHL', 'name' => 'فلبيني',        'en_name' => 'Filipino',       'sorting' => 50],
            ['code' => 'IDN', 'name' => 'إندونيسي',       'en_name' => 'Indonesian',     'sorting' => 51],
            ['code' => 'MYS', 'name' => 'ماليزي',        'en_name' => 'Malaysian',      'sorting' => 52],
            ['code' => 'VNM', 'name' => 'فيتنامي',       'en_name' => 'Vietnamese',     'sorting' => 53],
            ['code' => 'THA', 'name' => 'تايلاندي',      'en_name' => 'Thai',           'sorting' => 54],

            // East / Horn / West Africa
            ['code' => 'ETH', 'name' => 'إثيوبي',        'en_name' => 'Ethiopian',      'sorting' => 60],
            ['code' => 'ERI', 'name' => 'إريتري',        'en_name' => 'Eritrean',       'sorting' => 61],
            ['code' => 'KEN', 'name' => 'كيني',          'en_name' => 'Kenyan',         'sorting' => 62],
            ['code' => 'UGA', 'name' => 'أوغندي',         'en_name' => 'Ugandan',        'sorting' => 63],
            ['code' => 'TZA', 'name' => 'تنزاني',        'en_name' => 'Tanzanian',      'sorting' => 64],
            ['code' => 'NGA', 'name' => 'نيجيري',        'en_name' => 'Nigerian',       'sorting' => 65],
            ['code' => 'GHA', 'name' => 'غاني',          'en_name' => 'Ghanaian',       'sorting' => 66],

            // Misc
            ['code' => 'GBR', 'name' => 'بريطاني',       'en_name' => 'British',        'sorting' => 80],
            ['code' => 'USA', 'name' => 'أمريكي',        'en_name' => 'American',       'sorting' => 81],
            ['code' => 'CAN', 'name' => 'كندي',          'en_name' => 'Canadian',       'sorting' => 82],
            ['code' => 'FRA', 'name' => 'فرنسي',         'en_name' => 'French',         'sorting' => 83],
            ['code' => 'DEU', 'name' => 'ألماني',        'en_name' => 'German',         'sorting' => 84],
            ['code' => 'AUS', 'name' => 'أسترالي',       'en_name' => 'Australian',     'sorting' => 85],
            ['code' => 'CHN', 'name' => 'صيني',          'en_name' => 'Chinese',        'sorting' => 86],
            ['code' => 'JPN', 'name' => 'ياباني',        'en_name' => 'Japanese',       'sorting' => 87],
            ['code' => 'KOR', 'name' => 'كوري',          'en_name' => 'Korean',         'sorting' => 88],

            ['code' => 'OTH', 'name' => 'أخرى',          'en_name' => 'Other',          'sorting' => 999],
        ];

        foreach ($rows as $row) {
            DB::table('nationalities')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, [
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
