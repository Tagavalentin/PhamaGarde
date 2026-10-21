<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'adresse' => $this->adresse,
            'quartier' => $this->quartier,
            'ville' => $this->ville,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'telephone' => $this->telephone,
            'statut' => $this->getStatus(Carbon::now('Africa/Lome')),
        ];
    }
}
