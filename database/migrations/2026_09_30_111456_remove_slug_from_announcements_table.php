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
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });

        DB::table('announcements')->update(['slug' => DB::raw("'announcement-' || id")]);

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }
};
