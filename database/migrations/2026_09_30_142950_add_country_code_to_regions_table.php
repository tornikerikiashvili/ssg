<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table): void {
            $table->string('country_code', 32)->nullable()->unique();
        });

        foreach (config('map-countries') as $code => $name) {
            $regions = DB::table('regions')->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->get(['id']);

            if ($regions->count() === 1) {
                DB::table('regions')->where('id', $regions->first()->id)->update(['country_code' => $code]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table): void {
            $table->dropColumn('country_code');
        });
    }
};
