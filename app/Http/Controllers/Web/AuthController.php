<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PharmacistRegisterRequest;
use App\Models\User;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showPharmacistRegister()
    {
        return inertia('InscriptionPharmacien');
    }

    public function registerPharmacist(PharmacistRegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'pharmacist',
        ]);

        $pharmacy = Pharmacy::create([
            'user_id' => $user->id,
            'nom' => $request->nom,
            'adresse' => $request->adresse,
            'quartier' => $request->quartier,
            'ville' => $request->ville,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'telephone' => $request->telephone,
            'est_valide' => false,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Votre inscription a été enregistrée. Votre pharmacie sera validée par l\'administrateur.');
    }
}
