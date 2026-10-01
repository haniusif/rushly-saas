<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Some currencies now have an official symbol that isn't a reliable font glyph
 * (the 2025 Saudi Riyal and UAE Dirham symbols). symbol_svg holds inline SVG
 * markup for those, so the UI can render the real mark and fall back to the
 * unicode `symbol` when it's empty. SAR/AED are seeded from the SVG assets in
 * database/currency-svg/.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('currencies', 'symbol_svg')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->longText('symbol_svg')->nullable()->after('symbol');
            });
        }

        foreach (['SAR' => 'sar.svg', 'AED' => 'aed.svg'] as $code => $file) {
            $path = database_path('currency-svg/' . $file);
            if (! is_file($path)) {
                continue;
            }
            $svg = trim(file_get_contents($path));
            DB::table('currencies')->where('code', $code)->update(['symbol_svg' => $svg]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('currencies', 'symbol_svg')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->dropColumn('symbol_svg');
            });
        }
    }
};
