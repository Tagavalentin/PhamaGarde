<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Pharmacy;
use App\Models\PharmacyMedicine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MedicineAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_excludes_non_validated_pharmacies()
    {
        $medicine = Medicine::factory()->create();
        
        $validatedPharmacy = Pharmacy::factory()->create(['est_valide' => true]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $validatedPharmacy->id,
            'statut' => 'en stock',
        ]);

        $nonValidatedPharmacy = Pharmacy::factory()->create(['est_valide' => false]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $nonValidatedPharmacy->id,
            'statut' => 'en stock',
        ]);

        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'pharmacy_id' => $validatedPharmacy->id,
        ]);
    }

    public function test_a_confirmer_flag_48h_threshold()
    {
        $medicine = Medicine::factory()->create();
        $pharmacy = Pharmacy::factory()->create(['est_valide' => true]);

        $pharmacyMedicine = PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy->id,
            'statut' => 'en stock',
            'updated_at' => Carbon::now()->subHours(47)->subMinutes(59),
        ]);

        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability");
        $response->assertJsonFragment([
            'a_confirmer' => false,
        ]);

        DB::table('pharmacy_medicines')
            ->where('id', $pharmacyMedicine->id)
            ->update(['updated_at' => Carbon::now()->subHours(48)->subMinutes(1)]);
        
        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability");
        $response->assertJsonFragment([
            'a_confirmer' => true,
        ]);
    }

    public function test_distance_sorting()
    {
        $medicine = Medicine::factory()->create();
        
        $pharmacy1 = Pharmacy::factory()->create([
            'est_valide' => true,
            'latitude' => 6.1250,
            'longitude' => 1.2167,
        ]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy1->id,
            'statut' => 'en stock',
        ]);

        $pharmacy2 = Pharmacy::factory()->create([
            'est_valide' => true,
            'latitude' => 6.1300,
            'longitude' => 1.2200,
        ]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy2->id,
            'statut' => 'en stock',
        ]);

        $userLat = 6.1200;
        $userLng = 1.2100;

        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability?lat={$userLat}&lng={$userLng}");

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals($pharmacy1->id, $data[0]['pharmacy_id']);
        $this->assertEquals($pharmacy2->id, $data[1]['pharmacy_id']);
    }

    public function test_exclure_rupture_parameter()
    {
        $medicine = Medicine::factory()->create();
        
        $pharmacy1 = Pharmacy::factory()->create(['est_valide' => true]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy1->id,
            'statut' => 'en stock',
        ]);

        $pharmacy2 = Pharmacy::factory()->create(['est_valide' => true]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy2->id,
            'statut' => 'rupture',
        ]);

        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability");
        $response->assertJsonCount(2);

        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability?exclure_rupture=1");
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'pharmacy_id' => $pharmacy1->id,
        ]);
    }

    public function test_no_n_plus_one_queries()
    {
        $medicine = Medicine::factory()->create();
        
        $pharmacy1 = Pharmacy::factory()->create(['est_valide' => true]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy1->id,
            'statut' => 'en stock',
        ]);

        $pharmacy2 = Pharmacy::factory()->create(['est_valide' => true]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy2->id,
            'statut' => 'en stock',
        ]);

        DB::enableQueryLog();
        
        $this->getJson("/api/pharma/medicines/{$medicine->id}/availability");
        
        $queries = DB::getQueryLog();
        
        $this->assertLessThanOrEqual(7, count($queries), 'Should not have N+1 queries');
    }

    public function test_accessible_without_authentication()
    {
        $medicine = Medicine::factory()->create();
        $pharmacy = Pharmacy::factory()->create(['est_valide' => true]);
        PharmacyMedicine::factory()->create([
            'medicine_id' => $medicine->id,
            'pharmacy_id' => $pharmacy->id,
            'statut' => 'en stock',
        ]);

        $response = $this->getJson("/api/pharma/medicines/{$medicine->id}/availability");

        $response->assertStatus(200);
    }
}
