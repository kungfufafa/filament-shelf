<?php

namespace Database\Factories;

use App\Models\BusinessEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessEntity>
 */
class BusinessEntityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<BusinessEntity>
     */
    protected $model = BusinessEntity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'format' => null,
            'color' => fake()->optional()->hexColor(),
            'letterhead' => null,
        ];
    }
}
