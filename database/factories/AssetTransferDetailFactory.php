<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\AssetTransferDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetTransferDetail>
 */
class AssetTransferDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AssetTransferDetail>
     */
    protected $model = AssetTransferDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_transfer_id' => AssetTransfer::inRandomOrder()->first()->id ?? AssetTransfer::factory(),
            'asset_id' => Asset::inRandomOrder()->first()->id ?? Asset::factory(),
            'equipment' => fake()->optional()->word(),
        ];
    }
}
