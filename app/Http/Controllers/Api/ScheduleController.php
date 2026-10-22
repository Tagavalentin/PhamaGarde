<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpeningHour;
use App\Models\DutySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScheduleController extends Controller
{
    // Horaires
    public function getHours(Request $request)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $hours = $pharmacy->openingHours()->orderBy('jour_semaine')->get();
        
        // Grouper par jour
        $grouped = [];
        for ($i = 1; $i <= 7; $i++) {
            $grouped[$i] = $hours->where('jour_semaine', $i)->values();
        }

        return response()->json($grouped);
    }

    public function updateHours(Request $request)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $request->validate([
            'hours' => 'required|array',
            'hours.*.jour_semaine' => 'required|integer|between:1,7',
            'hours.*.heure_ouverture' => 'nullable|date_format:H:i',
            'hours.*.heure_fermeture' => 'nullable|date_format:H:i',
        ]);

        // Supprimer les anciens horaires
        $pharmacy->openingHours()->delete();

        // Créer les nouveaux horaires
        foreach ($request->hours as $hourData) {
            if (empty($hourData['heure_ouverture']) && empty($hourData['heure_fermeture'])) {
                continue; // Jour fermé
            }

            $pharmacy->openingHours()->create([
                'jour_semaine' => $hourData['jour_semaine'],
                'heure_ouverture' => $hourData['heure_ouverture'],
                'heure_fermeture' => $hourData['heure_fermeture'],
            ]);
        }

        // Vider le cache de la liste de pharmacies
        Cache::forget('pharmacies_list');

        return response()->json(['message' => 'Hours updated']);
    }

    // Gardes
    public function getDuties(Request $request)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $duties = $pharmacy->dutySchedules()->orderBy('date_debut')->get();

        return response()->json($duties);
    }

    public function storeDuty(Request $request)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $request->validate([
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        // Vérifier le chevauchement
        $hasOverlap = $pharmacy->dutySchedules()
            ->where(function ($query) use ($request) {
                $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                      ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('date_debut', '<=', $request->date_debut)
                            ->where('date_fin', '>=', $request->date_fin);
                      });
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json(['message' => 'Duty schedule overlaps with existing schedule'], 422);
        }

        $duty = $pharmacy->dutySchedules()->create([
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);

        // Vider le cache de la liste de garde
        Cache::forget('pharmacies_list');

        return response()->json($duty, 201);
    }

    public function destroyDuty(Request $request, $id)
    {
        $pharmacy = $request->user()->pharmacy;
        
        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy not validated'], 403);
        }

        $duty = $pharmacy->dutySchedules()->findOrFail($id);
        $duty->delete();

        // Vider le cache de la liste de garde
        Cache::forget('pharmacies_list');

        return response()->json(['message' => 'Duty deleted']);
    }
}
