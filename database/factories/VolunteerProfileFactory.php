<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VolunteerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerProfile>
 */
class VolunteerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $supportsF2f = fake()->boolean(70);

        return [
            'user_id' => User::factory(),
            'bio' => fake()->sentence(14),
            // Guarantee every volunteer offers at least one support mode.
            'supports_f2f' => $supportsF2f,
            'supports_virtual' => ! $supportsF2f || fake()->boolean(70),
            'is_active' => true,
        ];
    }
}
