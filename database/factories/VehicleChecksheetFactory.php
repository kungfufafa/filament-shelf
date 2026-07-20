<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\VehicleChecksheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleChecksheet>
 */
class VehicleChecksheetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<VehicleChecksheet>
     */
    protected $model = VehicleChecksheet::class;

    /**
     * Define the model's default state.
     *
     * `rental_duration` and `distance_traveled` are recomputed by the model's
     * saving hook, so they are left out of the factory definition.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startKm = fake()->numberBetween(1000, 50000);
        $departure = fake()->dateTimeThisMonth();

        return [
            'asset_id' => Asset::inRandomOrder()->first()->id ?? Asset::factory(),
            'reference_number' => fake()->unique()->bothify('VC-#####'),
            'pic' => fake()->optional()->name(),
            'license_plate' => strtoupper(fake()->bothify('?#####')),
            'location' => fake()->optional()->city(),
            'destination' => fake()->optional()->city(),
            'remarks' => fake()->optional()->sentence(),
            'start_km' => $startKm,
            'departure_time' => $departure,
            'departure_photo' => null,
            'departure_damage_report' => null,
            'end_km' => $startKm + fake()->numberBetween(10, 500),
            'return_time' => fake()->dateTimeInInterval($departure, '+1 day'),
            'return_photo' => null,
            'return_damage_report' => null,
        ];
    }
}
