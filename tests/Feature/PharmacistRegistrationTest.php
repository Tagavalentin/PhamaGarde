<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pharmacy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacistRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_pharmacien_peut_s_inscrire_avec_sa_fiche_pharmacie()
    {
        $response = $this->postJson('/api/pharma/register-pharmacist', [
            'name' => 'Jean Dupont',
            'email' => 'jean@pharma.tg',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+22890000000',
            'nom' => 'Pharmacie Centrale',
            'adresse' => '123 Rue Principale',
            'quartier' => 'Lomé Centre',
            'ville' => 'Lomé',
            'latitude' => 6.1250,
            'longitude' => 1.2167,
            'telephone' => '+22890000001',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'pharmacy' => ['id', 'nom', 'est_valide'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jean@pharma.tg',
            'role' => 'pharmacist',
        ]);

        $this->assertDatabaseHas('pharmacies', [
            'nom' => 'Pharmacie Centrale',
            'est_valide' => false,
        ]);
    }

    public function test_la_pharmacie_est_non_validee_par_defaut()
    {
        $response = $this->postJson('/api/pharma/register-pharmacist', [
            'name' => 'Jean Dupont',
            'email' => 'jean@pharma.tg',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+22890000000',
            'nom' => 'Pharmacie Centrale',
            'adresse' => '123 Rue Principale',
            'quartier' => 'Lomé Centre',
            'ville' => 'Lomé',
            'latitude' => 6.1250,
            'longitude' => 1.2167,
            'telephone' => '+22890000001',
        ]);

        $pharmacy = Pharmacy::where('nom', 'Pharmacie Centrale')->first();
        $this->assertFalse($pharmacy->est_valide);
    }

    public function test_une_pharmacie_non_validee_n_apparait_pas_dans_la_liste_publique()
    {
        $pharmacy = Pharmacy::factory()->create(['est_valide' => false]);

        $response = $this->getJson('/api/pharma/pharmacies');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $ids = collect($data)->pluck('id');
        $this->assertNotContains($pharmacy->id, $ids);
    }

    public function test_seul_un_admin_peut_voir_les_pharmacies_en_attente()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pharmacy = Pharmacy::factory()->create(['est_valide' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/pharma/admin/pharmacies/pending');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $pharmacy->id]);
    }

    public function test_un_utilisateur_non_admin_ne_peut_pas_voir_les_pharmacies_en_attente()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharma/admin/pharmacies/pending');

        $response->assertStatus(403);
    }

    public function test_un_admin_peut_valider_une_pharmacie()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pharmacy = Pharmacy::factory()->create(['est_valide' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/pharma/admin/pharmacies/{$pharmacy->id}/approve");

        $response->assertStatus(200);
        
        $pharmacy->refresh();
        $this->assertTrue($pharmacy->est_valide);
    }

    public function test_un_admin_peut_rejeter_une_pharmacie()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/pharma/admin/pharmacies/{$pharmacy->id}/reject", [
                'motif' => 'Adresse incorrecte',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('pharmacies', ['id' => $pharmacy->id]);
    }

    public function test_apres_validation_la_pharmacie_apparait_dans_la_liste_publique()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $user->id,
            'est_valide' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/pharma/admin/pharmacies/{$pharmacy->id}/approve");

        $response = $this->getJson('/api/pharma/pharmacies');

        $response->assertStatus(200);
        $data = $response->json('data');
        $ids = collect($data)->pluck('id');
        $this->assertContains($pharmacy->id, $ids);
    }

    public function test_les_coordonnees_sont_validees_lors_de_l_inscription()
    {
        $response = $this->postJson('/api/pharma/register-pharmacist', [
            'name' => 'Jean Dupont',
            'email' => 'jean@pharma.tg',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+22890000000',
            'nom' => 'Pharmacie Centrale',
            'adresse' => '123 Rue Principale',
            'quartier' => 'Lomé Centre',
            'ville' => 'Lomé',
            'latitude' => 100, // Invalide
            'longitude' => 1.2167,
            'telephone' => '+22890000001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }
}
