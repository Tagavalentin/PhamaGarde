<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PharmacistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacist = User::create([
            'name' => 'Pharmacien Test',
            'email' => 'pharmacist@pharmagarde.tg',
            'password' => Hash::make('password'),
            'role' => 'pharmacist',
            'phone' => '+22890000002',
        ]);

        Pharmacy::create([
            'user_id' => $pharmacist->id,
            'nom' => 'Pharmacie Centrale Lomé',
            'adresse' => '123 Avenue de l\'Indépendance',
            'quartier' => 'Tokoin',
            'ville' => 'Lomé',
            'latitude' => 6.1250,
            'longitude' => 1.2167,
            'telephone' => '+22890000003',
            'est_valide' => true,
        ]);
    }
}
