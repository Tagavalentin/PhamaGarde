<?php

namespace Database\Factories;

use App\Models\PharmacyMedicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PharmacyMedicine>
 */
class PharmacyMedicineFactory extends Factory
{
    protected $model = PharmacyMedicine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'statut' => fake()->randomElement(['en stock', 'stock faible', 'rupture']),
            'quantite' => fake()->numberBetween(0, 100),
            'prix' => fake()->randomFloat(2, 500, 50000),
            'updated_at' => now(),
        ];
    }
}
