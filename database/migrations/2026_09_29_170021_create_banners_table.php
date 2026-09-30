<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('label')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('variant')->default('standard');
            $table->json('pages');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('game_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('show_game_stats')->default(true);
            $table->string('logo_path')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('original_artwork')->nullable();
            $table->boolean('use_original_video')->default(false);
            $table->string('primary_label')->nullable();
            $table->string('primary_target')->default('none');
            $table->string('primary_url', 2048)->nullable();
            $table->string('secondary_label')->nullable();
            $table->string('secondary_target')->default('none');
            $table->string('secondary_url', 2048)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
