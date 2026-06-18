<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $cols = [
                'sidebar_color'      => ['string', 16],
                'sidebar_text_color' => ['string', 16],
                'topbar_color'       => ['string', 16],
                'topbar_text_color'  => ['string', 16],
                'accent_color'       => ['string', 16],
                // Enum-as-string. dark | light | brand.
                'sidebar_style'      => ['string', 16],
                // Whitelist enforced by repository: inter | cairo | tajawal | roboto | system.
                'font_family'        => ['string', 32],
                // sharp | default | rounded.
                'border_radius'      => ['string', 16],
                // dense | comfortable.
                'density'            => ['string', 16],
            ];
            foreach ($cols as $name => [$type, $len]) {
                if (!Schema::hasColumn('merchants', $name)) {
                    $table->{$type}($name, $len)->nullable()->after('text_color');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            foreach ([
                'sidebar_color','sidebar_text_color','topbar_color','topbar_text_color',
                'accent_color','sidebar_style','font_family','border_radius','density',
            ] as $col) {
                if (Schema::hasColumn('merchants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
