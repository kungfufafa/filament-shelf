<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetRequestItem>
 */
class AssetRequestItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AssetRequestItem>
     */
    protected $model = AssetRequestItem::class;

    /**
     * Define the model's default state.
     *
     * `item_name` and `qty` are auto-filled by the model's creating hook when
     * an asset is attached, so they may be left null here.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_request_id' => AssetRequest::inRandomOrder()->first()->id ?? AssetRequest::factory(),
            'asset_id' => null,
            'item_name' => fake()->optional()->words(2, true),
            'qty' => fake()->numberBetween(1, 10),
            'notes' => fake()->optional()->sentence(),
            'fulfilled_asset_id' => null,
            'fulfilled_at' => null,
        ];
    }
}
