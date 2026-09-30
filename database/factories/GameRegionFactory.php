<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameRegion;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GameRegion> */
class GameRegionFactory extends Factory
{
    public function definition(): array
    {
        return ['game_id' => Game::factory(), 'region_id' => Region::factory(), 'status' => 'available'];
    }
}
