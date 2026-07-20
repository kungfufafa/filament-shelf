<?php

namespace Database\Factories;

use App\Models\CustomAssetAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomAssetAttribute>
 */
class CustomAssetAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomAssetAttribute>
     */
    protected $model = CustomAssetAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement([
                CustomAssetAttribute::TYPE_TEXT,
                CustomAssetAttribute::TYPE_NUMBER,
                CustomAssetAttribute::TYPE_TEXTAREA,
                CustomAssetAttribute::TYPE_DATE,
                CustomAssetAttribute::TYPE_DOCUMENT_EXPIRY,
            ]),
            'required' => fake()->boolean(20),
            'is_active' => true,
            'category_id' => [],
            'is_notifiable' => fake()->boolean(30),
            'notification_type' => fake()->optional()->randomElement(['fixed_date', 'relative_date', 'monthly']),
            'notification_offset' => fake()->optional()->numberBetween(1, 30),
            'fixed_notification_date' => null,
            'notification_channels' => [],
            'notification_recipient_user_ids' => [],
            'notification_recipient_emails' => [],
            'notification_recipient_whatsapp_numbers' => [],
        ];
    }
}
