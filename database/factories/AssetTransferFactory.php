<?php

namespace Database\Factories;

use App\Models\AssetTransfer;
use App\Models\BusinessEntity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetTransfer>
 */
class AssetTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AssetTransfer>
     */
    protected $model = AssetTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_entity_id' => BusinessEntity::inRandomOrder()->first()->id ?? BusinessEntity::factory(),
            'letter_number' => fake()->unique()->bothify('??######'),
            'from_user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'to_user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'document' => null,
            'transfer_date' => fake()->dateTimeThisYear(),
        ];
    }
}
