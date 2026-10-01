<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_items', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('resource_items')->whereNull('slug')->orderBy('id')->each(function (object $resource): void {
            DB::table('resource_items')->where('id', $resource->id)->update(['slug' => (string) Str::uuid()]);
        });

        Schema::table('resource_items', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }
};
