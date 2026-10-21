<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidatedPharmacist
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($request->user()->role !== 'pharmacist') {
            return response()->json(['message' => 'User is not a pharmacist'], 403);
        }

        $pharmacy = $request->user()->pharmacy;

        if (!$pharmacy || !$pharmacy->est_valide) {
            return response()->json(['message' => 'Pharmacy is not validated'], 403);
        }

        return $next($request);
    }
}
