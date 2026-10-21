<?php

namespace Database\Factories;

use App\Models\OpeningHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningHour>
 */
class OpeningHourFactory extends Factory
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
            'jour_semaine' => fake()->randomElement(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']),
            'heure_ouverture' => fake()->time('H:i'),
            'heure_fermeture' => fake()->time('H:i'),
        ];
    }
}
