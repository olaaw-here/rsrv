<?php

namespace Database\Factories;

use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends Factory<ProviderProfile>
 */
class ProviderProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'provider']),
            'business_name' => fake()->company(),
            'description' => fake()->sentence(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'rating_avg' => 0,
            'total_reviews' => 0,
            'status' => 'active',
        ];
    }
}
