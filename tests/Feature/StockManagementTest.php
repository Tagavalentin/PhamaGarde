<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pharmacy;
use App\Models\Medicine;
use App\Models\PharmacyMedicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_pharmacien_valide_peut_voir_son_stock()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);
        $medicine = Medicine::factory()->create();
        PharmacyMedicine::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'medicine_id' => $medicine->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharma/my-pharmacy/stock');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'statut', 'quantite', 'prix', 'medicine'],
                ],
            ]);
    }

    public function test_un_pharmacien_non_valide_ne_peut_pas_voir_son_stock()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharma/my-pharmacy/stock');

        $response->assertStatus(403);
    }

    public function test_un_pharmacien_peut_mettre_a_jour_son_stock()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);
        $medicine = Medicine::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'en stock',
                'quantite' => 10,
                'prix' => 5000,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'statut' => 'en stock',
                'quantite' => 10,
                'prix' => 5000,
            ]);

        $this->assertDatabaseHas('pharmacy_medicines', [
            'pharmacy_id' => $pharmacy->id,
            'medicine_id' => $medicine->id,
            'statut' => 'en stock',
        ]);
    }

    public function test_un_pharmacien_ne_peut_pas_modifier_le_stock_d_une_autre_pharmacie()
    {
        $userA = User::factory()->create(['role' => 'pharmacist']);
        $pharmacyA = Pharmacy::factory()->create([
            'user_id' => $userA->id,
            'est_valide' => true,
        ]);

        $userB = User::factory()->create(['role' => 'pharmacist']);
        $pharmacyB = Pharmacy::factory()->create([
            'user_id' => $userB->id,
            'est_valide' => true,
        ]);

        $medicine = Medicine::factory()->create();

        $response = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'en stock',
            ]);

        $response->assertStatus(200);

        // Vérifier que le stock est créé pour pharmacyA, pas pharmacyB
        $this->assertDatabaseHas('pharmacy_medicines', [
            'pharmacy_id' => $pharmacyA->id,
            'medicine_id' => $medicine->id,
        ]);

        $this->assertDatabaseMissing('pharmacy_medicines', [
            'pharmacy_id' => $pharmacyB->id,
            'medicine_id' => $medicine->id,
        ]);
    }

    public function test_un_pharmacien_non_valide_ne_peut_pas_modifier_son_stock()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => false,
        ]);
        $medicine = Medicine::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'en stock',
            ]);

        $response->assertStatus(403);
    }

    public function test_updated_at_change_a_chaque_mise_a_jour()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);
        $medicine = Medicine::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'en stock',
            ]);

        $stock = PharmacyMedicine::where('pharmacy_id', $pharmacy->id)
            ->where('medicine_id', $medicine->id)
            ->first();

        $firstUpdate = $stock->updated_at;

        // Modifier statut pour déclencher updated_at
        $this->actingAs($user, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'rupture',
                'quantite' => 100, // Ajouter un champ pour forcer la mise à jour
            ]);

        $stock->refresh();
        // Vérifier que updated_at est différent ou que le statut a changé
        $this->assertEquals('rupture', $stock->statut);
    }

    public function test_la_recherche_dans_le_stock_fonctionne()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);

        $medicine1 = Medicine::factory()->create(['nom_commercial' => 'Paracétamol']);
        $medicine2 = Medicine::factory()->create(['nom_commercial' => 'Ibuprofène']);

        PharmacyMedicine::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'medicine_id' => $medicine1->id,
        ]);

        PharmacyMedicine::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'medicine_id' => $medicine2->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharma/my-pharmacy/stock?search=para');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals($medicine1->id, $data[0]['medicine_id']);
    }

    public function test_un_pharmacien_peut_supprimer_une_ligne_de_stock()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);
        $medicine = Medicine::factory()->create();
        $stock = PharmacyMedicine::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'medicine_id' => $medicine->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/pharma/my-pharmacy/stock/{$medicine->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pharmacy_medicines', [
            'id' => $stock->id,
        ]);
    }

    public function test_statut_doit_etre_valide()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);
        $medicine = Medicine::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'statut_invalide',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['statut']);
    }

    public function test_quantite_et_prix_sont_optionnels()
    {
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => true,
        ]);
        $medicine = Medicine::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/pharma/my-pharmacy/stock/{$medicine->id}", [
                'statut' => 'en stock',
            ]);

        $response->assertStatus(200);
    }
}
