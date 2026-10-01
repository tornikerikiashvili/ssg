<?php

namespace Database\Factories;

use App\Models\PortalNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortalNotification> */
class PortalNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'teaser' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'is_published' => true,
        ];
    }
}
