<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Sorted as a usability ranking for the nationality dropdown:
        // GCC first, then the rest of MENA, then the major nationality
        // sources for the KSA logistics workforce (South Asia, SE Asia,
        // East Africa), then global misc, then 'Other' as a fallback.
        // updateOrInsert keyed on `code` keeps this idempotent.
        $rows = [
            // GCC
            ['code' => 'KSA', 'name' => 'المملكة العربية السعودية', 'en_name' => 'Saudi Arabia',         'sorting' => 1],
            ['code' => 'UAE', 'name' => 'الإمارات العربية المتحدة', 'en_name' => 'United Arab Emirates', 'sorting' => 2],
            ['code' => 'KWT', 'name' => 'الكويت',                  'en_name' => 'Kuwait',               'sorting' => 3],
            ['code' => 'QAT', 'name' => 'قطر',                     'en_name' => 'Qatar',                'sorting' => 4],
            ['code' => 'BHR', 'name' => 'البحرين',                 'en_name' => 'Bahrain',              'sorting' => 5],
            ['code' => 'OMN', 'name' => 'عُمان',                    'en_name' => 'Oman',                 'sorting' => 6],

            // MENA
            ['code' => 'YEM', 'name' => 'اليمن',                   'en_name' => 'Yemen',                'sorting' => 10],
            ['code' => 'JOR', 'name' => 'الأردن',                  'en_name' => 'Jordan',               'sorting' => 11],
            ['code' => 'PSE', 'name' => 'فلسطين',                  'en_name' => 'Palestine',            'sorting' => 12],
            ['code' => 'LBN', 'name' => 'لبنان',                   'en_name' => 'Lebanon',              'sorting' => 13],
            ['code' => 'SYR', 'name' => 'سوريا',                   'en_name' => 'Syria',                'sorting' => 14],
            ['code' => 'IRQ', 'name' => 'العراق',                  'en_name' => 'Iraq',                 'sorting' => 15],
            ['code' => 'EGY', 'name' => 'مصر',                     'en_name' => 'Egypt',                'sorting' => 20],
            ['code' => 'SDN', 'name' => 'السودان',                 'en_name' => 'Sudan',                'sorting' => 21],
            ['code' => 'LBY', 'name' => 'ليبيا',                   'en_name' => 'Libya',                'sorting' => 22],
            ['code' => 'TUN', 'name' => 'تونس',                    'en_name' => 'Tunisia',              'sorting' => 23],
            ['code' => 'DZA', 'name' => 'الجزائر',                 'en_name' => 'Algeria',              'sorting' => 24],
            ['code' => 'MAR', 'name' => 'المغرب',                  'en_name' => 'Morocco',              'sorting' => 25],
            ['code' => 'MRT', 'name' => 'موريتانيا',                'en_name' => 'Mauritania',           'sorting' => 26],
            ['code' => 'DJI', 'name' => 'جيبوتي',                  'en_name' => 'Djibouti',             'sorting' => 27],
            ['code' => 'SOM', 'name' => 'الصومال',                 'en_name' => 'Somalia',              'sorting' => 28],
            ['code' => 'TUR', 'name' => 'تركيا',                   'en_name' => 'Türkiye',              'sorting' => 29],
            ['code' => 'IRN', 'name' => 'إيران',                   'en_name' => 'Iran',                 'sorting' => 30],

            // South Asia (major KSA logistics workforce)
            ['code' => 'PAK', 'name' => 'باكستان',                 'en_name' => 'Pakistan',             'sorting' => 40],
            ['code' => 'IND', 'name' => 'الهند',                   'en_name' => 'India',                'sorting' => 41],
            ['code' => 'BGD', 'name' => 'بنغلاديش',                'en_name' => 'Bangladesh',           'sorting' => 42],
            ['code' => 'LKA', 'name' => 'سريلانكا',                'en_name' => 'Sri Lanka',            'sorting' => 43],
            ['code' => 'NPL', 'name' => 'نيبال',                    'en_name' => 'Nepal',                'sorting' => 44],
            ['code' => 'AFG', 'name' => 'أفغانستان',               'en_name' => 'Afghanistan',          'sorting' => 45],

            // SE Asia
            ['code' => 'PHL', 'name' => 'الفلبين',                 'en_name' => 'Philippines',          'sorting' => 50],
            ['code' => 'IDN', 'name' => 'إندونيسيا',                'en_name' => 'Indonesia',            'sorting' => 51],
            ['code' => 'MYS', 'name' => 'ماليزيا',                 'en_name' => 'Malaysia',             'sorting' => 52],
            ['code' => 'VNM', 'name' => 'فيتنام',                  'en_name' => 'Vietnam',              'sorting' => 53],
            ['code' => 'THA', 'name' => 'تايلاند',                 'en_name' => 'Thailand',             'sorting' => 54],

            // East / Horn of Africa + West Africa
            ['code' => 'ETH', 'name' => 'إثيوبيا',                 'en_name' => 'Ethiopia',             'sorting' => 60],
            ['code' => 'ERI', 'name' => 'إريتريا',                 'en_name' => 'Eritrea',              'sorting' => 61],
            ['code' => 'KEN', 'name' => 'كينيا',                    'en_name' => 'Kenya',                'sorting' => 62],
            ['code' => 'UGA', 'name' => 'أوغندا',                   'en_name' => 'Uganda',               'sorting' => 63],
            ['code' => 'TZA', 'name' => 'تنزانيا',                 'en_name' => 'Tanzania',             'sorting' => 64],
            ['code' => 'NGA', 'name' => 'نيجيريا',                 'en_name' => 'Nigeria',              'sorting' => 65],
            ['code' => 'GHA', 'name' => 'غانا',                    'en_name' => 'Ghana',                'sorting' => 66],

            // Misc commonly seen
            ['code' => 'GBR', 'name' => 'المملكة المتحدة',          'en_name' => 'United Kingdom',       'sorting' => 80],
            ['code' => 'USA', 'name' => 'الولايات المتحدة',         'en_name' => 'United States',        'sorting' => 81],
            ['code' => 'CAN', 'name' => 'كندا',                    'en_name' => 'Canada',               'sorting' => 82],
            ['code' => 'FRA', 'name' => 'فرنسا',                    'en_name' => 'France',               'sorting' => 83],
            ['code' => 'DEU', 'name' => 'ألمانيا',                 'en_name' => 'Germany',              'sorting' => 84],
            ['code' => 'AUS', 'name' => 'أستراليا',                'en_name' => 'Australia',            'sorting' => 85],
            ['code' => 'CHN', 'name' => 'الصين',                   'en_name' => 'China',                'sorting' => 86],
            ['code' => 'JPN', 'name' => 'اليابان',                 'en_name' => 'Japan',                'sorting' => 87],
            ['code' => 'KOR', 'name' => 'كوريا الجنوبية',          'en_name' => 'South Korea',          'sorting' => 88],

            // Fallback
            ['code' => 'OTH', 'name' => 'أخرى',                    'en_name' => 'Other',                'sorting' => 999],
        ];

        foreach ($rows as $row) {
            DB::table('countries')->updateOrInsert(
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
