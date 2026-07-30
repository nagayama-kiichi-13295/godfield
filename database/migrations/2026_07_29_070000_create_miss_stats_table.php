<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('miss_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('kana', 8);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['player_id', 'kana']);
        });

        Schema::table('solo_runs', function (Blueprint $table) {
            $table->json('miss_map')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('miss_stats');

        Schema::table('solo_runs', function (Blueprint $table) {
            $table->dropColumn('miss_map');
        });
    }
};