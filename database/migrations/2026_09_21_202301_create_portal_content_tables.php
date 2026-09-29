<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['games', 'resource_items', 'announcements', 'roadmap_items', 'engagement_tools'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
                $table->boolean('is_published')->default(false);
                $table->boolean('is_demo')->default(false);
                $table->text('description')->nullable();
                if ($name === 'games') {
                    $table->string('category')->default('Crash');
                    $table->decimal('rtp', 5, 2)->nullable();
                    $table->date('release_date')->nullable();
                    $table->string('cover_image')->nullable();
                    $table->boolean('is_featured')->default(false);
                }
                if ($name === 'resource_items') {
                    $table->string('kind')->default('download');
                    $table->foreignId('game_id')->nullable()->constrained()->cascadeOnDelete();
                    $table->string('file_path')->nullable();
                    $table->index(['kind', 'is_published']);
                }
                if ($name === 'announcements') {
                    $table->string('priority')->default('info');
                }
                if ($name === 'roadmap_items') {
                    $table->string('status')->default('planned');
                    $table->date('target_date')->nullable();
                }
                if ($name === 'engagement_tools') {
                    $table->string('category')->default('Promotion');
                }
                $table->timestamps();
                $table->index(['is_published', 'company_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['engagement_tools', 'roadmap_items', 'announcements', 'resource_items', 'games'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
