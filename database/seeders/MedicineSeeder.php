<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/medicines.csv');
        
        if (!File::exists($csvPath)) {
            $this->command->warn('CSV file not found: ' . $csvPath);
            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        
        $imported = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $nom_commercial = trim($row[0]);
            $dci = trim($row[1]);
            $forme = isset($row[2]) ? trim($row[2]) : null;
            $dosage = isset($row[3]) ? trim($row[3]) : null;
            $categorie = isset($row[4]) ? trim($row[4]) : null;

            // Normalisation
            $nom_commercial = Str::upper($nom_commercial);
            $dci = Str::upper($dci);

            if (empty($nom_commercial) || empty($dci)) {
                continue;
            }

            Medicine::updateOrCreate(
                [
                    'nom_commercial' => $nom_commercial,
                    'forme' => $forme,
                    'dosage' => $dosage,
                ],
                [
                    'dci' => $dci,
                    'categorie' => $categorie,
                ]
            );

            $imported++;
        }

        fclose($handle);
        
        $this->command->info("Imported {$imported} medicines from CSV.");
    }
}
