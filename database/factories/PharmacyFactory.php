<?php

namespace Database\Factories;

use App\Models\Pharmacy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pharmacy>
 */
class PharmacyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nom' => fake()->company(),
            'adresse' => fake()->streetAddress(),
            'quartier' => fake()->word(),
            'ville' => fake()->city(),
            'latitude' => fake()->latitude(6.0, 6.5),
            'longitude' => fake()->longitude(1.0, 1.5),
            'telephone' => fake()->phoneNumber(),
            'est_valide' => false,
        ];
    }
}
