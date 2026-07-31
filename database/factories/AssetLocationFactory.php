<?php

namespace Database\Factories;

use App\Models\AssetLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetLocation>
 */
class AssetLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AssetLocation>
     */
    protected $model = AssetLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'address' => fake()->optional()->address(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
