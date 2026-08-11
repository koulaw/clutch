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
        Schema::create('demos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->ulid('public_id')->unique();
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->char('checksum_sha256', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->string('status')->default('uploaded');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'checksum_sha256']);
            $table->unique(['storage_disk', 'storage_path']);
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('status')->default('queued');
            $table->string('schema_version')->nullable();
            $table->string('parser_version')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('error_context')->nullable();
            $table->timestamps();

            $table->unique(['demo_id', 'attempt']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('map_name')->index();
            $table->timestamp('started_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->decimal('tick_rate', 6, 3)->nullable();
            $table->string('team_one_name')->nullable();
            $table->string('team_two_name')->nullable();
            $table->unsignedSmallInteger('team_one_score')->default(0);
            $table->unsignedSmallInteger('team_two_score')->default(0);
            $table->string('winner_side')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->unsignedBigInteger('start_tick');
            $table->unsignedBigInteger('freeze_end_tick')->nullable();
            $table->unsignedBigInteger('end_tick');
            $table->string('winner_side')->nullable();
            $table->string('win_reason')->nullable();
            $table->unsignedSmallInteger('team_one_score')->default(0);
            $table->unsignedSmallInteger('team_two_score')->default(0);
            $table->timestamps();

            $table->unique(['game_match_id', 'number']);
            $table->index(['game_match_id', 'start_tick']);
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('steam_id')->nullable()->unique();
            $table->string('name');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('game_match_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('team_name')->nullable();
            $table->string('starting_side')->nullable();
            $table->timestamps();

            $table->unique(['game_match_id', 'player_id']);
            $table->index(['player_id', 'game_match_id']);
        });

        Schema::create('artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_round_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64);
            $table->string('version');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['storage_disk', 'storage_path']);
            $table->index(['analysis_id', 'type']);
            $table->index(['game_round_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artifacts');
        Schema::dropIfExists('game_match_player');
        Schema::dropIfExists('players');
        Schema::dropIfExists('game_rounds');
        Schema::dropIfExists('game_matches');
        Schema::dropIfExists('analyses');
        Schema::dropIfExists('demos');
    }
};
