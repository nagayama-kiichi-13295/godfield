<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('player1_id')->nullable()->after('id')->constrained('players')->nullOnDelete();
            $table->foreignId('player2_id')->nullable()->after('player1_id')->constrained('players')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->after('winner')->constrained('players')->nullOnDelete();
            $table->string('player1_char', 32)->nullable();
            $table->string('player2_char', 32)->nullable();
            $table->unsignedInteger('player1_level')->default(1);
            $table->unsignedInteger('player2_level')->default(1);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->string('name', 20)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('player1_id');
            $table->dropConstrainedForeignId('player2_id');
            $table->dropConstrainedForeignId('winner_id');
            $table->dropColumn(['player1_char', 'player2_char', 'player1_level', 'player2_level']);
        });
    }
};