<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CatalogOptionFactory extends Factory
{
    public function definition(): array
    {
        return ['kind' => 'category', 'name' => fake()->unique()->words(2, true), 'sort_order' => 0];
    }
}
