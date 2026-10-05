<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('announcements')->update([
            'is_published' => DB::raw('show_on_dashboard OR show_on_roadmap'),
        ]);
    }

    public function down(): void {}
};
