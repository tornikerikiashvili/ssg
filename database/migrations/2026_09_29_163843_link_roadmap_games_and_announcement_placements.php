<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE game_regions DROP CONSTRAINT game_regions_status_check');
        DB::statement("ALTER TABLE game_regions ADD CONSTRAINT game_regions_status_check CHECK (status IN ('available', 'limited', 'unavailable', 'upcoming'))");
        Schema::table('games', function (Blueprint $table) {
            $table->string('release_status')->default('released');
            $table->boolean('preview_enabled')->default(false);
        });
        Schema::table('roadmap_items', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('milestone_type')->default('initial_release');
            $table->unsignedTinyInteger('progress')->nullable();
            $table->string('target_quarter')->nullable();
        });
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('show_on_dashboard')->default(true);
            $table->boolean('show_on_roadmap')->default(false);
        });
    }

    public function down(): void
    {
        DB::table('game_regions')->where('status', 'upcoming')->update(['status' => 'unavailable']);
        DB::statement('ALTER TABLE game_regions DROP CONSTRAINT game_regions_status_check');
        DB::statement("ALTER TABLE game_regions ADD CONSTRAINT game_regions_status_check CHECK (status IN ('available', 'limited', 'unavailable'))");
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn(['show_on_dashboard', 'show_on_roadmap']));
        Schema::table('roadmap_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn(['milestone_type', 'progress', 'target_quarter']);
        });
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn(['release_status', 'preview_enabled']));
    }
};
