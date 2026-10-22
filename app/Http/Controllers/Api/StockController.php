<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PharmacyMedicine;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $query = $pharmacy->pharmacyMedicines()->with('medicine');

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('medicine', function ($q) use ($search) {
                $q->where('nom_commercial', 'like', "%{$search}%")
                  ->orWhere('dci', 'like', "%{$search}%");
            });
        }

        $stocks = $query->paginate($request->per_page ?? 15);

        return response()->json($stocks);
    }

    public function update(Request $request, $medicine_id)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $medicine = Medicine::findOrFail($medicine_id);

        $request->validate([
            'statut' => 'required|in:en stock,stock faible,rupture',
            'quantite' => 'nullable|integer|min:0',
            'prix' => 'nullable|numeric|min:0',
        ]);

        $stock = PharmacyMedicine::updateOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
                'medicine_id' => $medicine_id,
            ],
            [
                'statut' => $request->statut,
                'quantite' => $request->quantite,
                'prix' => $request->prix,
            ]
        );

        // Vider le cache de disponibilité
        Cache::forget("medicine_{$medicine_id}_availability");

        return response()->json($stock);
    }

    public function destroy(Request $request, $medicine_id)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $stock = PharmacyMedicine::where('pharmacy_id', $pharmacy->id)
            ->where('medicine_id', $medicine_id)
            ->firstOrFail();

        $stock->delete();

        // Vider le cache de disponibilité
        Cache::forget("medicine_{$medicine_id}_availability");

        return response()->json(['message' => 'Stock deleted']);
    }
}
