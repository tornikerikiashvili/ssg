<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_downloads', function (Blueprint $table) {
            $table->dropForeign(['resource_item_id']);
            $table->unsignedBigInteger('resource_item_id')->nullable()->change();
            $table->foreign('resource_item_id')->references('id')->on('resource_items')->nullOnDelete();
            $table->string('resource_title')->nullable();
            $table->string('file_type')->nullable();
        });
        DB::table('resource_downloads')->orderBy('id')->chunkById(200, function ($downloads): void {
            $resources = DB::table('resource_items')->whereIn('id', $downloads->pluck('resource_item_id'))->get()->keyBy('id');
            foreach ($downloads as $download) {
                $resource = $resources->get($download->resource_item_id);
                if ($resource) {
                    DB::table('resource_downloads')->where('id', $download->id)->update([
                        'resource_title' => $resource->title,
                        'file_type' => strtoupper(pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION)),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('resource_downloads', function (Blueprint $table) {
            $table->dropForeign(['resource_item_id']);
            $table->foreign('resource_item_id')->references('id')->on('resource_items')->cascadeOnDelete();
            $table->dropColumn(['resource_title', 'file_type']);
        });
    }
};
