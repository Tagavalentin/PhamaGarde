<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\PharmacyMedicine;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $normalizedQuery = mb_strtolower($query);

        $medicines = Medicine::select('id', 'nom_commercial', 'dci', 'forme', 'dosage')
            ->where(function ($q) use ($normalizedQuery) {
                $q->whereRaw('LOWER(nom_commercial) LIKE ?', [$normalizedQuery . '%'])
                  ->orWhereRaw('LOWER(dci) LIKE ?', [$normalizedQuery . '%']);
            })
            ->limit(10)
            ->get();

        return response()->json($medicines);
    }

    public function availability(Request $request, $id)
    {
        $medicine = Medicine::findOrFail($id);

        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $exclureRupture = $request->boolean('exclure_rupture');

        $query = PharmacyMedicine::with(['pharmacy' => function ($q) {
            $q->where('est_valide', true);
        }])
            ->where('medicine_id', $id)
            ->whereHas('pharmacy', function ($q) {
                $q->where('est_valide', true);
            });

        if ($exclureRupture) {
            $query->where('statut', '!=', 'rupture');
        }

        $pharmacyMedicines = $query->get();

        $now = now();
        $results = [];

        foreach ($pharmacyMedicines as $pm) {
            $pharmacy = $pm->pharmacy;
            if (!$pharmacy) continue;

            $distance = null;
            if ($lat && $lng && $pharmacy->latitude && $pharmacy->longitude) {
                $distance = $this->calculateDistance($lat, $lng, $pharmacy->latitude, $pharmacy->longitude);
            }

            $updatedAt = is_string($pm->updated_at) ? Carbon::parse($pm->updated_at) : $pm->updated_at;
            $aConfirmer = $updatedAt->diffInHours($now) > 48;

            $results[] = [
                'pharmacy_id' => $pharmacy->id,
                'nom' => $pharmacy->nom,
                'adresse' => $pharmacy->adresse,
                'quartier' => $pharmacy->quartier,
                'ville' => $pharmacy->ville,
                'telephone' => $pharmacy->telephone,
                'latitude' => $pharmacy->latitude,
                'longitude' => $pharmacy->longitude,
                'statut_stock' => $pm->statut,
                'quantite' => $pm->quantite,
                'prix' => $pm->prix,
                'updated_at' => (string) $pm->updated_at,
                'a_confirmer' => $aConfirmer,
                'distance_km' => $distance,
                'statut_ouverture' => $pharmacy->getStatus(),
            ];
        }

        if ($lat && $lng) {
            usort($results, function ($a, $b) {
                if ($a['distance_km'] === null) return 1;
                if ($b['distance_km'] === null) return -1;
                return $a['distance_km'] <=> $b['distance_km'];
            });
        }

        return response()->json($results);
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }
}
