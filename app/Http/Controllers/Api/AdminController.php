<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function pendingPharmacies()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pharmacies = Pharmacy::where('est_valide', false)
            ->with('user')
            ->get();

        return response()->json($pharmacies);
    }

    public function approvePharmacy(Request $request, $id)
    {
        $pharmacy = Pharmacy::findOrFail($id);
        Gate::authorize('update', $pharmacy);

        $pharmacy->est_valide = true;
        $pharmacy->save();

        // TODO: Envoyer notification au pharmacien

        return response()->json(['message' => 'Pharmacie validée']);
    }

    public function rejectPharmacy(Request $request, $id)
    {
        $pharmacy = Pharmacy::findOrFail($id);
        Gate::authorize('update', $pharmacy);

        $request->validate([
            'motif' => 'required|string|max:500',
        ]);
        
        // TODO: Envoyer notification avec motif au pharmacien

        // Supprimer la pharmacie
        $pharmacy->delete();

        return response()->json(['message' => 'Pharmacie rejetée']);
    }

    public function importMedicines(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        
        $expectedHeaders = ['nom_commercial', 'dci', 'forme', 'dosage', 'categorie'];
        if ($header !== $expectedHeaders) {
            fclose($handle);
            return response()->json([
                'message' => 'Invalid CSV headers',
                'expected' => $expectedHeaders,
                'found' => $header,
            ], 422);
        }

        $imported = 0;
        $updated = 0;
        $rejected = [];
        $lineNumber = 2; // Start at 2 (header is line 1)

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                $rejected[] = [
                    'line' => $lineNumber,
                    'reason' => 'Missing required fields (nom_commercial, dci)',
                ];
                $lineNumber++;
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
                $rejected[] = [
                    'line' => $lineNumber,
                    'reason' => 'Empty nom_commercial or dci',
                ];
                $lineNumber++;
                continue;
            }

            try {
                $medicine = Medicine::updateOrCreate(
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

                if ($medicine->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $rejected[] = [
                    'line' => $lineNumber,
                    'reason' => $e->getMessage(),
                ];
            }

            $lineNumber++;
        }

        fclose($handle);

        return response()->json([
            'message' => 'Import completed',
            'imported' => $imported,
            'updated' => $updated,
            'rejected' => $rejected,
        ]);
    }
}
