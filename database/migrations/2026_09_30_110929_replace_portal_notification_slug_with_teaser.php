<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_notifications', function (Blueprint $table) {
            $table->string('teaser')->nullable();
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('portal_notifications', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });

        DB::table('portal_notifications')->update(['slug' => DB::raw("'notification-' || id")]);

        Schema::table('portal_notifications', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->dropColumn('teaser');
        });
    }
};
