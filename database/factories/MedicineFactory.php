<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom_commercial' => fake()->word() . ' ' . fake()->randomElement(['Comprimé', 'Gélule', 'Sirop', 'Crème', 'Pommade']),
            'dci' => fake()->word(),
            'forme' => fake()->randomElement(['comprimé', 'gélule', 'sirop', 'crème', 'pommade']),
            'dosage' => fake()->randomElement(['500mg', '1000mg', '5mg/ml', '10mg', '20mg']),
            'categorie' => fake()->randomElement(['antalgique', 'antibiotique', 'anti-inflammatoire', 'antihistaminique']),
        ];
    }
}
