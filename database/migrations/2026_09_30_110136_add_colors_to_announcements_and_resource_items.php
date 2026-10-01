<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('color')->default('green');
        });
        Schema::table('resource_items', function (Blueprint $table) {
            $table->string('color')->default('blue');
        });

        DB::table('announcements')->where('priority', 'important')->update(['color' => 'red-orange']);
        DB::table('resource_items')->where('kind', 'download')->update(['color' => 'red-orange']);
        DB::table('resource_items')->whereIn('kind', ['license', 'certificate'])->update(['color' => 'pink']);
    }

    public function down(): void
    {
        foreach (['announcements', 'resource_items'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
