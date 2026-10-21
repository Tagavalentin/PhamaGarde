<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PharmacyIndexRequest;
use App\Http\Resources\PharmacyResource;
use App\Models\Pharmacy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function index(PharmacyIndexRequest $request)
    {
        $query = Pharmacy::where('est_valide', true);

        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $now = Carbon::now();

        if ($lat && $lng) {
            $lat = (float) $lat;
            $lng = (float) $lng;

            $boundingBox = 10.0;
            $latMin = $lat - $boundingBox;
            $latMax = $lat + $boundingBox;
            $lngMin = $lng - $boundingBox;
            $lngMax = $lng + $boundingBox;

            $query->whereBetween('latitude', [$latMin, $latMax])
                  ->whereBetween('longitude', [$lngMin, $lngMax]);
        }

        $pharmacies = $query->get();

        if ($request->boolean('open')) {
            $pharmacies = $pharmacies->filter(function ($pharmacy) use ($now) {
                return $pharmacy->isOpenNow($now);
            });
        } elseif ($request->boolean('garde')) {
            $pharmacies = $pharmacies->filter(function ($pharmacy) use ($now) {
                return $pharmacy->isOnDuty($now);
            });
        }

        if ($lat && $lng) {
            $pharmacies = $pharmacies->map(function ($pharmacy) use ($lat, $lng) {
                $distance = $this->calculateDistance($lat, $lng, $pharmacy->latitude, $pharmacy->longitude);
                $pharmacy->distance_km = round($distance, 1);
                return $pharmacy;
            });

            $pharmacies = $pharmacies->sortBy('distance_km')->values();
        }

        return PharmacyResource::collection($pharmacies);
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
