<?php

namespace Database\Factories;

use App\Models\RoadmapItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RoadmapItem> */
class RoadmapItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'is_published' => true,
        ];
    }
}
