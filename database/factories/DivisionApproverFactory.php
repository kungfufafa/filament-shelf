<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\DivisionApprover;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DivisionApprover>
 */
class DivisionApproverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DivisionApprover>
     */
    protected $model = DivisionApprover::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'division_id' => Division::inRandomOrder()->first()->id ?? Division::factory(),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'level' => fake()->numberBetween(1, 5),
        ];
    }
}
