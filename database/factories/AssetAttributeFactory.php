<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetAttribute;
use App\Models\CustomAssetAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetAttribute>
 */
class AssetAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AssetAttribute>
     */
    protected $model = AssetAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::inRandomOrder()->first()->id ?? Asset::factory(),
            'custom_attribute_id' => CustomAssetAttribute::inRandomOrder()->first()->id ?? CustomAssetAttribute::factory(),
            'attribute_value' => fake()->optional()->word(),
        ];
    }
}
