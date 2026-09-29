<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            foreach (['category_id', 'game_type_id', 'payout_type_id', 'volatility_id'] as $column) {
                $table->foreignId($column)->nullable()->constrained('catalog_options')->restrictOnDelete();
            }
            $table->text('features')->nullable();
            $table->text('rules')->nullable();
            $table->json('specifications')->nullable();
            $table->json('feature_tags')->nullable();
            $table->json('regions')->nullable();
        });
        Schema::table('resource_items', function (Blueprint $table) {
            $table->foreignId('catalog_option_id')->nullable()->constrained('catalog_options')->restrictOnDelete();
        });
        Schema::create('engagement_tool_game', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('engagement_tool_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'engagement_tool_id']);
        });
        foreach (DB::table('games')->distinct()->pluck('category') as $name) {
            $id = DB::table('catalog_options')->insertGetId(['kind' => 'category', 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('games')->where('category', $name)->update(['category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_tool_game');
        Schema::table('resource_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('catalog_option_id'));
        Schema::table('games', function (Blueprint $table) {
            foreach (['category_id', 'game_type_id', 'payout_type_id', 'volatility_id'] as $column) {
                $table->dropConstrainedForeignId($column);
            }
            $table->dropColumn(['features', 'rules', 'specifications', 'feature_tags', 'regions']);
        });
    }
};
