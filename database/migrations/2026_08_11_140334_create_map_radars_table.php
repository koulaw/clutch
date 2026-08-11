<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('map_radars', function (Blueprint $table) {
            $table->id();
            $table->string('map_name');
            $table->string('version');
            $table->json('network_protocols');
            $table->string('image_path');
            $table->unsignedSmallInteger('image_width');
            $table->unsignedSmallInteger('image_height');
            $table->char('checksum_sha256', 64);
            $table->json('coordinate_transform');
            $table->timestamps();

            $table->unique(['map_name', 'version']);
            $table->index('map_name');
        });

        Schema::table('game_matches', function (Blueprint $table) {
            $table->foreignId('map_radar_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('map_radar_id');
        });

        Schema::dropIfExists('map_radars');
    }
};
