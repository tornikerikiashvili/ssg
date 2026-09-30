<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('game_regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['available', 'limited', 'unavailable'])->default('available');
            $table->timestamps();
            $table->unique(['game_id', 'region_id']);
        });
        foreach (['company_region' => 'company_id', 'region_user' => 'user_id'] as $name => $owner) {
            Schema::create($name, function (Blueprint $table) use ($owner): void {
                $table->foreignId($owner)->constrained()->cascadeOnDelete();
                $table->foreignId('region_id')->constrained()->restrictOnDelete();
                $table->primary([$owner, 'region_id']);
            });
        }

        foreach (DB::table('games')->whereNotNull('regions')->get(['id', 'regions']) as $game) {
            foreach (json_decode($game->regions, true, flags: JSON_THROW_ON_ERROR) ?? [] as $entry) {
                $name = trim($entry['country'] ?? '');
                if ($name === '') {
                    continue;
                }
                $regionId = DB::table('regions')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');
                $regionId ??= DB::table('regions')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('game_regions')->updateOrInsert(['game_id' => $game->id, 'region_id' => $regionId], [
                    'status' => in_array($entry['status'] ?? null, ['available', 'limited', 'unavailable'], true) ? $entry['status'] : 'unavailable',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn('regions'));
    }

    public function down(): void
    {
        Schema::table('games', fn (Blueprint $table) => $table->json('regions')->nullable());
        $entries = DB::table('game_regions')->join('regions', 'regions.id', '=', 'game_regions.region_id')
            ->get(['game_id', 'name', 'status'])->groupBy('game_id');
        foreach ($entries as $gameId => $regions) {
            DB::table('games')->where('id', $gameId)->update(['regions' => json_encode($regions->map(fn (object $region): array => [
                'country' => $region->name, 'status' => $region->status,
            ])->all(), JSON_THROW_ON_ERROR)]);
        }
        Schema::dropIfExists('region_user');
        Schema::dropIfExists('company_region');
        Schema::dropIfExists('game_regions');
        Schema::dropIfExists('regions');
    }
};
