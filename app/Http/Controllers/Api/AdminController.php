<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
}
