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
        Schema::table('map_radars', function (Blueprint $table) {
            $table->renameColumn('network_protocols', 'patch_versions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_radars', function (Blueprint $table) {
            $table->renameColumn('patch_versions', 'network_protocols');
        });
    }
};
