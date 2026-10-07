<?php

namespace Database\Factories;

use App\Models\FileFormat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FileFormat> */
class FileFormatFactory extends Factory
{
    public function definition(): array
    {
        return ['extension' => fake()->unique()->lexify('ext??????')];
    }
}
