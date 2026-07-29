<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_characters', function (Blueprint $table) {
            $table->unsignedInteger('points')->default(0)->after('exp');
            $table->unsignedInteger('pt_hp')->default(0)->after('points');
            $table->unsignedInteger('pt_power')->default(0)->after('pt_hp');
            $table->unsignedInteger('pt_int')->default(0)->after('pt_power');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->json('player1_stats')->nullable();
            $table->json('player2_stats')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('player_characters', function (Blueprint $table) {
            $table->dropColumn(['points', 'pt_hp', 'pt_power', 'pt_int']);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['player1_stats', 'player2_stats']);
        });
    }
};