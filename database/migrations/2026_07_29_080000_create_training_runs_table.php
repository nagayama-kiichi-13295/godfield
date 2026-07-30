<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('character', 32);
            $table->json('target_kana')->nullable();
            $table->unsignedInteger('words')->default(0);
            $table->unsignedInteger('weak_words')->default(0);
            $table->unsignedInteger('typed_chars')->default(0);
            $table->unsignedInteger('miss_count')->default(0);
            $table->unsignedInteger('max_combo')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedInteger('exp_gained')->default(0);
            $table->unsignedInteger('level_before')->default(1);
            $table->unsignedInteger('level_after')->default(1);
            $table->json('miss_map')->nullable();
            $table->boolean('finished')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_runs');
    }
};