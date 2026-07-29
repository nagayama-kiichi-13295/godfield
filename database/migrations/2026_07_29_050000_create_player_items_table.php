<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('item', 40);
            $table->timestamps();

            $table->unique(['player_id', 'item']);
        });

        Schema::table('player_characters', function (Blueprint $table) {
            $table->string('eq_weapon', 40)->nullable();
            $table->string('eq_armor', 40)->nullable();
            $table->string('eq_charm', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_items');

        Schema::table('player_characters', function (Blueprint $table) {
            $table->dropColumn(['eq_weapon', 'eq_armor', 'eq_charm']);
        });
    }
};