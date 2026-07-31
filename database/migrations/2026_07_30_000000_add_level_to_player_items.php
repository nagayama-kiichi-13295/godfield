<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_items', function (Blueprint $table) {
            $table->unsignedInteger('level')->default(0)->after('item');
            $table->unsignedInteger('shards')->default(0)->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('player_items', function (Blueprint $table) {
            $table->dropColumn(['level', 'shards']);
        });
    }
};