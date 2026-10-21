<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PharmacyResource;
use App\Models\Pharmacy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function index(Request $request)
    {
        $query = Pharmacy::where('est_valide', true);

        $now = Carbon::now();

        if ($request->boolean('open')) {
            $pharmacies = $query->get()->filter(function ($pharmacy) use ($now) {
                return $pharmacy->isOpenNow($now);
            });
        } elseif ($request->boolean('garde')) {
            $pharmacies = $query->get()->filter(function ($pharmacy) use ($now) {
                return $pharmacy->isOnDuty($now);
            });
        } else {
            $pharmacies = $query->get();
        }

        return PharmacyResource::collection($pharmacies);
    }
}
