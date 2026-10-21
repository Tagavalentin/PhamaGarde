<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pharmacy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'adresse',
        'quartier',
        'ville',
        'latitude',
        'longitude',
        'telephone',
        'est_valide',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'est_valide' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    public function dutySchedules(): HasMany
    {
        return $this->hasMany(DutySchedule::class);
    }

    public function pharmacyMedicines(): HasMany
    {
        return $this->hasMany(PharmacyMedicine::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function isOpenNow(Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now();
        $dayOfWeek = $this->getDayOfWeek($now);

        $openingHour = $this->openingHours()->where('jour_semaine', $dayOfWeek)->first();

        if (!$openingHour) {
            return false;
        }

        $nowTime = $now->copy();
        $openTime = $nowTime->copy()->setTimeFromTimeString($openingHour->heure_ouverture);
        $closeTime = $nowTime->copy()->setTimeFromTimeString($openingHour->heure_fermeture);

        if ($closeTime->lessThan($openTime)) {
            return $nowTime->greaterThanOrEqualTo($openTime) || $nowTime->lessThan($closeTime);
        }

        return $nowTime->between($openTime, $closeTime);
    }

    public function isOnDuty(Carbon $date = null): bool
    {
        $date = $date ?? Carbon::now();
        $dateString = $date->toDateString();

        return $this->dutySchedules()
            ->where('date_debut', '<=', $dateString)
            ->where('date_fin', '>=', $dateString)
            ->exists();
    }

    public function getStatus(Carbon $now = null): string
    {
        $now = $now ?? Carbon::now();

        if ($this->isOnDuty($now)) {
            return 'garde';
        }

        if ($this->isOpenNow($now)) {
            return 'ouvert';
        }

        return 'ferme';
    }

    private function getDayOfWeek(Carbon $date): string
    {
        $days = [
            0 => 'dimanche',
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
        ];

        return $days[$date->dayOfWeek];
    }
}
