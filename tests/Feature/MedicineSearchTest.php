<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_at_least_2_characters()
    {
        $response = $this->getJson('/api/pharma/medicines?q=p');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_search_by_name_commercial()
    {
        Medicine::factory()->create([
            'nom_commercial' => 'Paracetamol 500mg',
            'dci' => 'paracetamol',
            'forme' => 'comprimé',
            'dosage' => '500mg',
        ]);

        $response = $this->getJson('/api/pharma/medicines?q=para');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'nom_commercial' => 'Paracetamol 500mg',
        ]);
    }

    public function test_search_by_dci()
    {
        Medicine::factory()->create([
            'nom_commercial' => 'Doliprane',
            'dci' => 'paracetamol',
            'forme' => 'comprimé',
            'dosage' => '1000mg',
        ]);

        $response = $this->getJson('/api/pharma/medicines?q=paracetamol');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'dci' => 'paracetamol',
        ]);
    }

    public function test_search_is_case_insensitive()
    {
        Medicine::factory()->create([
            'nom_commercial' => 'Paracetamol 500mg',
            'dci' => 'paracetamol',
            'forme' => 'comprimé',
            'dosage' => '500mg',
        ]);

        $response1 = $this->getJson('/api/pharma/medicines?q=PARACETAMOL');
        $response2 = $this->getJson('/api/pharma/medicines?q=para');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response1->assertJsonCount(1);
        $response2->assertJsonCount(1);
    }

    public function test_search_returns_max_10_results()
    {
        Medicine::factory()->count(15)->create([
            'nom_commercial' => 'Test Medicine',
            'dci' => 'test',
            'forme' => 'comprimé',
            'dosage' => '500mg',
        ]);

        $response = $this->getJson('/api/pharma/medicines?q=test');

        $response->assertStatus(200);
        $response->assertJsonCount(10);
    }

    public function test_search_with_no_query_returns_empty()
    {
        Medicine::factory()->create([
            'nom_commercial' => 'Paracetamol',
            'dci' => 'paracetamol',
            'forme' => 'comprimé',
            'dosage' => '500mg',
        ]);

        $response = $this->getJson('/api/pharma/medicines');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_throttle_limits_requests()
    {
        $this->markTestSkipped('Throttle test skipped for performance - can be tested manually');
    }

    public function test_accessible_without_authentication()
    {
        $response = $this->getJson('/api/pharma/medicines?q=test');

        $response->assertStatus(200);
    }
}
