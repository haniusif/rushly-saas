<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $cols = [
                'sidebar_color'      => 16,
                'sidebar_text_color' => 16,
                'topbar_color'       => 16,
                'topbar_text_color'  => 16,
                'accent_color'       => 16,
                'sidebar_style'      => 16,
                'font_family'        => 32,
                'border_radius'      => 16,
                'density'            => 16,
            ];
            foreach ($cols as $name => $len) {
                if (!Schema::hasColumn('general_settings', $name)) {
                    $table->string($name, $len)->nullable()->after('login_layout');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            foreach ([
                'sidebar_color','sidebar_text_color','topbar_color','topbar_text_color',
                'accent_color','sidebar_style','font_family','border_radius','density',
            ] as $col) {
                if (Schema::hasColumn('general_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
