<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->text('dropbox_folder_path')->nullable();
            $table->string('dropbox_folder_id')->nullable();
            $table->timestamp('dropbox_synced_at')->nullable();
            $table->text('dropbox_sync_error')->nullable();
        });
        Schema::table('resource_items', function (Blueprint $table): void {
            $table->string('dropbox_file_id')->nullable();
            $table->text('dropbox_path')->nullable();
            $table->string('dropbox_revision')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('dropbox_available')->default(true);
            $table->unique(['game_id', 'dropbox_file_id']);
        });
    }

    public function down(): void
    {
        Schema::table('resource_items', function (Blueprint $table): void {
            $table->dropUnique(['game_id', 'dropbox_file_id']);
            $table->dropColumn(['dropbox_file_id', 'dropbox_path', 'dropbox_revision', 'file_size', 'dropbox_available']);
        });
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn(['dropbox_folder_path', 'dropbox_folder_id', 'dropbox_synced_at', 'dropbox_sync_error']));
    }
};
