<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'primary_color')) $table->string('primary_color', 16)->nullable()->after('return_charges');
            if (!Schema::hasColumn('merchants', 'text_color'))    $table->string('text_color', 16)->nullable()->after('primary_color');
            if (!Schema::hasColumn('merchants', 'logo_id'))       $table->unsignedBigInteger('logo_id')->nullable()->after('text_color');
            if (!Schema::hasColumn('merchants', 'light_logo_id')) $table->unsignedBigInteger('light_logo_id')->nullable()->after('logo_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            foreach (['primary_color','text_color','logo_id','light_logo_id'] as $col) {
                if (Schema::hasColumn('merchants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
