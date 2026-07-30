<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solo_runs', function (Blueprint $table) {
            $table->boolean('is_cleared')->default(false);
            $table->unsignedInteger('level_before')->default(1);
            $table->unsignedInteger('level_after')->default(1);
            $table->unsignedInteger('bonus_exp')->default(0);
            $table->string('drop_item', 40)->nullable();
            $table->unsignedInteger('max_combo')->default(0);
            $table->unsignedInteger('typed_chars')->default(0);
            $table->unsignedInteger('miss_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('solo_runs', function (Blueprint $table) {
            $table->dropColumn([
                'is_cleared', 'level_before', 'level_after', 'bonus_exp',
                'drop_item', 'max_combo', 'typed_chars', 'miss_count', 'duration_ms',
            ]);
        });
    }
};