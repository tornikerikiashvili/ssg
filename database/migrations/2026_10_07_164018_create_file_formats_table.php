<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_formats', function (Blueprint $table): void {
            $table->id();
            $table->text('extension')->unique();
            $table->timestamps();
        });
        Schema::table('resource_items', function (Blueprint $table): void {
            $table->foreignId('file_format_id')->nullable()->constrained()->nullOnDelete();
        });
        DB::table('resource_items')->whereNotNull('dropbox_file_id')->orderBy('id')->chunkById(200, function ($resources): void {
            foreach ($resources as $resource) {
                $extension = mb_strtolower(pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION));
                if ($extension === '') {
                    continue;
                }
                DB::table('file_formats')->insertOrIgnore(['extension' => $extension, 'created_at' => now(), 'updated_at' => now()]);
                $formatId = DB::table('file_formats')->where('extension', $extension)->value('id');
                DB::table('resource_items')->where('id', $resource->id)->update(['file_format_id' => $formatId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('resource_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('file_format_id');
        });
        Schema::dropIfExists('file_formats');
    }
};
