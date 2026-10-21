<?php

namespace Database\Factories;

use App\Models\DutySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DutySchedule>
 */
class DutyScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pharmacy_id' => null,
            'date_debut' => fake()->date(),
            'date_fin' => fake()->date(),
        ];
    }
}
