<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Resource;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => ProviderProfile::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['tempat', 'lapangan', 'konsultasi']),
            'description' => fake()->paragraph(),
            'capacity' => fake()->numberBetween(1, 50),
            'slot_duration_minutes' => 60,
            'base_price' => fake()->numberBetween(50_000, 500_000),
            'status' => 'active',
        ];
    }
}
