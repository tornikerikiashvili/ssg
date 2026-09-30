<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'pages' => ['dashboard'], 'variant' => 'standard', 'is_published' => true];
    }
}
