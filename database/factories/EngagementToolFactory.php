<?php

namespace Database\Factories;

use App\Models\EngagementTool;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EngagementTool> */
class EngagementToolFactory extends Factory
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
