<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
